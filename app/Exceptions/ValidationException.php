<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException as BaseValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationException extends BaseValidationException
{
    public function render(Request $request): JsonResponse
    {
        $errors = $this->validator->errors()->toArray();
        
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صحيحة',
            'error_code' => 'VALIDATION_ERROR',
            'status_code' => 422,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], 422);
    }
} 