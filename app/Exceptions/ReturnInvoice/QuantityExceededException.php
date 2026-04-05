<?php

namespace App\Exceptions\ReturnInvoice;

class QuantityExceededException extends ReturnInvoiceException
{
    public function __construct(string $message = 'Return quantity exceeds original quantity')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'quantity_exceeded';
    }
}
