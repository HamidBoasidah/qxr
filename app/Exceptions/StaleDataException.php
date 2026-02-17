<?php

namespace App\Exceptions;

class StaleDataException extends ApplicationException
{
    public function __construct(string $message = 'البيانات قديمة. يرجى التحديث والمحاولة مرة أخرى')
    {
        parent::__construct($message, 409, 'STALE_DATA');
    }
}
