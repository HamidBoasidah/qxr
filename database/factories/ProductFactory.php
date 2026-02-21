<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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
            // use Arabic unit names for Yemeni/Arabic fake data
            'unit_name' => $this->faker->randomElement(['حبة', 'صندوق', 'عبوة', 'زجاجة', 'كرتون']),
            'base_price' => $this->faker->randomFloat(2, 1, 1000),
            'is_active' => $this->faker->boolean(85),
            'main_image' => null,
        ];
    }

    /**
     * Configure the factory to handle attribute mapping
     */
    public function configure()
    {
        return $this->afterMaking(function (Product $product) {
            // Already handled in raw() and make()
        })->afterCreating(function (Product $product) {
            // Already persisted
        });
    }

    /**
     * Override the raw method to map company_id to company_user_id
     */
    public function raw($attributes = [], ?Model $parent = null)
    {
        // Map company_id to company_user_id for backward compatibility
        if (array_key_exists('company_id', $attributes)) {
            if (!array_key_exists('company_user_id', $attributes)) {
                $attributes['company_user_id'] = $attributes['company_id'];
            }
            unset($attributes['company_id']);
        }
        
        return parent::raw($attributes, $parent);
    }

    /**
     * Override the make method to handle attribute mapping
     */
    public function make($attributes = [], ?Model $parent = null)
    {
        // Map company_id to company_user_id for backward compatibility
        if (array_key_exists('company_id', $attributes)) {
            if (!array_key_exists('company_user_id', $attributes)) {
                $attributes['company_user_id'] = $attributes['company_id'];
            }
            unset($attributes['company_id']);
        }
        
        return parent::make($attributes, $parent);
    }
}