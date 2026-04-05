<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnInvoiceItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'return_invoice_id',
        'original_item_id',
        'returned_quantity',
        'unit_price_snapshot',
        'discount_type_snapshot',
        'discount_value_snapshot',
        'expiry_date_snapshot',
        'is_bonus',
        'refund_amount',
    ];

    protected $casts = [
        'unit_price_snapshot'     => 'decimal:4',
        'discount_value_snapshot' => 'decimal:4',
        'refund_amount'           => 'decimal:4',
        'is_bonus'                => 'boolean',
        'expiry_date_snapshot'    => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function returnInvoice(): BelongsTo
    {
        return $this->belongsTo(ReturnInvoice::class);
    }

    public function originalItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'original_item_id');
    }
}
