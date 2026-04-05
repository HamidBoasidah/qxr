<?php

namespace App\Exceptions\ReturnInvoice;

class ExpiryTooCloseException extends ReturnInvoiceException
{
    public function __construct(string $message = 'Product expiry date is too close to allow return')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'expiry_too_close';
    }
}
