<?php

namespace App\Exceptions\ReturnInvoice;

abstract class ReturnInvoiceException extends \Exception
{
    /**
     * Returns the HTTP status code for this exception.
     */
    abstract public function getHttpStatus(): int;

    /**
     * Returns the machine-readable error code for this exception.
     */
    abstract public function getErrorCode(): string;
}
