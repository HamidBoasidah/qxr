<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition()
    {
        $name = $this->faker->unique()->word; // generated in Arabic locale
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'tag_type' => $this->faker->randomElement(['company','customer','product']),
            'is_active' => $this->faker->boolean(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
