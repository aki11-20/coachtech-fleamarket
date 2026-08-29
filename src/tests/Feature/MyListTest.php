<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MyListTest extends TestCase
{
    use RefreshDatabase;

    public function test_mylist_shows_only_liked_items_for_authenticated_user()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $liked1 = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'カメラ',
            'brand_name' => 'Nikon',
            'description' => '高画質',
            'condition' => '良好',
            'price' => 10000,
            'image' => 'images/a.jpg',
        ]);
        $liked2 = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'ヘッドホン',
            'brand_name' => 'Sony',
            'description' => '高音質',
            'condition' => '良好',
            'price' => 8000,
            'image' => 'images/b.jpg',
        ]);
        $notLiked = Item::create([
            'user_id' => $seller->id,
            'product_name' => '本',
            'brand_name' => '',
            'description' => '絵本',
            'condition' => '良好',
            'price' => 1200,
            'image' => 'images/c.jpg',
        ]);

        DB::table('likes')->insert([
            ['user_id' => $user->id, 'item_id' => $liked1->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'item_id' => $liked2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user);

        $response = $this->get(route('items.index', ['tab' => 'mylist']));

        $response->assertOk()
        ->assertSee('カメラ')
        ->assertSee('ヘッドホン')
        ->assertDontSee('本');
    }

    public function test_mylist_shows_sold_badge_for_liked_and_sold_items() {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $likedSold = Item::create([
            'user_id' => $seller->id,
            'product_name' => '靴',
            'brand_name' => 'Nike',
            'description' => '新作',
            'condition' => '良好',
            'price' => 12000,
            'image' => 'images/shoes.jpg',
        ]);

        Order::create([
            'user_id' => $user->id,
            'item_id' => $likedSold->id,
            'payment_type' => 'card',
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
            'postal_code' => '123-4567',
            'address' => '京都',
            'building' => '',
        ]);

        DB::table('likes')->insert([
            'user_id' => $user->id,
            'item_id' => $likedSold->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('items.index', ['tab' => 'mylist']));

        $response->assertOk()
        ->assertSee('靴')
        ->assertSee('class="item-card__badge">Sold</span>', false);
    }

    public function test_guest_sees_nothing_in_mylist() {
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => '帽子',
            'brand_name' => 'NewEra',
            'description' => '限定',
            'condition' => '良好',
            'price' => 3000,
            'image' => 'images/cap.jpg',
        ]);

        $response = $this->get(route('items.index', ['tab' => 'mylist']));
        $response->assertOk()
        ->assertDontSee('帽子');
    }
}
