<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreviewInvalidatedException extends ApplicationException
{
    protected array $details = [];

    public function __construct(
        string $message = 'Preview is no longer valid. Please re-preview your order.',
        array $details = []
    ) {
        parent::__construct($message, 409, 'PREVIEW_INVALIDATED');
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'details' => $this->details,
            'error_code' => $this->errorCode,
            'status_code' => $this->statusCode,
            'timestamp' => now()->toISOString(),
        ], $this->statusCode);
    }
}
