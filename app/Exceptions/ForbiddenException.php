<?php

namespace App\Exceptions;

class ForbiddenException extends ApplicationException
{
    public function __construct(string $message = 'ممنوع الوصول لهذا المورد')
    {
        parent::__construct($message, 403, 'FORBIDDEN');
    }
} 