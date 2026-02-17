<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'unit_price_snapshot',
        'discount_amount_snapshot',
        'final_line_total_snapshot',
        'selected_offer_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function selectedOffer()
    {
        return $this->belongsTo(Offer::class, 'selected_offer_id');
    }

    public function bonuses()
    {
        return $this->hasMany(OrderItemBonus::class);
    }
}
