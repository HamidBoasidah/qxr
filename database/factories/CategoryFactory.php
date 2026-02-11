<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        $name = $this->faker->unique()->word;
        return [
            // name will be generated in Arabic because faker_locale is set to Arabic
            'name' => $name,
            'slug' => $this->faker->unique()->slug,
            'category_type' => $this->faker->randomElement(['company','customer','product']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
