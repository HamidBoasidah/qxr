<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use App\Models\Governorate;
use App\Models\District;
use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition()
    {
        $gov = Governorate::inRandomOrder()->first();
        $district = $gov ? District::where('governorate_id', $gov->id)->inRandomOrder()->first() : null;
        $area = $district ? Area::where('district_id', $district->id)->inRandomOrder()->first() : null;

        return [
            'user_id' => User::factory(),
            'label' => $this->faker->randomElement(['home', 'work', 'other']),
            'address' => $this->faker->address,
            'governorate_id' => $gov?->id,
            'district_id' => $district?->id,
            'area_id' => $area?->id,
            'lat' => $this->faker->latitude(),
            'lang' => $this->faker->longitude(),
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
