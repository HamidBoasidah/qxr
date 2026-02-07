<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $categoryId = Category::where('category_type', 'product')->inRandomOrder()->value('id')
            ?: Category::factory()->create(['category_type' => 'product'])->id;

        $companyUserId = User::where('user_type', 'company')->inRandomOrder()->value('id')
            ?: User::factory()->create(['user_type' => 'company'])->id;

        return [
            'company_user_id' => $companyUserId,
            'category_id' => $categoryId,
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('SKU-#####')),
            'description' => $this->faker->paragraph(),
            'unit_name' => $this->faker->randomElement(['piece', 'box', 'pack', 'bottle', 'carton']),
            'base_price' => $this->faker->randomFloat(2, 1, 1000),
            'is_active' => $this->faker->boolean(85),
            'main_image' => null,
        ];
    }
}
