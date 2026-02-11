<?php
namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition()
    {
        $gov = \App\Models\Governorate::inRandomOrder()->first();
        $govId = $gov?->id ?: \App\Models\Governorate::factory()->create()->id;

        // Generate a district-like Arabic name using common prefix
        $arabicName = 'مديرية ' . ($this->faker->unique()->word ?? $this->faker->unique()->city);

        return [
            'name_ar' => $arabicName,
            'name_en' => $this->faker->unique()->word,
            'is_active' => true,
            'governorate_id' => $govId,
            'created_by' => \App\Models\User::inRandomOrder()->first()?->id,
            'updated_by' => \App\Models\User::inRandomOrder()->first()?->id,
        ];
    }
}
