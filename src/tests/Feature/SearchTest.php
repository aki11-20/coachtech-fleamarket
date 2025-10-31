<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_search_items_by_partial_product_name()
    {
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        Item::create([
            'user_id' => $seller->id,
            'product_name' => 'カメラ',
            'brand_name' => 'Nikon',
            'description' => '高画質',
            'condition' => '良好',
            'price' => 10000,
            'image' => 'images/a.jpg',
        ]);
        Item::create([
            'user_id' => $seller->id,
            'product_name' => 'ヘッドホン',
            'brand_name' => 'Sony',
            'description' => '高音質',
            'condition' => '良好',
            'price' => 8000,
            'image' => 'images/b.jpg',
        ]);
        Item::create([
            'user_id' => $seller->id,
            'product_name' => 'マグカップ',
            'brand_name' => '',
            'description' => '新品',
            'condition' => '良好',
            'price' => 1500,
            'image' => 'images/c.jpg',
        ]);

        $response = $this->get(route('items.index', ['keyword' => 'カメ']));

        $response->assertOk()
        ->assertSee('カメラ')
        ->assertDontSee('ヘッドホン')
        ->assertDontSee('マグカップ');

        $response->assertSee('name="keyword"', false)
        ->assertSee('value="カメ"', false);
    }

    public function test_keyword_is_preserved_when_switching_to_mylist_and_filter_applies_on_likes() {
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
        $likeMatch = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'ノイズキャンセリングヘッドホン',
            'brand_name' => 'Sony',
            'description' => '高音質',
            'condition' => '良好',
            'price' => 18000,
            'image' => 'images/headphone.jpg',
        ]);
        $likeNotMatch = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'カメラ',
            'brand_name' => 'Nikon',
            'description' => '高画質',
            'condition' => '良好',
            'price' => 30000,
            'image' => 'images/cam.jpg',
        ]);
        $notLiked = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'マグカップ',
            'brand_name' => '',
            'description' => '新品',
            'condition' => '良好',
            'price' => 1200,
            'image' => 'images/mug.jpg',
        ]);

        DB::table('likes')->insert([
            ['user_id' => $user->id, 'item_id' => $likeMatch->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'item_id' => $likeNotMatch->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user);

        $response = $this->get(route('items.index', ['tab' => 'mylist', 'keyword' => 'ヘッド']));

        $response->assertOk()
        ->assertSee('ノイズキャンセリングヘッドホン')
        ->assertDontSee('カメラ')
        ->assertDontSee('マグカップ');

        $response->assertSee('name="keyword"', false)
        ->assertSee('value="ヘッド"', false);
    }
}
