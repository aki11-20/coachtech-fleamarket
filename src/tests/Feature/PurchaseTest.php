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

class PurchaseTest extends TestCase
{
    use RefreshDatabase, InteractsWithStripeFakes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));
        $user->markEmailAsVerified();

        return $user;
    }

    private function createItem(?User $seller = null): Item
    {
        $seller = $seller ?: $this->createVerifiedUser([
            'name' => 'Seller',
            'email' => 'seller@example.com',
        ]);

        return Item::create([
            'user_id' => $seller->id,
            'product_name' => 'イヤホン',
            'brand_name' => 'Sony',
            'description' => 'ワイヤレス',
            'condition' => '良好',
            'price' => 12000,
            'image' => 'images/earphones.jpg',
        ]);
    }

    private function beginPurchase(User $buyer, Item $item)
    {
        return $this->withSession([
            'purchase.address.' . $item->id => [
                'postal_code' => '123-4567',
                'address' => '京都府京都市',
                'building' => 'テストビル101',
            ],
        ])->actingAs($buyer, 'web')->post(
            route('purchase.store', ['item_id' => $item->id]),
            ['payment_type' => 'card']
        );
    }

    private function createOrder(User $buyer, Item $item, string $status): Order
    {
        return Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_type' => 'card',
            'status' => $status,
            'stripe_checkout_session_id' => 'cs_test_existing_' . $item->id,
            'reserved_until' => $status === Order::STATUS_PENDING ? now()->addHour() : null,
            'paid_at' => $status === Order::STATUS_PAID ? now() : null,
            'postal_code' => '123-4567',
            'address' => '京都府京都市',
            'building' => null,
        ]);
    }

    public function test_checkout_start_creates_pending_order_and_saves_session_id()
    {
        $buyer = $this->createVerifiedUser(['email' => 'buyer1@example.com']);
        $item = $this->createItem();

        $this->stripePaymentServiceMock
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturnUsing(fn (Order $order) => $this->makeCheckoutSession($order));

        $response = $this->beginPurchase($buyer, $item);

        $order = Order::firstOrFail();
        $response->assertRedirect('https://checkout.stripe.test/session/' . $order->id);
        $this->assertTrue($order->fresh()->isPending());
        $this->assertSame('cs_test_order_' . $order->id, $order->fresh()->stripe_checkout_session_id);
        $this->assertNotNull($order->fresh()->reserved_until);
    }

    public function test_stripe_api_exception_cancels_created_order()
    {
        $buyer = $this->createVerifiedUser(['email' => 'buyer2@example.com']);
        $item = $this->createItem();

        $this->stripePaymentServiceMock
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andThrow(new RuntimeException('Stripe unavailable'));

        $this->beginPurchase($buyer, $item)
            ->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        $order = Order::firstOrFail();
        $this->assertTrue($order->isCancelled());
        $this->assertNotNull($order->cancelled_at);
        $this->assertNull($order->reserved_until);
    }

    public function test_pending_order_is_not_displayed_as_sold()
    {
        $buyer = $this->createVerifiedUser(['email' => 'buyer3@example.com']);
        $item = $this->createItem();

        $this->stripePaymentServiceMock
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturnUsing(fn (Order $order) => $this->makeCheckoutSession($order));

        $this->beginPurchase($buyer, $item)->assertRedirect();

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('取引中')
            ->assertDontSee('class="item-card__badge">Sold</span>', false);
    }

    public function test_another_user_cannot_purchase_item_with_pending_order()
    {
        $firstBuyer = $this->createVerifiedUser(['email' => 'buyer4@example.com']);
        $secondBuyer = $this->createVerifiedUser(['email' => 'buyer5@example.com']);
        $item = $this->createItem();
        $this->createOrder($firstBuyer, $item, Order::STATUS_PENDING);
        $this->stripePaymentServiceMock->shouldReceive('createCheckoutSession')->never();

        $this->beginPurchase($secondBuyer, $item)->assertSessionHasErrors('purchase');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_paid_item_cannot_be_purchased_again()
    {
        $firstBuyer = $this->createVerifiedUser(['email' => 'buyer6@example.com']);
        $secondBuyer = $this->createVerifiedUser(['email' => 'buyer7@example.com']);
        $item = $this->createItem();
        $this->createOrder($firstBuyer, $item, Order::STATUS_PAID);
        $this->stripePaymentServiceMock->shouldReceive('createCheckoutSession')->never();

        $this->beginPurchase($secondBuyer, $item)->assertSessionHasErrors('purchase');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_user_cannot_purchase_own_item()
    {
        $seller = $this->createVerifiedUser(['email' => 'seller-own@example.com']);
        $item = $this->createItem($seller);
        $this->stripePaymentServiceMock->shouldReceive('createCheckoutSession')->never();

        $this->beginPurchase($seller, $item)->assertSessionHasErrors('purchase');

        $this->assertDatabaseCount('orders', 0);
    }
}
