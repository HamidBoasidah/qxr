<?php

namespace App\Exceptions\ReturnPolicy;

class PolicyValidationException extends \Exception
{
    public function __construct(string $message = 'Return policy validation failed')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'POLICY_VALIDATION_ERROR';
    }
}
