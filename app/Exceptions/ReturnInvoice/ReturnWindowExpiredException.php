<?php

namespace App\Exceptions\ReturnInvoice;

class ReturnWindowExpiredException extends ReturnInvoiceException
{
    public function __construct(string $message = 'Return window has expired')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'return_window_expired';
    }
}
