<?php
namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition()
    {
        $district = \App\Models\District::inRandomOrder()->first();
        $districtId = $district?->id ?: \App\Models\District::factory()->create()->id;

        $arabicArea = 'حي ' . ($this->faker->unique()->word ?? $this->faker->unique()->streetName);

        return [
            'name_ar' => $arabicArea,
            'name_en' => $this->faker->unique()->word,
            'is_active' => true,
            'district_id' => $districtId,
            'created_by' => \App\Models\User::inRandomOrder()->first()?->id,
            'updated_by' => \App\Models\User::inRandomOrder()->first()?->id,
        ];
    }
}
