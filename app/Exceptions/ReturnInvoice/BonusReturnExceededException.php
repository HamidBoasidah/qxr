<?php

namespace App\Exceptions\ReturnInvoice;

class BonusReturnExceededException extends ReturnInvoiceException
{
    public function __construct(string $message = 'Bonus return quantity exceeds the allowed ratio')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'bonus_return_exceeded';
    }
}
