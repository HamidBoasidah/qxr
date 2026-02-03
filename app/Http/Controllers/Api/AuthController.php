<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Traits\ExceptionHandler;
use App\Http\Traits\SuccessResponse;
use App\Http\Requests\Api\UpdatePasswordRequest;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ExceptionHandler, SuccessResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'nullable|email|required_without:phone_number',
                'phone_number' => 'nullable|string|required_without:email',
                'password' => 'required|string',
            ]);
            $result = $this->authService->loginApi($request->only('email', 'phone_number', 'password'));
            return $this->successResponse($result, 'تم تسجيل الدخول بنجاح');
        } catch (\Exception $e) {
            throw $e;
        }
    }

  public function logout(Request $request)
    {
        try {
            // تمرير الطلب إلى الخدمة
            $this->authService->logout($request);

            // الرد بنجاح
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        }
    }

    public function logoutFromAllDevices(Request $request)
    {
        try {
            $this->authService->logoutFromAllDevices($request->user());
            return $this->successResponse(null, 'تم تسجيل الخروج من جميع الأجهزة بنجاح');
        } catch (\Exception $e) {
            return $this->successResponse(null, 'تم تسجيل الخروج من جميع الأجهزة بنجاح');
        }
    }

    public function me(Request $request)
    {
        return $this->successResponse([
            'user' => $request->user()
        ], 'تم جلب بيانات المستخدم بنجاح');
    }

    /**
     * Update the authenticated user's password.
     *
     * @param UpdatePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            $user = $request->user();
            
            // Update the password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return $this->successResponse(null, 'تم تحديث كلمة السر بنجاح');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
