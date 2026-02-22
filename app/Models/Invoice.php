<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'order_id',
        'subtotal_snapshot',
        'discount_total_snapshot',
        'total_snapshot',
        'issued_at',
        'status',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function bonusItems()
    {
        return $this->hasMany(InvoiceBonusItem::class);
    }
}