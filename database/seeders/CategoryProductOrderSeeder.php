<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategoryProductOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = Category::factory(5)->create();

        // Create products for each category
        $products = Product::factory(20)
            ->recycle($categories)
            ->create();

        // Create some users
        $users = User::factory(10)->create();

        // Create orders with products
        Order::factory(15)
            ->recycle($users)
            ->create()
            ->each(function (Order $order) use ($products): void {
                $orderProducts = $products->random(rand(1, 5));

                $order->products()->attach(
                    $orderProducts->mapWithKeys(function (Product $product): array {
                        return [
                            $product->id => [
                                'quantity' => rand(1, 3),
                                'price' => $product->price,
                            ],
                        ];
                    })
                );
            });
    }
}
