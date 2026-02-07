<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are product categories
        if (Category::where('category_type', 'product')->count() < 5) {
            Category::factory()->count(10)->create(['category_type' => 'product']);
        }

        // Ensure there are company users
        if (User::where('user_type', 'company')->count() < 5) {
            User::factory()->count(5)->create(['user_type' => 'company']);
        }

        // Create products
        Product::factory()->count(50)->create();
    }
}
