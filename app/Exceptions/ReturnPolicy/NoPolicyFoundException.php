<?php

namespace App\Exceptions\ReturnPolicy;

class NoPolicyFoundException extends \Exception
{
    public function __construct(string $message = 'No active return policy found for this company')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'NO_ACTIVE_RETURN_POLICY';
    }
}
