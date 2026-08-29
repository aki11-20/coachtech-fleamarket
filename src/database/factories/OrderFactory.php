<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'item_id' => Item::factory(),
            'payment_type' => 'card',
            'status' => Order::STATUS_PAID,
            'stripe_checkout_session_id' => null,
            'reserved_until' => null,
            'paid_at' => now(),
            'cancelled_at' => null,
            'postal_code' => '123-4567',
            'address' => $this->faker->address,
            'building' => null,
        ];
    }

    public function paid()
    {
        return $this->state(function () {
            return [
                'status' => Order::STATUS_PAID,
                'reserved_until' => null,
                'paid_at' => now(),
                'cancelled_at' => null,
            ];
        });
    }

    public function pending()
    {
        return $this->state(function () {
            return [
                'status' => Order::STATUS_PENDING,
                'reserved_until' => now()->addHour(),
                'paid_at' => null,
                'cancelled_at' => null,
            ];
        });
    }

    public function cancelled()
    {
        return $this->state(function () {
            return [
                'status' => Order::STATUS_CANCELLED,
                'reserved_until' => null,
                'paid_at' => null,
                'cancelled_at' => now(),
            ];
        });
    }
}
