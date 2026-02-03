<?php
namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition()
    {
        return [
            'name_ar' => $this->faker->citySuffix . ' عربي',
            'name_en' => $this->faker->city,
            'is_active' => true,
            'district_id' => \App\Models\District::inRandomOrder()->first()?->id ?? 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
