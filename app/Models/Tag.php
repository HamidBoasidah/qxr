<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tag_type',
        'is_active',
        'created_by',
        'updated_by',
    ];

}
