<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        if (\App\Models\District::count() > 0) {
            Area::factory(20)->create();
        }
    }
}
