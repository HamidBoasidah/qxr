<?php

namespace App\DTOs;

abstract class BaseDTO
{
    protected function fileUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }
}
