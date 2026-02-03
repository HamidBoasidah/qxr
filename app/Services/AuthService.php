<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
// logging removed
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class AuthService
{
    // تسجيل دخول API (token)
    public function loginApi(array $credentials)
    {
        $byEmail = !empty($credentials['email']);
        $byPhone = !empty($credentials['phone_number']);

        $user = null;
        if ($byEmail) {
            $user = User::where('email', $credentials['email'])->first();
        } elseif ($byPhone) {
            $user = User::where('phone_number', $credentials['phone_number'])->first();
        }

        $errorField = $byEmail ? 'email' : 'phone_number';

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                $errorField => ['بيانات تسجيل الدخول غير صحيحة'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                $errorField => ['الحساب معطل، يرجى التواصل مع الإدارة'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'avatar' => $user->avatar,
            'user_type' => $user->user_type,
        ];

        return [
            'user' => $userData,
            'token' => $token,
        ];
    }

    // تسجيل دخول session (Inertia/web)
    public function loginWeb(array $credentials)
    {
        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => ['بيانات تسجيل الدخول غير صحيحة'],
            ]);
        }

        $user = Auth::user();
        if (!$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['الحساب معطل، يرجى التواصل مع الإدارة'],
            ]);
        }
        // يمكن هنا إرجاع بيانات المستخدم أو null حسب الحاجة
        return $user;
    }

    public function logout(Request $request)
    {
        // تسجيل خروج المستخدم
        Auth::logout();

        // إبطال الجلسة وحماية CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }


    public function logoutFromAllDevices($user)
    {
        if ($user) {
            $user->tokens()->delete();
        }
        return true;
    }
}
