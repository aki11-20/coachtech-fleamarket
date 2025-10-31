<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUser(array $overrides = []): User {
        $user = User::create(array_merge([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->markEmailAsVerified();
        return $user;
    }

    private function createItem(): Item {
        $seller = $this->createVerifiedUser([
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

    public function test_address_changed_on_edit_page_is_reflected_in_purchase_show() {
        $buyer = $this->createVerifiedUser([
            'email' => 'buyer1@example.com',
        ]);
        $item = $this->createItem();

        $response = $this->actingAs($buyer, 'web')
        ->post(route('purchase.address.update', ['item_id' => $item->id]), [
            'postal_code' => '600-8001',
            'address' => '京都市伏見区',
            'building' => 'テスト101',
        ]);
        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        $this->actingAs($buyer, 'web')
        ->get(route('purchase.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('〒 600-8001')
        ->assertSee('京都市伏見区 テスト101');
    }

    public function test_purchased_order_is_saved_with_the_changed_shipping_address() {
        $buyer = $this->createVerifiedUser([
            'email' => 'buyer2@example.com',
        ]);
        $item = $this->createItem();

        $this->actingAs($buyer, 'web')
        ->post(route('purchase.address.update', ['item_id' => $item->id]), [
            'postal_code' => '604-8005',
            'address' => '東京都渋谷区',
            'building' => '501',
        ])
        ->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        $this->actingAs($buyer, 'web')
        ->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_type' => 'card',
        ])
        ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'payment_type' => 'card',
            'postal_code' => '604-8005',
            'address' => '東京都渋谷区',
            'building' => '501',
        ]);
    }
}
