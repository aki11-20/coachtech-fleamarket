<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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

            $conditionMap = [
                '良好' => 'new',
                '目立った傷や汚れなし' => 'used',
                'やや傷や汚れあり' => 'used',
                '状態が悪い' => 'used',
            ];

        $items = [
            [
                'product_name' => '腕時計',
                'brand_name' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'price' => 15000,
                'condition' => '良好',
                'image' => '/storage/items/watch.jpg'
            ],
            [
                'product_name' => 'HDD',
                'brand_name' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'price' => 5000,
                'condition' => '目立った傷や汚れなし',
                'image' => '/storage/items/hdd.jpg'
            ],
            [
                'product_name' => '玉ねぎ3束',
                'brand_name' => null,
                'description' => '新鮮な玉ねぎ3束のセット',
                'price' => 300,
                'condition' => 'やや傷や汚れあり',
                'image' => '/storage/items/onion.jpg'
            ],
            [
                'product_name' => '革靴',
                'brand_name' => null,
                'description' => 'クラシックなデザインの革靴',
                'price' => 4000,
                'condition' => '状態が悪い',
                'image' => '/storage/items/shoes.jpg'
            ],
            [
                'product_name' => 'ノートPC',
                'brand_name' => null,
                'description' => '高性能なノートパソコン',
                'price' => 45000,
                'condition' => '良好',
                'image' => '/storage/items/laptop.jpg'
            ],
            [
                'product_name' => 'マイク',
                'brand_name' => null,
                'description' => '高音質のレコーディング用マイク',
                'price' => 8000,
                'condition' => '目立った傷や汚れなし',
                'image' => '/storage/items/mic.jpg'
            ],
            [
                'product_name' => 'ショルダーバック',
                'brand_name' => null,
                'description' => 'おしゃれなショルダーバック',
                'price' => 3500,
                'condition' => 'やや傷や汚れあり',
                'image' => '/storage/items/bag.jpg'
            ],
            [
                'product_name' => 'タンブラー',
                'brand_name' => null,
                'description' => '使いやすいタンブラー',
                'price' => 500,
                'condition' => '状態が悪い',
                'image' => '/storage/items/tumbler.jpg'
            ],
            [
                'product_name' => 'コーヒーミル',
                'brand_name' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'price' => 4000,
                'condition' => '良好',
                'image' => '/storage/items/mill.jpg'
            ],
            [
                'product_name' => 'メイクセット',
                'brand_name' => null,
                'description' => '便利なメイクアップセット',
                'price' => 2500,
                'condition' => '目立った傷や汚れなし',
                'image' => '/storage/items/makeup.jpg'
            ],
        ];

        foreach ($items as $item) {
            Item::create([
                'user_id' => $owner->id,
                'product_name' => $item['product_name'],
                'brand_name' => $item['brand_name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'condition' => $item['condition'],
                'image' => $item['image'],
            ]);
        }
    }
}
