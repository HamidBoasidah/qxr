<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            AdminSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            GovernorateSeeder::class,
            DistrictSeeder::class,
            AreaSeeder::class,
            UserSeeder::class,
            FillUserAddressesSeeder::class,
            ProductSeeder::class,
            OfferSeeder::class,
            OrderSeeder::class,
            InvoiceSeeder::class,
            ReturnPolicySeeder::class,
            ReturnInvoiceSeeder::class,
            AdvertisementSeeder::class,
        ]);
    }
}
