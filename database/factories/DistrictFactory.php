<?php
namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition()
    {
        return [
            'name_ar' => $this->faker->citySuffix . ' عربي',
            'name_en' => $this->faker->city,
            'is_active' => true,
            'governorate_id' => 1, // يفضل ضبطه ديناميكياً لاحقاً
            'created_by' => \App\Models\User::inRandomOrder()->first()?->id,
            'updated_by' => \App\Models\User::inRandomOrder()->first()?->id,
        ];
    }
}
