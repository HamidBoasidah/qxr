<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    protected function json(array $value): string
    {
        // JSON بدون ترميز Unicode أو الشرطات المائلة
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () {
            $guard          = config('acl.guard', 'web');

            // الموارد الموافِقة للتعديلات الأخيرة فقط
            $resources      = config('acl.resources', []);
            $resourceLabels = config('acl.resource_labels', []);
            $actionLabels   = config('acl.action_labels', [
                'view'   => ['en' => 'View',   'ar' => 'عرض'],
                'create' => ['en' => 'Create', 'ar' => 'إنشاء'],
                'update' => ['en' => 'Update', 'ar' => 'تعديل'],
                'delete' => ['en' => 'Delete', 'ar' => 'حذف'],
            ]);

            // 1) توليد الأذونات المفردة resource.action فقط
            foreach ($resources as $resource => $actions) {
                $resourceEn = $resourceLabels[$resource]['en']
                    ?? (string) Str::of($resource)->replace('-', ' ')->headline();
                $resourceAr = $resourceLabels[$resource]['ar'] ?? $resource;

                foreach ($actions as $action) {
                    $actEn = $actionLabels[$action]['en'] ?? ucfirst($action);
                    $actAr = $actionLabels[$action]['ar'] ?? $action;

                    DB::table((new Permission)->getTable())->updateOrInsert(
                        ['name' => "{$resource}.{$action}", 'guard_name' => $guard],
                        ['display_name' => $this->json([
                            'en' => "{$actEn} {$resourceEn}",
                            'ar' => "{$actAr} {$resourceAr}",
                        ])]
                    );
                }
            }

            // مُساعد لتجميع أسماء الأذونات
            $permNames = function (array $resList, array $actionsWanted) use ($resources) {
                return collect($resList)->flatMap(function ($res) use ($resources, $actionsWanted) {
                    $allowed = $resources[$res] ?? [];
                    return collect($actionsWanted)
                        ->filter(fn ($a) => in_array($a, $allowed, true))
                        ->map(fn ($a) => "{$res}.{$a}");
                })->values();
            };

            $allActionPerms = collect($resources)
                ->flatMap(fn ($acts, $res) => collect($acts)->map(fn ($a) => "{$res}.{$a}"))
                ->values();

            $allViewPerms = collect($resources)
                ->filter(fn ($acts) => in_array('view', $acts, true))
                ->keys()
                ->map(fn ($res) => "{$res}.view")
                ->values();

            $allNonDeletePerms = $allActionPerms
                ->reject(fn ($name) => Str::endsWith($name, '.delete'))
                ->values();

            // مجموعات منطقية للموارد الحالية
            $geoResources   = ['areas', 'districts', 'governorates'];
            $adminEntities  = ['users', 'roles', 'permissions'];

            // 2) الأدوار الافتراضية (6 أدوار) — منح أذونات صريحة فقط

            // (أ) Super Admin: كل شيء
            $super = Role::updateOrCreate(
                ['name' => 'super-admin', 'guard_name' => $guard],
                ['display_name' => ['en' => 'Super Admin', 'ar' => 'مدير النظام']]
            );
            $super->syncPermissions($allActionPerms->all());

            // (ب) Admin: كل شيء ما عدا الحذف
            $admin = Role::updateOrCreate(
                ['name' => 'admin', 'guard_name' => $guard],
                ['display_name' => ['en' => 'Admin', 'ar' => 'مشرف']]
            );
            $admin->syncPermissions($allNonDeletePerms->all());

            // (ج) Manager: (view+create+update) للموارد الجغرافية، و(users/roles/permissions) مشاهدة فقط
            $manager = Role::updateOrCreate(
                ['name' => 'manager', 'guard_name' => $guard],
                ['display_name' => ['en' => 'Manager', 'ar' => 'مدير']]
            );
            $managerPerms = collect()
                ->merge($permNames($geoResources, ['view', 'create', 'update']))
                ->merge($permNames($adminEntities, ['view']))
                ->unique()
                ->values();
            $manager->syncPermissions($managerPerms->all());

            // (د) Editor: (view+update) للموارد الجغرافية
            $editor = Role::updateOrCreate(
                ['name' => 'editor', 'guard_name' => $guard],
                ['display_name' => ['en' => 'Editor', 'ar' => 'محرر']]
            );
            $editorPerms = $permNames($geoResources, ['view', 'update']);
            $editor->syncPermissions($editorPerms->all());

            // (هـ) Data Entry: (view+create) للموارد الجغرافية
            $dataEntry = Role::updateOrCreate(
                ['name' => 'data-entry', 'guard_name' => $guard],
                ['display_name' => ['en' => 'Data Entry', 'ar' => 'مدخل بيانات']]
            );
            $dataEntryPerms = $permNames($geoResources, ['view', 'create']);
            $dataEntry->syncPermissions($dataEntryPerms->all());

            // (و) Viewer: مشاهدة فقط لكل الموارد التي تدعم "view"
            $viewer = Role::updateOrCreate(
                ['name' => 'viewer', 'guard_name' => $guard],
                ['display_name' => ['en' => 'Viewer', 'ar' => 'مشاهد']]
            );
            $viewer->syncPermissions($allViewPerms->all());
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
