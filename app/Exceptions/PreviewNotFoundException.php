<?php

namespace App\Exceptions;

class PreviewNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Preview not found or expired')
    {
        parent::__construct($message, 404, 'PREVIEW_NOT_FOUND');
    }
}
