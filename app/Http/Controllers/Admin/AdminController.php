<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Role;
use App\Models\Admin;
use App\DTOs\AdminDTO;

class AdminController extends Controller
{
    protected AdminService $service;

    public function __construct(AdminService $service)
    {
        $this->service = $service;
        $this->middleware('permission:admins.view')->only(['index', 'show']);
        $this->middleware('permission:admins.create')->only(['create', 'store']);
        $this->middleware('permission:admins.update')->only(['edit', 'update' , 'activate', 'deactivate']);
        $this->middleware('permission:admins.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $admins = $this->service->paginate($perPage);

        $admins->getCollection()->transform(function ($admin) {
            return AdminDTO::fromModel($admin)->toIndexArray();
        });

        return Inertia::render('Admin/Admin/Index', [
            'admins' => $admins,
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        return Inertia::render('Admin/Admin/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreAdminRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $this->service->create($data);

        return redirect()->route('admin.admins.index');
    }

    public function edit(Admin $admin)
    {
        $roles = Role::all();
        $adminDTO = AdminDTO::fromModel($admin)->toArray();

        return Inertia::render('Admin/Admin/Edit', [
            'admin' => $adminDTO,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateAdminRequest $request, Admin $admin)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $this->service->update($admin->id, $data);

        return redirect()->route('admin.admins.index');
    }

    public function show(Admin $admin)
    {
        $adminDTO = AdminDTO::fromModel($admin)->toArray();

        return Inertia::render('Admin/Admin/Show', [
            'admin' => $adminDTO,
        ]);
    }

    public function destroy(Admin $admin)
    {
        $this->service->delete($admin->id);
        return redirect()->route('admin.admins.index');
    }

    public function activate(Admin $admin)
    {
        $this->service->activate($admin->id);
        return redirect()->back();
    }

    public function deactivate(Admin $admin)
    {
        $this->service->deactivate($admin->id);
        return redirect()->back();
    }
}
