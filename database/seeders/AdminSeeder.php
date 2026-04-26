<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin'],
            ['display_name' => ['en' => 'Admin', 'ar' => 'مشرف']]
        );

        $admins = [
            [
                'first_name' => 'مدير',
                'last_name' => 'النظام',
                'email' => 'admin@quick-xr.com',
                'phone_number' => '770000001',
                'whatsapp_number' => '770000001',
                'role' => $superAdminRole ?? $adminRole,
            ],
            [
                'first_name' => 'سمر',
                'last_name' => 'إسماعيل',
                'email' => 'samar-esmael@quick-xr.com',
                'phone_number' => '770000002',
                'whatsapp_number' => '770000002',
                'role' => $adminRole,
            ],
            [
                'first_name' => 'شركة الجبل',
                'last_name' => 'للأدوية',
                'email' => 'aljabal@quick-xr.com',
                'phone_number' => '770000003',
                'whatsapp_number' => '770000003',
                'role' => $adminRole,
            ],
        ];

        foreach ($admins as $data) {
            $role = $data['role'];
            unset($data['role']);

            $admin = Admin::where('email', $data['email'])->first();
            if (! $admin) {
                $admin = Admin::create(array_merge($data, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'locale' => 'ar',
                ]));
            }

            $admin->syncRoles([$role]);
        }
    }
}
