<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'product_id',
        'min_qty',
        'reward_type',
        'discount_percent',
        'discount_fixed',
        'bonus_product_id',
        'bonus_qty',
    ];

    protected $casts = [
        'min_qty' => 'integer',
        'bonus_qty' => 'integer',
        'discount_percent' => 'decimal:2',
        'discount_fixed' => 'decimal:2',
    ];

    /**
     * Round decimal values to ensure proper precision before storage
     */
    public function setAttribute($key, $value)
    {
        // Round discount values to 2 decimal places
        if (in_array($key, ['discount_percent', 'discount_fixed']) && $value !== null) {
            $value = round((float)$value, 2, PHP_ROUND_HALF_UP);
        }
        
        return parent::setAttribute($key, $value);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function bonusProduct()
    {
        return $this->belongsTo(Product::class, 'bonus_product_id');
    }
}