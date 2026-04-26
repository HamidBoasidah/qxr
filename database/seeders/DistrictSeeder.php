<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            'صنعاء' => [
                ['ar' => 'مديرية التحرير', 'en' => 'Al Tahrir'],
                ['ar' => 'مديرية معين', 'en' => 'Maeen'],
                ['ar' => 'مديرية الصافية', 'en' => 'Al Safiyah'],
                ['ar' => 'مديرية شعوب', 'en' => 'Shuaub'],
                ['ar' => 'مديرية السبعين', 'en' => 'Al Sabeen'],
            ],
            'عدن' => [
                ['ar' => 'مديرية كريتر', 'en' => 'Crater'],
                ['ar' => 'مديرية المعلا', 'en' => 'Al Mualla'],
                ['ar' => 'مديرية خور مكسر', 'en' => 'Khor Maksar'],
                ['ar' => 'مديرية المنصورة', 'en' => 'Al Mansoura'],
            ],
            'تعز' => [
                ['ar' => 'مديرية القاهرة', 'en' => 'Al Qahirah'],
                ['ar' => 'مديرية المظفر', 'en' => 'Al Mudhaffar'],
                ['ar' => 'مديرية صالة', 'en' => 'Salah'],
            ],
            'الحديدة' => [
                ['ar' => 'مديرية الحالي', 'en' => 'Al Hali'],
                ['ar' => 'مديرية الميناء', 'en' => 'Al Mina'],
            ],
            'إب' => [
                ['ar' => 'مديرية الظهار', 'en' => 'Al Dhahar'],
                ['ar' => 'مديرية المشنة', 'en' => 'Al Mashnah'],
            ],
            'ذمار' => [
                ['ar' => 'مديرية ذمار المدينة', 'en' => 'Dhamar City'],
                ['ar' => 'مديرية جهران', 'en' => 'Jahran'],
            ],
            'مأرب' => [
                ['ar' => 'مديرية مأرب المدينة', 'en' => 'Marib City'],
            ],
        ];

        foreach ($districts as $govName => $dists) {
            $gov = \App\Models\Governorate::where('name_ar', $govName)->first();
            if (!$gov) continue;

            foreach ($dists as $d) {
                \App\Models\District::firstOrCreate(
                    ['governorate_id' => $gov->id, 'name_ar' => $d['ar']],
                    ['name_en' => $d['en'], 'is_active' => true, 'created_by' => null, 'updated_by' => null]
                );
            }
        }

        // باقي المحافظات - مديرية افتراضية
        $govs = \App\Models\Governorate::whereDoesntHave('districts')->get();
        foreach ($govs as $gov) {
            \App\Models\District::firstOrCreate(
                ['governorate_id' => $gov->id, 'name_ar' => 'مديرية ' . $gov->name_ar],
                ['name_en' => $gov->name_en . ' District', 'is_active' => true, 'created_by' => null, 'updated_by' => null]
            );
        }
    }
}
