<?php

namespace App\Exceptions\ReturnInvoice;

class ReturnRatioExceededException extends ReturnInvoiceException
{
    public function __construct(string $message = 'Return ratio exceeds the maximum allowed by policy')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'return_ratio_exceeded';
    }
}
