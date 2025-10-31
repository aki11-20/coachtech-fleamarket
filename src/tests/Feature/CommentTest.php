<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;


class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUser(array $overrides = []): User {
        $user = User::create(array_merge([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->markEmailAsVerified();
        return $user;
    }
    private function createItem(): Item {
        $seller = $this->createVerifiedUser([
            'email' => 'seller@example.com', 'name' => 'Seller'
        ]);
        return Item::create([
            'user_id'      => $seller->id,
            'product_name' => 'ヘッドホン',
            'brand_name'   => 'Sony',
            'description'  => 'ノイズキャンセリング',
            'condition'    => '良好',
            'price'        => 12000,
            'image'        => 'images/headphones.jpg',
        ]);
    }

    public function test_authenticated_user_can_post_comment_and_count_increments() {
        $user = $this->createVerifiedUser();
        $item = $this->createItem();

        $this->actingAs($user)
            ->from(route('items.show', ['item_id' => $item->id]))
            ->post(route('items.comments.store', [
                'item_id' => $item->id]), ['body' => 'いいですね！',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'body'    => 'いいですね！',
        ]);

        $this->get(route('items.show', ['item_id' => $item->id]))
            ->assertOk()
            ->assertSee('コメント(1件)');
    }

    public function test_guest_cannot_post_comment_and_is_redirected_to_login() {
        $item = $this->createItem();

        $this->post(route('items.comments.store', ['item_id' => $item->id]), [
            'body' => 'ゲスト投稿',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'body'    => 'ゲスト投稿',
        ]);
    }

    public function test_empty_comment_shows_validation_error_and_is_not_saved() {
        $user = $this->createVerifiedUser();
        $item = $this->createItem();

        $response = $this->actingAs($user)
            ->from(route('items.show', ['item_id' => $item->id]))
            ->post(route('items.comments.store', ['item_id' => $item->id]), ['body' => '',]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_over_255_chars_comment_shows_validation_error_and_is_not_saved() {
        $user = $this->createVerifiedUser();
        $item = $this->createItem();

        $tooLong = str_repeat('あ', 256);

        $response = $this->actingAs($user)
            ->from(route('items.show', ['item_id' => $item->id]))
            ->post(route('items.comments.store', ['item_id' => $item->id]), ['body' => $tooLong,]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'user_id' => $user->id,
        ]);
    }
}
