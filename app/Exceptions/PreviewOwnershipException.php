<?php

namespace App\Exceptions;

class PreviewOwnershipException extends ApplicationException
{
    public function __construct(string $message = 'This preview belongs to another customer')
    {
        parent::__construct($message, 403, 'PREVIEW_OWNERSHIP_ERROR');
    }
}
