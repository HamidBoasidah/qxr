<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationException extends Exception
{
    protected $statusCode = 500;
    protected $errorCode = 'APPLICATION_ERROR';

    public function __construct(string $message = '', int $statusCode = 500, string $errorCode = 'STATION_ERROR')
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'status_code' => $this->statusCode,
            'timestamp' => now()->toISOString(),
        ], $this->statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
} 
