<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingValidationException extends Exception
{
    protected array $validationErrors;
    protected string $errorType;

    public function __construct(
        string $message = 'Booking validation failed',
        array $validationErrors = [],
        string $errorType = 'validation_error',
        int $code = 422
    ) {
        parent::__construct($message, $code);
        $this->validationErrors = $validationErrors;
        $this->errorType = $errorType;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_type' => $this->errorType,
            'errors' => !empty($this->validationErrors) ? $this->validationErrors : [$this->getMessage()]
        ];

        return response()->json($response, $this->getCode());
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get error type
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }
}