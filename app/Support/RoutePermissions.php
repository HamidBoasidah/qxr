<?php

namespace App\Support;

class RoutePermissions
{
    /**
     * Generate middleware assignments for a standard resource controller.
     */
    public static function resource(string $name): array
    {
        return [
            'index' => "permission:{$name}.view",
            'show' => "permission:{$name}.view",
            'create' => "permission:{$name}.create",
            'store' => "permission:{$name}.create",
            'edit' => "permission:{$name}.update",
            'update' => "permission:{$name}.update",
            'destroy' => "permission:{$name}.delete",
        ];
    }

    /**
     * Convenience helper for single-route permission checks.
     */
    public static function can(string $permission): string
    {
        return "permission:{$permission}";
    }
}
