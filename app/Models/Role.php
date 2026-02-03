<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Translatable\HasTranslations;

class Role extends SpatieRole
{
    use HasTranslations;

    protected $guard_name = 'admin';
    public $translatable = ['display_name'];
    
    protected $fillable = ['name', 'guard_name', 'display_name'];
}
