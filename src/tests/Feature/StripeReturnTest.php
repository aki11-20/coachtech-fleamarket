<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Concerns\InteractsWithStripeFakes;
use Tests\TestCase;

class StripeReturnTest extends TestCase
{
    use RefreshDatabase, InteractsWithStripeFakes;

    private function createVerifiedUser(string $email): User
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $user->markEmailAsVerified();

        return $user;
    }

    private function createOrder(string $status = Order::STATUS_PENDING): Order
    {
        $seller = $this->createVerifiedUser('seller-' . uniqid() . '@example.com');
        $buyer = $this->createVerifiedUser('buyer-' . uniqid() . '@example.com');
        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'Return商品',
            'brand_name' => 'Test',
            'description' => 'Return test item',
            'condition' => '良好',
            'price' => 15000,
            'image' => 'images/return.jpg',
        ]);

        return Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_type' => 'card',
            'status' => $status,
            'stripe_checkout_session_id' => 'cs_test_return_' . $item->id,
            'reserved_until' => $status === Order::STATUS_PENDING ? now()->addHour() : null,
            'paid_at' => $status === Order::STATUS_PAID ? now() : null,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
        ]);
    }

    public function test_success_marks_paid_session_as_paid()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, [
            'status' => 'complete',
            'payment_status' => 'paid',
        ]);
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->with($session->id)
            ->andReturn($session);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->once()
            ->andReturnNull();

        $this->actingAs($order->user, 'web')
            ->get(route('payments.success', ['session_id' => $session->id]))
            ->assertRedirect(route('items.show', ['item_id' => $order->item_id]));

        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_success_keeps_unpaid_session_pending()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, [
            'status' => 'complete',
            'payment_status' => 'unpaid',
        ]);
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->andReturn($session);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->once()
            ->andReturnNull();

        $this->actingAs($order->user, 'web')
            ->get(route('payments.success', ['session_id' => $session->id]))
            ->assertRedirect(route('items.show', ['item_id' => $order->item_id]));

        $this->assertTrue($order->fresh()->isPending());
    }

    public function test_success_cannot_confirm_another_users_session()
    {
        $order = $this->createOrder();
        $otherUser = $this->createVerifiedUser('other@example.com');
        $session = $this->makeCheckoutSession($order, ['payment_status' => 'paid']);
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->andReturn($session);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->never();

        $this->actingAs($otherUser, 'web')
            ->get(route('payments.success', ['session_id' => $session->id]))
            ->assertRedirect(route('items.index'));

        $this->assertTrue($order->fresh()->isPending());
    }

    public function test_success_does_not_mark_order_paid_for_invalid_session()
    {
        $order = $this->createOrder();
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->andThrow(new RuntimeException('Invalid Session'));

        $this->actingAs($order->user, 'web')
            ->get(route('payments.success', ['session_id' => 'cs_invalid']))
            ->assertRedirect(route('items.index'));

        $this->assertTrue($order->fresh()->isPending());
    }

    public function test_cancel_marks_order_cancelled_only_after_open_session_is_expired()
    {
        $order = $this->createOrder();
        $openSession = $this->makeCheckoutSession($order, ['status' => 'open']);
        $expiredSession = $this->makeCheckoutSession($order, ['status' => 'expired']);
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->andReturn($openSession);
        $this->stripePaymentServiceMock
            ->shouldReceive('expireCheckoutSession')
            ->once()
            ->with($openSession->id)
            ->andReturn($expiredSession);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->twice()
            ->andReturnNull();

        $this->actingAs($order->user, 'web')
            ->get(route('payments.cancel', ['order' => $order->id]))
            ->assertRedirect(route('items.show', ['item_id' => $order->item_id]));

        $this->assertTrue($order->fresh()->isCancelled());
    }

    public function test_cancel_keeps_order_pending_when_expire_api_fails()
    {
        $order = $this->createOrder();
        $openSession = $this->makeCheckoutSession($order, ['status' => 'open']);
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->andReturn($openSession);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->once()
            ->andReturnNull();
        $this->stripePaymentServiceMock
            ->shouldReceive('expireCheckoutSession')
            ->once()
            ->andThrow(new RuntimeException('Stripe unavailable'));

        $this->actingAs($order->user, 'web')
            ->get(route('payments.cancel', ['order' => $order->id]))
            ->assertRedirect(route('items.show', ['item_id' => $order->item_id]));

        $this->assertTrue($order->fresh()->isPending());
    }

    public function test_cancel_does_not_downgrade_paid_order()
    {
        $order = $this->createOrder(Order::STATUS_PAID);
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->never();
        $this->stripePaymentServiceMock
            ->shouldReceive('expireCheckoutSession')
            ->never();

        $this->actingAs($order->user, 'web')
            ->get(route('payments.cancel', ['order' => $order->id]))
            ->assertRedirect(route('items.show', ['item_id' => $order->item_id]));

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertNull($order->fresh()->cancelled_at);
    }
}
