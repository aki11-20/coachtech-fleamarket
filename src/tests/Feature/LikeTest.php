<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_user_can_like_an_item_and_count_increases_and_icon_fills()
    {
        $seller = User::create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);
        $liker = User::create([
            'name' => 'Liker',
            'email' => 'liker@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $seller->markEmailAsVerified();
        $liker->markEmailAsVerified();

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'ヘッドホン',
            'brand_name' => 'Sony',
            'description' => 'ノイズキャンセリング',
            'condition' => '良好',
            'price' => 12000,
            'image' => 'images/headphones.jpg',
        ]);

        $this->actingAs($liker, 'web');
        $this->get(route('items.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('☆', false)
        ->assertSee((string)($item->likes()->count()), false);

        $this->from(route('items.show', ['item_id' => $item->id]))
        ->post(route('items.like', ['item_id' => $item->id]))
        ->assertRedirect(route('items.show', ['item_id' => $item->id]));

        $this->assertDatabaseHas('likes', [
            'user_id' => $liker->id,
            'item_id' => $item->id,
        ]);

        $this->get(route('items.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('★', false)
        ->assertSee('1', false);
    }

    public function test_liked_icon_is_filled_when_already_liked() {
        $seller = User::create([
            'name' => 'Seller2',
            'email' => 'seller2@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);
        $liker = User::create([
            'name' => 'Liker2',
            'email' => 'liker2@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $seller->markEmailAsVerified();
        $liker->markEmailAsVerified();

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'カメラ',
            'brand_name' => 'Nikon',
            'description' => '高画質',
            'condition' => '良好',
            'price' => 50000,
            'image' => 'images/camera.jpg',
        ]);

        DB::table('likes')->insert([
            'user_id' => $liker->id,
            'item_id' => $item->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($liker, 'web')
        ->get(route('items.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('★',false);
    }

    public function test_user_can_unlike_and_count_decreases_and_icon_outlines() {
        $seller = User::create([
            'name' => 'Seller3',
            'email' => 'seller3@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);
        $liker = User::create([
            'name' => 'Liker3',
            'email' => 'liker3@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $seller->markEmailAsVerified();
        $liker->markEmailAsVerified();

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'キーボード',
            'brand_name' => 'HHKB',
            'description' => '押しやすい',
            'condition' => '良好',
            'price' => 20000,
            'image' => 'images/keyboard.jpg',
        ]);

        DB::table('likes')->insert([
            'user_id' => $liker->id,
            'item_id' => $item->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($liker, 'web');

        $this->from(route('items.show', ['item_id' => $item->id]))
        ->delete(route('items.unlike', ['item_id' => $item->id]))
        ->assertRedirect(route('items.show', ['item_id' => $item->id]));

        $this->assertDatabaseMissing('likes', [
            'user_id' => $liker->id,
            'item_id' => $item->id,
        ]);

        $this->get(route('items.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('☆', false)
        ->assertSee('0', false);
    }

    public function test_guest_cannot_like_and_is_redirected_to_login() {
        $seller = User::create([
            'name' => 'Seller4',
            'email' => 'seller4@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
        ]);

        $seller->markEmailAsVerified();

        $item = Item::create([
            'user_id' => $seller->id,
            'product_name' => 'マグカップ',
            'brand_name' => '',
            'description' => '新品',
            'condition' => '良好',
            'price' => 800,
            'image' => 'images/mug.jpg',
        ]);

        $this->post(route('items.like', ['item_id' => $item->id]))
        ->assertRedirect(route('login'));
    }
}
