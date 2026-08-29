<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $owner = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'テストユーザー',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
            );

        if (!$owner->hasVerifiedEmail()) {
            $owner->markEmailAsVerified();
        }

        $items = [
            [
                'product_name' => '腕時計',
                'brand_name' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'price' => 15000,
                'condition' => '良好',
                'image' => 'images/items/watch.jpg',
                'categories' => ['メンズ', 'アクセサリー'],
            ],
            [
                'product_name' => 'HDD',
                'brand_name' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'price' => 5000,
                'condition' => '目立った傷や汚れなし',
                'image' => 'images/items/hdd.jpg',
                'categories' => ['家電'],
            ],
            [
                'product_name' => '玉ねぎ3束',
                'brand_name' => null,
                'description' => '新鮮な玉ねぎ3束のセット',
                'price' => 300,
                'condition' => 'やや傷や汚れあり',
                'image' => 'images/items/onion.jpg',
                'categories' => ['キッチン'],
            ],
            [
                'product_name' => '革靴',
                'brand_name' => null,
                'description' => 'クラシックなデザインの革靴',
                'price' => 4000,
                'condition' => '状態が悪い',
                'image' => 'images/items/shoes.jpg',
                'categories' => ['ファッション', 'メンズ'],
            ],
            [
                'product_name' => 'ノートPC',
                'brand_name' => null,
                'description' => '高性能なノートパソコン',
                'price' => 45000,
                'condition' => '良好',
                'image' => 'images/items/laptop.jpg',
                'categories' => ['家電'],
            ],
            [
                'product_name' => 'マイク',
                'brand_name' => null,
                'description' => '高音質のレコーディング用マイク',
                'price' => 8000,
                'condition' => '目立った傷や汚れなし',
                'image' => 'images/items/mic.jpg',
                'categories' => ['家電'],
            ],
            [
                'product_name' => 'ショルダーバック',
                'brand_name' => null,
                'description' => 'おしゃれなショルダーバック',
                'price' => 3500,
                'condition' => 'やや傷や汚れあり',
                'image' => 'images/items/bag.jpg',
                'categories' => ['ファッション', 'レディース'],
            ],
            [
                'product_name' => 'タンブラー',
                'brand_name' => null,
                'description' => '使いやすいタンブラー',
                'price' => 500,
                'condition' => '状態が悪い',
                'image' => 'images/items/tumbler.jpg',
                'categories' => ['キッチン'],
            ],
            [
                'product_name' => 'コーヒーミル',
                'brand_name' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'price' => 4000,
                'condition' => '良好',
                'image' => 'images/items/mill.jpg',
                'categories' => ['キッチン'],
            ],
            [
                'product_name' => 'メイクセット',
                'brand_name' => null,
                'description' => '便利なメイクアップセット',
                'price' => 2500,
                'condition' => '目立った傷や汚れなし',
                'image' => 'images/items/makeup.jpg',
                'categories' => ['コスメ', 'レディース'],
            ],
        ];

        foreach ($items as $itemData) {
            $item = Item::updateOrCreate([
                'user_id' => $owner->id,
                'product_name' => $itemData['product_name'],
            ], [
                'brand_name' => $itemData['brand_name'],
                'description' => $itemData['description'],
                'price' => $itemData['price'],
                'condition' => $itemData['condition'],
                'image' => $itemData['image'],
            ]);

            $categoryIds = Category::query()
                ->whereIn('name', $itemData['categories'])
                ->get()
                ->unique('name')
                ->pluck('id');

            $item->categories()->sync($categoryIds);
        }
    }
}
