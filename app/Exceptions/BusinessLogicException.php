<?php

namespace App\Exceptions;

class BusinessLogicException extends ApplicationException
{
    public function __construct(string $message = 'خطأ في منطق الأعمال')
    {
        parent::__construct($message, 400, 'BUSINESS_LOGIC_ERROR');
    }
} 