<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $districts = \App\Models\District::all();
        foreach ($districts as $district) {
            for ($i = 1; $i <= 2; $i++) {
                \App\Models\Area::firstOrCreate([
                    'district_id' => $district->id,
                    'name_ar' => 'حي ' . $district->name_ar . ' ' . $i,
                ], [
                    'name_en' => $district->name_en . ' Area ' . $i,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]);
            }
        }
    }
}
