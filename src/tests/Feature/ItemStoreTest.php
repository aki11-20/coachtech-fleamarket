<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ItemStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createVerifiedUser(array $overrides = []): User {
        $user = User::create(array_merge([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));
        $user->markEmailAsVerified();
        return $user;
    }

    public function test_it_saves_item_and_attaches_multiple_categories_into_pivot() {
        $user = $this->createVerifiedUser();

        $catA = Category::create(['name' => '家電']);
        $catB = Category::create(['name' => 'メンズ']);
        $catC = Category::create(['name' => 'レディース']);

        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->create('earphones.jpg', 200, 'image/jpeg');

        $payload = [
            'product_name' => 'ワイヤレスイヤホン',
            'brand_name' => 'Sony',
            'description' => 'ノイズキャンセリング',
            'condition' => '良好',
            'price' => 12800,
            'image' => $fakeImage,
            'categories' => [$catA->id, $catB->id, $catC->id],
        ];

        $response = $this->actingAs($user, 'web')
            ->from(route('items.create'))
            ->post(route('items.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $item = Item::latest('id')->first();
        $this->assertNotNull($item, 'Item was not created');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'user_id' => $user->id,
            'product_name' => 'ワイヤレスイヤホン',
            'brand_name' => 'Sony',
            'description' => 'ノイズキャンセリング',
            'condition' => '良好',
            'price' => 12800,
        ]);

        $this->assertNotEmpty($item->image, 'Image path not saved');

        $this->assertDatabaseHas('category_item', [
            'item_id' => $item->id,
            'category_id' => $catA->id,
        ]);
        $this->assertDatabaseHas('category_item', [
            'item_id' => $item->id,
            'category_id' => $catB->id,
        ]);
        $this->assertDatabaseHas('category_item', [
            'item_id' => $item->id,
            'category_id' => $catC->id,
        ]);
    }

    public function test_it_fails_validation_when_required_fields_missing() {
        $user = $this->createVerifiedUser();

        Storage::fake('public');

        $bad = [
            'product_name' => '',
            'brand_name' => 'Sony',
            'description' => str_repeat('あ', 256),
            'condition' => '',
            'price' => null,
        ];

        $response = $this->actingAs($user, 'web')
        ->from(route('items.create'))
        ->post(route('items.store'), $bad);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'product_name',
            'description',
            'image',
            'categories',
            'condition',
            'price',
        ]);
    }

    public function test_it_saves_when_all_required_fields_are_present_even_with_single_category() {
        $user = $this->createVerifiedUser();

        $cat = Category::create(['name' => '家電']);

        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->create('p.jpg', 100, 'image/jpeg');

        $payload = [
            'product_name' => 'シンプル商品',
            'brand_name' => 'NoBrand',
            'description' => '説明文',
            'condition' => '良好',
            'price' => 3000,
            'image' => $fakeImage,
            'categories'   => [$cat->id],
        ];

        $response = $this->actingAs($user, 'web')
            ->post(route('items.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $item = Item::latest('id')->first();
        $this->assertNotNull($item);

        $this->assertDatabaseHas('items', [
            'id'           => $item->id,
            'product_name' => 'シンプル商品',
            'brand_name'   => 'NoBrand',
            'description'  => '説明文',
            'condition'    => '良好',
            'price'        => 3000,
        ]);

        $this->assertDatabaseHas('category_item', [
            'item_id'     => $item->id,
            'category_id' => $cat->id,
        ]);
    }
}
