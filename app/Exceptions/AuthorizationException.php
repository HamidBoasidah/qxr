<?php

namespace App\Exceptions;

class AuthorizationException extends ApplicationException
{
    public function __construct(string $message = 'غير مصرح لك بتنفيذ هذا الإجراء')
    {
        parent::__construct($message, 403, 'AUTHORIZATION_FAILED');
    }
}
