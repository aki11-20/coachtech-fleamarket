<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Stripe\Exception\SignatureVerificationException;
use Tests\Concerns\InteractsWithStripeFakes;
use Tests\TestCase;
use UnexpectedValueException;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase, InteractsWithStripeFakes;

    private function createOrder(string $status = Order::STATUS_PENDING): Order
    {
        $seller = User::create([
            'name' => 'Seller',
            'email' => uniqid('seller', true) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        $buyer = User::create([
            'name' => 'Buyer',
            'email' => uniqid('buyer', true) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'Webhook商品',
            'brand_name' => 'Test',
            'description' => 'Webhook test item',
            'condition' => '良好',
            'price' => 12000,
            'image' => 'images/webhook.jpg',
        ]);

        return Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_type' => 'card',
            'status' => $status,
            'stripe_checkout_session_id' => 'cs_test_webhook_' . $item->id,
            'reserved_until' => $status === Order::STATUS_PENDING ? now()->addHour() : null,
            'paid_at' => $status === Order::STATUS_PAID ? now() : null,
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区',
        ]);
    }

    private function postWebhook()
    {
        return $this->withHeader('Stripe-Signature', 'test_signature')
            ->postJson(route('stripe.webhook'), ['id' => 'evt_test']);
    }

    private function expectEvent(string $type, $session): void
    {
        $this->stripePaymentServiceMock
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn($this->makeStripeEvent($type, $session));
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->once()
            ->andReturnNull();
    }

    public function test_completed_paid_marks_order_as_paid()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, [
            'status' => 'complete',
            'payment_status' => 'paid',
        ]);
        $this->expectEvent('checkout.session.completed', $session);

        $this->postWebhook()->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->paid_at);
        $this->assertNull($order->reserved_until);
    }

    public function test_completed_unpaid_keeps_order_pending_and_saves_voucher_expiration()
    {
        $order = $this->createOrder();
        $order->update(['payment_type' => 'convenience']);
        $session = $this->makeCheckoutSession($order, [
            'status' => 'complete',
            'payment_status' => 'unpaid',
        ]);
        $voucherExpiresAt = now()->addDays(3)->startOfSecond();

        $this->stripePaymentServiceMock
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn($this->makeStripeEvent('checkout.session.completed', $session));
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->with($session->id)
            ->andReturn($session);
        $this->stripePaymentServiceMock
            ->shouldReceive('getVoucherExpiresAt')
            ->once()
            ->andReturn($voucherExpiresAt);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->once()
            ->andReturnNull();

        $this->postWebhook()->assertOk();

        $order->refresh();
        $this->assertTrue($order->isPending());
        $this->assertTrue($order->reserved_until->equalTo($voucherExpiresAt));
        $this->assertNull($order->paid_at);
        $this->assertNull($order->cancelled_at);
    }

    public function test_async_payment_succeeded_marks_order_as_paid()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, ['payment_status' => 'paid']);
        $this->expectEvent('checkout.session.async_payment_succeeded', $session);

        $this->postWebhook()->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_async_payment_failed_marks_order_as_cancelled()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order);
        $this->expectEvent('checkout.session.async_payment_failed', $session);

        $this->postWebhook()->assertOk();

        $this->assertTrue($order->fresh()->isCancelled());
    }

    public function test_expired_session_marks_order_as_cancelled()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, ['status' => 'expired']);
        $this->expectEvent('checkout.session.expired', $session);

        $this->postWebhook()->assertOk();

        $this->assertTrue($order->fresh()->isCancelled());
    }

    public function test_receiving_same_event_twice_is_idempotent()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, ['payment_status' => 'paid']);
        $event = $this->makeStripeEvent('checkout.session.async_payment_succeeded', $session);
        $this->stripePaymentServiceMock
            ->shouldReceive('constructWebhookEvent')
            ->twice()
            ->andReturn($event);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->twice()
            ->andReturnNull();

        $this->postWebhook()->assertOk();
        $paidAt = $order->fresh()->paid_at;
        $this->postWebhook()->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue($order->fresh()->paid_at->equalTo($paidAt));
    }

    public function test_failed_and_expired_events_do_not_cancel_paid_order()
    {
        $order = $this->createOrder(Order::STATUS_PAID);
        $session = $this->makeCheckoutSession($order);
        $failed = $this->makeStripeEvent('checkout.session.async_payment_failed', $session);
        $expired = $this->makeStripeEvent('checkout.session.expired', $session);
        $this->stripePaymentServiceMock
            ->shouldReceive('constructWebhookEvent')
            ->twice()
            ->andReturn($failed, $expired);
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->twice()
            ->andReturnNull();

        $this->postWebhook()->assertOk();
        $this->postWebhook()->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertNull($order->fresh()->cancelled_at);
    }

    public function test_invalid_metadata_is_rejected()
    {
        $order = $this->createOrder();
        $session = $this->makeCheckoutSession($order, [
            'metadata' => [
                'order_id' => (string) $order->id,
                'item_id' => '999999',
                'user_id' => (string) $order->user_id,
                'payment_type' => $order->payment_type,
            ],
        ]);
        $this->stripePaymentServiceMock
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andReturn($this->makeStripeEvent('checkout.session.completed', $session));
        $this->stripePaymentServiceMock
            ->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->with($session->id)
            ->andReturn($session);
        $this->stripePaymentServiceMock
            ->shouldReceive('getVoucherExpiresAt')
            ->once()
            ->with($session)
            ->andReturn(now()->addDays(3));
        $this->stripePaymentServiceMock
            ->shouldReceive('assertSessionMatchesOrder')
            ->once()
            ->andThrow(new UnexpectedValueException('metadata mismatch'));

        $this->postWebhook()->assertStatus(400);

        $this->assertTrue($order->fresh()->isPending());
    }

    public function test_invalid_signature_is_rejected()
    {
        $order = $this->createOrder();
        $this->stripePaymentServiceMock
            ->shouldReceive('constructWebhookEvent')
            ->once()
            ->andThrow(SignatureVerificationException::factory(
                'Invalid signature',
                '{}',
                'bad_signature'
            ));

        $this->postWebhook()->assertStatus(400);

        $this->assertTrue($order->fresh()->isPending());
    }
}
