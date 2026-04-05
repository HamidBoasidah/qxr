<?php

namespace App\Exceptions\ReturnPolicy;

class PolicyInUseException extends \Exception
{
    public function __construct(string $message = 'Cannot modify a policy that is linked to existing invoices')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'POLICY_IN_USE';
    }
}
