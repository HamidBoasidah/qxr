<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Address;

class FillUserAddressesSeeder extends Seeder
{
    /**
     * Ensure every existing user has at least one address.
     */
    public function run(): void
    {
        User::withCount('addresses')
            ->chunk(200, function ($users) {
                foreach ($users as $user) {
                    if ($user->addresses_count > 0) {
                        continue;
                    }

                    // Create a single default address for this user using the factory
                    Address::factory()
                        ->for($user)
                        ->state(['is_default' => true])
                        ->create();
                }
            });
    }
}
