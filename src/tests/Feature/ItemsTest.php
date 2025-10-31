<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class ItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_all_items()
    {
        $sellerA = User::create([
            'name' => 'TestA',
            'email' => 'testa@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $sellerB = User::create([
            'name' => 'TestB',
            'email' => 'testb@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $item = Item::create([
            'user_id' => $sellerA->id,
            'product_name' => 'カメラ',
            'brand_name' => 'Nikon',
            'description' => 'Good camera',
            'condition' => '良好',
            'price' => 10000,
            'image' => 'images/sample1.jpg',
        ]);

        $item2 = Item::create([
            'user_id' => $sellerB->id,
            'product_name' => 'ヘッドホン',
            'brand_name' => 'Sony',
            'description' => 'Nice sound',
            'condition' => '目立った傷や汚れなし',
            'price' => 8000,
            'image' => 'images/sample2.jpg',
        ]);

        $soldItem = Item::create([
            'user_id' => $sellerB->id,
            'product_name' => 'キーボード',
            'brand_name' => 'HHKB',
            'description' => 'Top quality',
            'condition' => '良好',
            'price' => 20000,
            'image' => 'images/sample3.jpg',
        ]);

        Order::create([
            'user_id' => $sellerA->id,
            'item_id' => $soldItem->id,
            'payment_type' => 'card',
            'postal_code' => '123-456',
            'address' => 'Tokyo',
            'building' => '',
        ]);

        $response = $this->get(route('items.index'));

        $response->assertOk()
        ->assertSee('カメラ')
        ->assertSee('ヘッドホン')
        ->assertSee('キーボード');
    }

    public function test_sold_items_show_sold_badge_on_index() {
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $unsold = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'Bag',
            'brand_name' => 'Anello',
            'description' => 'カジュアル',
            'condition' => '良好',
            'price' => 5000,
            'image' => 'images/bag.jpg',
        ]);

        $sold = Item::create([
            'user_id' => $seller->id,
            'product_name' => '靴',
            'brand_name' => 'Nike',
            'description' => 'かっこいい靴',
            'condition' => '良好',
            'price' => 12000,
            'image' => 'images/shoes.jpg',
        ]);

        Order::create([
            'user_id' => $seller->id,
            'item_id' => $sold->id,
            'payment_type' => 'convenience',
            'postal_code' => '123-4567',
            'address' => '京都',
            'building' => '',
        ]);

        $response = $this->get(route('items.index'));

        $response->assertOk()
        ->assertSee('靴')
        ->assertSee('class="item-card__badge">Sold</span>', false);

        $response->assertSee('Bag');
    }

    public function test_my_own_items_are_hidden_for_authenticated_user() {
        $me = User::create([
            'name' => 'Me',
            'email' => 'me@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $myItem1 = Item::create([
            'user_id' => $me->id,
            'product_name' => 'ジャケット',
            'brand_name' => 'Uniqlo',
            'description' => '暖かい',
            'condition' => '良好',
            'price' => 7000,
            'image' => 'images/jacket.jpg',
        ]);

        $myItem2 = Item::create([
            'user_id' => $me->id,
            'product_name' => 'Watch',
            'brand_name' => 'Apple',
            'description' => '最新',
            'condition' => '良好',
            'price' => 40000,
            'image' => 'images/watch.jpg',
        ]);

        $othersItem = Item::create([
            'user_id' => $other->id,
            'product_name' => '帽子',
            'brand_name' => 'NewEra',
            'description' => 'Cap',
            'condition' => '良好',
            'price' => 300,
            'image' => 'images/Cap.jpg',
        ]);

        $this->actingAs($me);

        $response = $this->get(route('items.index'));

        $response->assertOk()
        ->assertDontSee('ジャケット')
        ->assertDontSee('Watch');

        $response->assertSee('帽子');
    }
}
