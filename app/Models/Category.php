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
        if (!$this->icon_path) return null;

        // إذا كانت data URI (SVG مضمّن)
        if (str_starts_with($this->icon_path, 'data:')) {
            return $this->icon_path;
        }

        // إذا المسار يبدأ بـ / يعني مسار مباشر في public
        if (str_starts_with($this->icon_path, '/')) {
            return $this->icon_path;
        }

        return Storage::url($this->icon_path);
    }

    /**
     * Products belonging to this category
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
