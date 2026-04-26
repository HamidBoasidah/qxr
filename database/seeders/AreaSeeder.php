<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'مديرية التحرير' => [
                ['ar' => 'حي التحرير', 'en' => 'Al Tahrir'],
                ['ar' => 'شارع الزبيري', 'en' => 'Al Zubairi St'],
            ],
            'مديرية معين' => [
                ['ar' => 'حي الأصبحي', 'en' => 'Al Asbahi'],
                ['ar' => 'حي حدة', 'en' => 'Hadda'],
            ],
            'مديرية الصافية' => [
                ['ar' => 'حي الصافية', 'en' => 'Al Safiyah'],
                ['ar' => 'شارع تعز', 'en' => 'Taiz St'],
            ],
            'مديرية السبعين' => [
                ['ar' => 'حي السبعين', 'en' => 'Al Sabeen'],
                ['ar' => 'شارع الستين', 'en' => 'Sixty St'],
            ],
            'مديرية كريتر' => [
                ['ar' => 'حي كريتر', 'en' => 'Crater'],
                ['ar' => 'حي صيرة', 'en' => 'Sirah'],
            ],
            'مديرية خور مكسر' => [
                ['ar' => 'حي خور مكسر', 'en' => 'Khor Maksar'],
            ],
            'مديرية القاهرة' => [
                ['ar' => 'حي القاهرة', 'en' => 'Al Qahirah'],
                ['ar' => 'حي الجمهوري', 'en' => 'Al Jumhuri'],
            ],
            'مديرية الحالي' => [
                ['ar' => 'حي الحالي', 'en' => 'Al Hali'],
            ],
        ];

        foreach ($areas as $distName => $areaList) {
            $district = \App\Models\District::where('name_ar', $distName)->first();
            if (!$district) continue;

            foreach ($areaList as $a) {
                \App\Models\Area::firstOrCreate(
                    ['district_id' => $district->id, 'name_ar' => $a['ar']],
                    ['name_en' => $a['en'], 'is_active' => true, 'created_by' => null, 'updated_by' => null]
                );
            }
        }

        // باقي المديريات - حي افتراضي
        $districts = \App\Models\District::whereDoesntHave('areas')->get();
        foreach ($districts as $district) {
            \App\Models\Area::firstOrCreate(
                ['district_id' => $district->id, 'name_ar' => 'حي ' . $district->name_ar],
                ['name_en' => $district->name_en . ' Area', 'is_active' => true, 'created_by' => null, 'updated_by' => null]
            );
        }
    }
}
