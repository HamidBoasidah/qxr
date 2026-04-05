<?php

namespace App\Exceptions\ReturnInvoice;

class DuplicateReturnException extends ReturnInvoiceException
{
    public function __construct(string $message = 'A return invoice already exists for this invoice')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 409;
    }

    public function getErrorCode(): string
    {
        return 'duplicate_return';
    }
}
