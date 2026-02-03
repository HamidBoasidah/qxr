<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'slug' => $this->faker->unique()->slug,
            'category_type' => $this->faker->randomElement(['company','customer','product']),
            'is_active' => $this->faker->boolean(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
