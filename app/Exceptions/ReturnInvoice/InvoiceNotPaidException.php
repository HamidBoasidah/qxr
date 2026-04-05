<?php

namespace App\Exceptions\ReturnInvoice;

class InvoiceNotPaidException extends ReturnInvoiceException
{
    public function __construct(string $message = 'Invoice must be in paid status to process a return')
    {
        parent::__construct($message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }

    public function getErrorCode(): string
    {
        return 'invoice_not_paid';
    }
}
