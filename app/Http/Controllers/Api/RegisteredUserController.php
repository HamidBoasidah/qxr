<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreUserRequest $request, UserService $userService): JsonResponse
    {
        $data = $request->validated();

        // إذا وُجد ملف مرفق نُمرره ضمن البيانات ليتولى الـ Service التعامل معه
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment');
        }

        $user = $userService->create($data);

        // Create token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'طلب التسجيل تم بنجاح',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}
