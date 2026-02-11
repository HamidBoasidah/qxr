<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Services\UserService;
use App\Models\User;
use App\Models\Address;
use App\Models\Category;

use App\DTOs\UserDTO;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.update')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index(Request $request, UserService $userService)
    {
        $perPage = (int) $request->input('per_page', 10);

        $users = $userService->paginate($perPage);

        // تجهيز بيانات كل مستخدم بنفس منطق show
        $users->getCollection()->transform(function ($user) {
            return UserDTO::fromModel($user)->toIndexArray();
        });

        return Inertia::render('Company/User/Index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        $categories = Category::whereIn('category_type', ['company', 'customer'])->where('is_active', true)->get();

        return Inertia::render('Company/User/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreUserRequest $request, UserService $userService)
    {
        $data = $request->validated();

        // ✅ إصلاح: اسم الحقل الصحيح هو avatar وليس attachment
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        // ✅ من الأفضل تعبئتها من السيرفر (لو كنت تستخدمها)
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $userService->create($data);

        return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        $userDTO = UserDTO::fromModel($user)->toArray();

        return Inertia::render('Company/User/Show', [
            'user' => $userDTO,
        ]);
    }

    public function edit(User $user)
    {
        $userDTO = UserDTO::fromModel($user)->toArray();

        $categories = Category::whereIn('category_type', ['company', 'customer'])->where('is_active', true)->get();

        return Inertia::render('Company/User/Edit', [
            'user' => $userDTO,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateUserRequest $request, UserService $userService, User $user)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        // ✅ تحديث تلقائي
        $data['updated_by'] = Auth::id();

        $userService->update($user->id, $data);

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