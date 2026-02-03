<?php

namespace App\Exceptions;

class DataIntegrityException extends ApplicationException
{
    public function __construct(string $message = 'خطأ في سلامة البيانات')
    {
        parent::__construct($message, 422, 'DATA_INTEGRITY_ERROR');
    }
} 