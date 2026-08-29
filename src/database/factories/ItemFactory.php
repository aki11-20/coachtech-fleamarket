<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'product_name' => $this->faker->word,
            'brand_name' => $this->faker->company,
            'description' => $this->faker->sentence,
            'condition' => '良好',
            'price' => $this->faker->numberBetween(100, 100000),
            'image' => 'images/sample.jpg',
        ];
    }
}
