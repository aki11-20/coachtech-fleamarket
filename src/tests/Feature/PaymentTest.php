<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;

class PaymentTest extends TestCase
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

    public function test_payment_selection_updates_summary_with_card() {
        $buyer = $this->createVerifiedUser([
            'email' => 'buyer1@example.com',
        ]);
        $item = $this->createItem();

        $this->actingAs($buyer, 'web')
        ->get(route('purchase.show', ['item_id' => $item->id]))
        ->assertOk();

        $response = $this->actingAs($buyer, 'web')
        ->post(route('purchase.payment.update', ['item_id' => $item->id]), [
            'payment_type' => 'card',
        ]);

        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        $this->actingAs($buyer, 'web')
        ->get(route('purchase.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('カード支払い');
    }

    public function test_payment_selection_updates_summary_with_convenience() {
        $buyer = $this->createVerifiedUser([
            'email' => 'buyer2@example.com',
        ]);
        $item = $this->createItem();

        $response = $this->actingAs($buyer, 'web')
        ->post(route('purchase.payment.update', ['item_id' => $item->id]), [
            'payment_type' => 'convenience',
        ]);
        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        $this->actingAs($buyer, 'web')
        ->get(route('purchase.show', ['item_id' => $item->id]))
        ->assertOk()
        ->assertSee('コンビニ支払い');
    }

    public function test_invalid_payment_selection_shows_validation_error_and_keeps_unselected() {
        $buyer = $this->createVerifiedUser([
            'email' => 'buyer3@example.com',
        ]);
        $item = $this->createItem();

        $response = $this->actingAs($buyer, 'web')
        ->from(route('purchase.show', ['item_id' => $item->id]))
        ->post(route('purchase.payment.update', ['item_id' => $item->id]), [
            'payment_type' => 'bitcoin',
        ]);

        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));
        $response->assertSessionHasErrors('payment_type');
    }
}
