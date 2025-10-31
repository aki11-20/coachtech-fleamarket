<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ItemShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_all_required_fields_on_item_showpage()
    {
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);
        $commenter = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'イヤホン',
            'brand_name' => 'Sony',
            'description' => 'ワイヤレス',
            'condition' => '良好',
            'price' => 10000,
            'image' => 'images/earphones.jpg',
        ]);

        $category1 = Category::create(['name' => '家電']);
        $category2 = Category::create(['name' => 'アクセサリー']);
        $item->categories()->attach([$category1->id, $category2->id]);

        DB::table('likes')->insert([
            ['user_id' => $seller->id, 'item_id' => $item->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $commenter->id, 'item_id' => $item->id, 'created_at' =>now(), 'updated_at' => now()],
        ]);

        DB::table('comments')->insert([
            [
                'user_id' => $commenter->id,
                'item_id' => $item->id,
                'body' => 'いいですね',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $seller->id,
                'item_id' => $item->id,
                'body' => '良ければどうぞ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get(route('items.show', $item->id));

        $response->assertOk()
        ->assertSee($item->image)
        ->assertSee('イヤホン')
        ->assertSee('Sony')
        ->assertSee('¥' . number_format(10000) . '(税込)')
        ->assertSee('2')
        ->assertSee('コメント(2件)')
        ->assertSee('ワイヤレス')
        ->assertSee('家電')
        ->assertSee('アクセサリー')
        ->assertSee('良好')
        ->assertSee('Test')
        ->assertSee('いいですね')
        ->assertSee('良ければどうぞ');
    }

    public function test_it_shows_multiple_selected_categories_on_item_show_page() {
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'キーボード',
            'brand_name' => 'HHKB',
            'description' => '打鍵感が最高',
            'condition' => '良好',
            'price' => 20000,
            'image' => 'images/keyboard.jpg',
        ]);

        $categoryA = Category::create([
            'name' => '家電'
        ]);
        $categoryB = Category::create([
            'name' => 'メンズ'
        ]);
        $categoryC =Category::create([
            'name' => 'レディース'
        ]);

        $item->categories()->attach([$categoryA->id, $categoryB->id, $categoryC->id]);

        $response = $this->get(route('items.show', $item->id));

        $response->assertOk()
        ->assertSee('家電')
        ->assertSee('メンズ')
        ->assertSee('レディース');
    }
}
