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

    // العروض التي تحتوي على هذا المنتج
    public function offerItems()
    {
        return $this->hasMany(OfferItem::class, 'product_id');
    }

    // العروض النشطة الحالية على هذا المنتج
    public function activeOffers()
    {
        return $this->hasManyThrough(
            Offer::class,
            OfferItem::class,
            'product_id',    // Foreign key on offer_items table
            'id',            // Foreign key on offers table
            'id',            // Local key on products table
            'offer_id'       // Local key on offer_items table
        )
        ->where('offers.status', 'active')
        ->where('offers.scope', 'public')
        ->where(function ($query) {
            $query->whereNull('offers.start_at')
                ->orWhere('offers.start_at', '<=', now());
        })
        ->where(function ($query) {
            $query->whereNull('offers.end_at')
                ->orWhere('offers.end_at', '>=', now());
        });
    }
}