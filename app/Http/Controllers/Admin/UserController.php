<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateUserRequest;
use Inertia\Inertia;
use App\Services\UserService;
use App\Models\User;
use App\Models\Role;
use App\DTOs\UserDTO;
use App\Http\Requests\StoreUserRequest;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.update')->only(['edit', 'update' , 'activate', 'deactivate']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index(Request $request, UserService $userService)
    {
        $perPage = $request->input('per_page', 10);
        $users = $userService->paginate($perPage);
        // تجهيز بيانات كل مستخدم بنفس منطق show
        $users->getCollection()->transform(function ($user) {
            return UserDTO::fromModel($user)->toIndexArray();
        });
        return Inertia::render('Admin/User/Index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        $roles = Role::all();

        return Inertia::render('Admin/User/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request, UserService $userService)
    {
        $data = $request->validated();

        // إذا وُجد ملف مرفق نُمرره ضمن البيانات ليتولى المستودع التعامل معه
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment');
        }

        $userService->create($data);

        return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        $userDTO = UserDTO::fromModel($user)->toArray();
        return Inertia::render('Admin/User/Show', [
            'user' => $userDTO,
        ]);
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        $userDTO = UserDTO::fromModel($user)->toArray();

        return Inertia::render('Admin/User/Edit', [
            'user' => $userDTO,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, UserService $userService , User $user)
    {
        $data = $request->validated();

        // إذا وُجد ملف مرفق نُمرره ضمن البيانات ليتولى المستودع التعامل معه
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment');
        }

        $userService->update($user->id , $data);

        return redirect()->route('admin.users.index');
    }

    public function destroy(UserService $userService, User $user)
    {
        $userService->delete($user->id);

        return redirect()->route('admin.users.index');
    }

    public function activate(UserService $userService, $id)
    {
        $userService->activate($id);
        return back()->with('success', 'User activated successfully');
    }

    public function deactivate(UserService $userService, $id)
    {
        $userService->deactivate($id);
        return back()->with('success', 'User deactivated successfully');
    }

}