<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Category extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category_type',
        'is_active',
        'icon_path',
        'created_by',
        'updated_by',
    ];


    /**
     * Get the icon URL attribute.
     */
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon_path ? Storage::url($this->icon_path) : null;
    }

    /**
     * Products belonging to this category
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
