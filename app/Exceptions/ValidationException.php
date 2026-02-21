<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException as BaseValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Contracts\Validation\Validator;

class ValidationException extends BaseValidationException
{
    protected ?string $customMessage = null;
    
    /**
     * Create exception with just a message string
     */
    public static function withMessage(string $message): self
    {
        $validator = ValidatorFacade::make([], []);
        $validator->errors()->add('error', $message);
        
        $instance = new static($validator);
        $instance->customMessage = $message;
        
        return $instance;
    }
    
    /**
     * Create exception from validator
     */
    public function __construct(Validator|string $validator, $response = null, $errorBag = 'default')
    {
        if (is_string($validator)) {
            // Handle string message for backward compatibility
            $message = $validator;
            $validator = ValidatorFacade::make([], []);
            $validator->errors()->add('error', $message);
            $this->customMessage = $message;
        }
        
        parent::__construct($validator, $response, $errorBag);
    }
    
    /**
     * Get validation errors
     */
    public function errors(): array
    {
        if ($this->customMessage) {
            return ['error' => [$this->customMessage]];
        }
        
        return $this->validator->errors()->toArray();
    }
    
    public function render(Request $request): JsonResponse
    {
        $errors = $this->errors();
        
        return response()->json([
            'success' => false,
            'message' => $this->customMessage ?? 'بيانات غير صحيحة',
            'error_code' => 'VALIDATION_ERROR',
            'status_code' => 422,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], 422);
    }
} 