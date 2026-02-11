<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Governorate;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $govs = [
            ['ar' => 'صنعاء', 'en' => "Sana'a"],
            ['ar' => 'عدن', 'en' => 'Aden'],
            ['ar' => 'تعز', 'en' => 'Taiz'],
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

        foreach ($govs as $g) {
            \App\Models\Governorate::firstOrCreate([
                'name_ar' => $g['ar'],
            ], [
                'name_en' => $g['en'],
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]);
        }
    }
}
