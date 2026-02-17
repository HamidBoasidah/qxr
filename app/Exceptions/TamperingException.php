<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TamperingException extends ApplicationException
{
    protected array $errors = [];

    public function __construct(string $message = 'تم اكتشاف تلاعب في البيانات', array $errors = [])
    {
        parent::__construct($message, 422, 'TAMPERING_DETECTED');
        $this->errors = $errors;
    }

    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'status_code' => $this->statusCode,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
