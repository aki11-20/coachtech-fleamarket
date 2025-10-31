<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUser(array $overrides = []): User {
        $user = User::create(array_merge([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->markEmailAsVerified();
        return $user;
    }

    private function createItem(User $seller = null): Item {
        if (!$seller) {
            $seller = $this->createVerifiedUser([
                'name' => 'Seller',
                'email' => 'seller0@example.com',
            ]);
        }

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

    public function test_it_redirects_to_checkout_when_user_presses_purchase_button() {
        $buyer = $this->createVerifiedUser(['name' => 'Buyer', 'email' => 'buyer1@example.com']);
        $item  = $this->createItem();

        $this->withSession([
            'purchase.address.' . $item->id => [
                'postal_code' => '123-4567',
                'address' => '京都府京都市',
                'building' => 'テストビル101',
            ],
        ]);

        $this->actingAs($buyer, 'web')
        ->from(route('purchase.show', ['item_id' => $item->id]))
        ->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_type' => 'card',
            'postal_code' => '123-4567',
            'address' => '京都府京都市',
            'building' => 'テストビル101',
        ])
        ->assertRedirect();
    }

    public function test_order_is_created_after_success_callback() {
        $buyer = $this->createVerifiedUser(['name' => 'Buyer', 'email' => 'buyer2@example.com']);
        $item = $this->createItem();

        $this->withSession([
            'purchase.address.' . $item->id => [
                'postal_code' => '123-4567',
                'address' => '京都府京都市',
                'building' => 'テストビル101',
            ],
        ]);

        $this->actingAs($buyer, 'web')
        ->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_type' => 'card',
            'postal_code' => '123-4567',
            'address' => '京都府京都市',
            'building' => '',
        ])
        ->assertRedirect();

        $this->get(route('payments.success'))
        ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_type' => 'card',
            'postal_code' => '123-4567',
            'address' => '京都府京都市',
        ]);
    }

    public function test_sold_badge_is_visible_on_index_after_success() {
        $buyer = $this->createVerifiedUser(['name' => 'Buyer', 'email' => 'buyer3@example.com']);
        $item = $this->createItem();

        $this->withSession([
            'purchase.address.' . $item->id => [
                'postal_code' => '123-4567',
                'address' => '京都府京都市',
                'building' => 'テストビル101',
            ],
        ]);

        $this->actingAs($buyer, 'web')
        ->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_type' => 'card',
            'postal_code' => '123-4567',
            'address' => '京都府京都市',
            'building' => '',
        ])
        ->assertRedirect();

        $this->get(route('payments.success'))
        ->assertRedirect();

        $this->get(route('items.index'))
        ->assertOk()
        ->assertSee('class="item-card__badge">Sold</span>', false)
        ->assertSee($item->product_name);
    }

    public function test_purchased_item_appears_in_profile_after_success() {
        $buyer = $this->createVerifiedUser(['name' => 'Buyer', 'email' => 'buyer4@example.com']);
        $item = $this->createItem();

        $this->withSession([
            'purchase.address.' . $item->id => [
                'postal_code' => '123-4567',
                'address' => '京都府京都市',
                'building' => 'テストビル101',
            ],
        ]);

        $this->actingAs($buyer, 'web')
        ->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_type' => 'card',
            'postal_code' => '123-4567',
            'address' => '京都府京都市',
            'building' => '',
        ])
        ->assertRedirect();

        $this->get(route('payments.success'))
        ->assertRedirect();

        $this->actingAs($buyer, 'web')
        ->get(route('mypage'))
        ->assertOk()
        ->assertSee($item->product_name);
    }
}
