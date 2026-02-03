<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{

    protected $guard_name = 'admin';

    protected $fillable = ['name', 'guard_name'];
}
