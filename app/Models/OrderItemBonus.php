<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemBonus extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_item_id',
        'offer_id',
        'bonus_product_id',
        'bonus_qty',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function bonusProduct()
    {
        return $this->belongsTo(Product::class, 'bonus_product_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
