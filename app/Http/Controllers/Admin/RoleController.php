<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RoleService;
use App\Services\PermissionService;
use App\Models\Role;
use Inertia\Inertia;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['create', 'store']);
        $this->middleware('permission:roles.update')->only(['edit', 'update' , 'activate', 'deactivate']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }


    public function index(Request $request, RoleService $roleService)
    {
        // 1) per_page آمن
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        // 2) جلب مع عدّ الصلاحيات (تأكد أن paginate() في الـRepository يعمل withCount('permissions'))
        $roles = $roleService->paginate($perPage);

        // 3) تحويل البيانات لتكون خفيفة ومناسبة للواجهة
        $roles = $roles->through(function ($role) {
            return [
                'id'                => $role->id,
                'name'              => $role->name, // الـslug التقني
                'display_name'      => $role->getTranslations('display_name') ?? [], // لكل اللغات
                'permissions_count' => $role->permissions_count,
                'created_at'        => $role->created_at?->toISOString(),
            ];
        })->withQueryString(); // يحافظ على ?per_page=... وغيرها في روابط التصفح

        return Inertia::render('Admin/Role/Index', [
            'roles'  => $roles,
            'locale' => app()->getLocale(), // مفيد للـFE إن أردت
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Role/Create', [
            'acl' => [
                'resources'       => config('acl.resources'),
                'resource_labels' => config('acl.resource_labels'),
                'action_labels'   => config('acl.action_labels'),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request, RoleService $roleService)
    {
        $data = $request->validated();
        $roleService->create($data);
        return redirect()->route('admin.roles.index');
    }

    
    public function show(Role $role, PermissionService $permissionService)
    {
        $role->load('permissions:id,name');
        return Inertia::render('Admin/Role/Show', [
            'role' => $role,
            'acl' => [
                'resources'       => config('acl.resources'),
                'resource_labels' => config('acl.resource_labels'),
                'action_labels'   => config('acl.action_labels'),
            ],
        ]);
    }

    
    public function edit(Role $role)
    {
        $role->load('permissions:id,name');
        return Inertia::render('Admin/Role/Edit', [
            'role' => $role,
            'acl' => [
                'resources'       => config('acl.resources'),
                'resource_labels' => config('acl.resource_labels'),
                'action_labels'   => config('acl.action_labels'),
            ],
        ]);
    }

    
    public function update(UpdateRoleRequest $request, RoleService $roleService, Role $role)
    {
        $data = $request->validated();
        $roleService->update($role->id, $data);

        return redirect()->route('admin.roles.index');
    }

    
    public function destroy(RoleService $roleService, Role $role)
    {
        $roleService->delete($role->id);
        return redirect()->route('admin.roles.index');
    }
}
