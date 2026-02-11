<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            GovernorateSeeder::class,
            DistrictSeeder::class,
            AreaSeeder::class,
            FillUserAddressesSeeder::class,
            ProductSeeder::class,
        ]);

        // seed categories and tags with Arabic data (do not rely on factories)
        $this->call([CategorySeeder::class, TagSeeder::class]);
    }
}
