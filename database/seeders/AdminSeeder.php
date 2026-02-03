<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there's at least one admin role (guard = admin)
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin'],
            ['display_name' => ['en' => 'Admin', 'ar' => 'مشرف']]
        );

        // Create a main admin account if not exists
        $mainEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $main = Admin::where('email', $mainEmail)->first();
        if (! $main) {
            $main = Admin::factory()->create([
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => $mainEmail,
                'is_active' => true,
            ]);
        }

        // Always ensure the first/main admin has role ID = 1 when available
        $roleIdOne = Role::find(1);
        if ($roleIdOne) {
            $main->syncRoles([$roleIdOne]);
        } else {
            // Fallback to the default admin role name
            $main->syncRoles([$adminRole]);
        }

        // Create a few random admins
        Admin::factory(5)->create();
    }
}
