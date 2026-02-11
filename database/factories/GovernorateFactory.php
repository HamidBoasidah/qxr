<?php
namespace Database\Factories;

use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition()
    {
        $govs = [
            ['ar' => 'صنعاء', 'en' => "Sana'a"],
            ['ar' => 'عدن', 'en' => 'Aden'],
            ['ar' => 'تعز', 'en' => "Taiz"],
            ['ar' => 'الحديدة', 'en' => 'Al Hudaydah'],
            ['ar' => 'حضرموت', 'en' => 'Hadhramaut'],
            ['ar' => 'إب', 'en' => 'Ibb'],
            ['ar' => 'ذمار', 'en' => 'Dhamar'],
            ['ar' => 'المهرة', 'en' => 'Al Mahrah'],
            ['ar' => 'الجوف', 'en' => 'Al Jawf'],
            ['ar' => 'عمران', 'en' => 'Amran'],
            ['ar' => 'أبين', 'en' => 'Abyan'],
            ['ar' => 'لحج', 'en' => 'Lahij'],
            ['ar' => 'مأرب', 'en' => 'Marib'],
            ['ar' => 'ريمة', 'en' => 'Raymah'],
            ['ar' => 'صعدة', 'en' => 'Saada'],
            ['ar' => 'البيضاء', 'en' => 'Al Bayda'],
            ['ar' => 'شبوة', 'en' => 'Shabwah'],
            ['ar' => 'سقطرى', 'en' => 'Socotra'],
            ['ar' => 'المحويت', 'en' => 'Al Mahwit'],
        ];

        $pick = $this->faker->randomElement($govs);

        return [
            'name_ar' => $pick['ar'],
            'name_en' => $pick['en'],
            'is_active' => true,
            'created_by' => \App\Models\User::inRandomOrder()->first()?->id,
            'updated_by' => \App\Models\User::inRandomOrder()->first()?->id,
        ];
    }
}
