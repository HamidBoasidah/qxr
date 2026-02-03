<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\DTOs\AdminDTO;
use App\DTOs\UserDTO;

class ProfileController extends Controller
{

    /**
     * Display the authenticated user's profile (Admin or User).
     */
    public function show(Request $request)
    {
        // التحقق من نوع المستخدم المسجل
        if ($request->user('admin')) {
            // المستخدم مشرف
            $admin = $request->user('admin');
            $adminDTO = AdminDTO::fromModel($admin)->toArray();
            
            return Inertia::render('Admin/Profile/UserProfile', [
                'user' => $adminDTO,
            ]);
        } 
        elseif ($request->user('web')) {
            // المستخدم عادي
            $user = $request->user('web');
            $userDTO = UserDTO::fromModel($user)->toArray();
            
            return Inertia::render('Admin/Profile/UserProfile', [
                'user' => $userDTO,
            ]);
        }
        else {
            // لا يوجد مستخدم مسجل
            return redirect()->route('admin.login');
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        $validated = $request->validated();

        // التحقق من نوع المستخدم المسجل وتحديث البيانات
        if ($request->user('admin')) {
            $admin = $request->user('admin');
            
            // التعامل مع رفع الصورة
            if ($request->hasFile('avatar')) {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($admin->avatar && Storage::disk('public')->exists($admin->avatar)) {
                    Storage::disk('public')->delete($admin->avatar);
                }
                
                // رفع الصورة الجديدة
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validated['avatar'] = $avatarPath;
            }
            
            // إزالة array_filter للسماح بالقيم الفارغة
            $admin->update($validated);
            
            // إعادة تحميل البيانات المحدثة
            $admin->refresh();
            
            // تحديث المستخدم في الـ auth guard لتحديث HandleInertiaRequests
            Auth::guard('admin')->setUser($admin);
            
            $adminDTO = AdminDTO::fromModel($admin)->toArray();
            
            // إرجاع Inertia response دائماً (مثل باقي update functions)
            return Inertia::render('Admin/Profile/UserProfile', [
                'user' => $adminDTO,
            ])->with('success', 'تم تحديث البيانات بنجاح');
        } 
        elseif ($request->user('web')) {
            $user = $request->user('web');
            
            // التعامل مع رفع الصورة
            if ($request->hasFile('avatar')) {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                
                // رفع الصورة الجديدة
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validated['avatar'] = $avatarPath;
            }
            
            // إزالة array_filter للسماح بالقيم الفارغة
            $user->update($validated);
            
            // إعادة تحميل البيانات المحدثة
            $user->refresh();
            
            // تحديث المستخدم في الـ auth guard لتحديث HandleInertiaRequests
            Auth::guard('web')->setUser($user);
            
            $userDTO = UserDTO::fromModel($user)->toArray();
            
            // إرجاع Inertia response دائماً (مثل باقي update functions)
            return Inertia::render('Admin/Profile/UserProfile', [
                'user' => $userDTO,
            ])->with('success', 'تم تحديث البيانات بنجاح');
        }
        else {
            return redirect()->route('admin.login');
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
