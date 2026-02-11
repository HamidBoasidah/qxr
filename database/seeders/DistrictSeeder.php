<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\District;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $govs = \App\Models\Governorate::all();
        foreach ($govs as $gov) {
            // create a few sample districts per governorate
            for ($i = 1; $i <= 3; $i++) {
                \App\Models\District::firstOrCreate([
                    'governorate_id' => $gov->id,
                    'name_ar' => 'مديرية ' . $gov->name_ar . ' ' . $i,
                ], [
                    'name_en' => $gov->name_en . ' District ' . $i,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]);
            }
        }
    }
}
