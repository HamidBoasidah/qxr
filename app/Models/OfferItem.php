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