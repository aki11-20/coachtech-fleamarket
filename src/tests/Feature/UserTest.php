<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUserWithProfile(array $userOverrides = [], array $profileOverrides = []): User {
        $user = User::create(array_merge([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password123'),
        ], $userOverrides));
        $user->markEmailAsVerified();

        $user->profile()->create(array_merge([
            'nickname' => 'test',
            'postal_code' => '600-8001',
            'address' => '京都府京都市',
            'building' => 'テストビル101',
            'image' => 'images/profile/buyer.jpg',
        ], $profileOverrides));
        return $user;
    }

    private function createSellerWithItem(string $email, string $productName): Item {
        $seller = $this->createVerifiedUserWithProfile([
            'name' => 'Seller',
            'email' => $email,
        ], [
            'nickname' => '出品者',
            'image' => 'images/profile/seller.jpg',
        ]);

        return Item::create([
            'user_id' => $seller->id,
            'product_name' => $productName,
            'brand_name' => 'Sony',
            'description' => '説明',
            'condition' => '良好',
            'price' => 12000,
            'image' => 'images/earphones.jpg',
        ]);
    }

    public function test_profile_page_shows_profile_image_username_and_purchased_list()
    {
        $buyer = $this->createVerifiedUserWithProfile([
                'email' => 'buyer1@example.com',
            ], [
                'nickname' => '買い手ユーザー', 'image' => 'images/profile/buyer1.jpg',
            ]
        );

        $firstPurchasedItem = $this->createSellerWithItem('seller_a@example.com', 'イヤホンA');
        $secondPurchasedItem = $this->createSellerWithItem('seller_b@example.com', 'イヤホンB');

        Order::create([
            'user_id' => $buyer->id,
            'item_id' => $firstPurchasedItem->id,
            'payment_type' => 'card',
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
            'postal_code' => '600-8001',
            'address' => '京都府京都市',
            'building' => 'テストビル101',
        ]);
        Order::create([
            'user_id' => $buyer->id,
            'item_id' => $secondPurchasedItem->id,
            'payment_type' => 'card',
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
            'postal_code' => '600-8001',
            'address' => '京都府京都市',
            'building' => 'テストビル101',
        ]);

        $this->actingAs($buyer, 'web')
            ->get(route('mypage'))
            ->assertOk()
            ->assertSee('http://localhost/images/profile/buyer1.jpg', false)
            ->assertSee('買い手ユーザー')
            ->assertSee('イヤホンA')
            ->assertSee('イヤホンB');
    }

    public function test_profile_page_shows_selling_list_on_sell_tab() {
        $seller = $this->createVerifiedUserWithProfile([
            'name' => 'SellerSelf',
            'email' => 'seller_self@example.com',
        ],  [
            'nickname' => '出品者本人',
            'image' => 'images/profile/seller_self.jpg',
        ]
        );

        $firstSellingItem = Item::create([
            'user_id' => $seller->id,
            'product_name' => '出品イヤホン1',
            'brand_name' => 'Sony',
            'description' => '説明1',
            'condition' => '良好',
            'price' => 9800,
            'image' => 'images/earphones.jpg',
        ]);
        $secondSellingItem = Item::create([
            'user_id' => $seller->id,
            'product_name' => '出品イヤホン2',
            'brand_name' => 'Sony',
            'description' => '説明2',
            'condition' => '良好',
            'price' => 15800,
            'image' => 'images/earphones.jpg',
        ]);

        $this->actingAs($seller, 'web')
        ->get(route('mypage', ['tab' => 'sell']))
        ->assertOk()
        ->assertSee('http://localhost/images/profile/seller_self.jpg', false)
        ->assertSee('出品者本人')
        ->assertSee('出品イヤホン1')
        ->assertSee('出品イヤホン2');
    }
}
