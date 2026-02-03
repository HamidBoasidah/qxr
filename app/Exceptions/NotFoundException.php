<?php

namespace App\Exceptions;

class NotFoundException extends ApplicationException
{
    public function __construct(string $message = 'المورد المطلوب غير موجود')
    {
        parent::__construct($message, 404, 'NOT_FOUND');
    }
} 