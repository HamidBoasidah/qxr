<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_user_id',
        'category_id',
        'name',
        'sku',
        'description',
        'unit_name',
        'base_price',
        'main_image',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // الشركة المالكة للمنتج
    public function company()
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    // Alias for company relationship (for consistency with policy)
    public function user()
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    // تصنيف المنتج
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // التاجات
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }
}