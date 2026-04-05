<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnInvoice extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'original_invoice_id',
        'company_id',
        'return_policy_id',
        'total_refund_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'status'              => 'string',
        'total_refund_amount' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'original_invoice_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function returnPolicy(): BelongsTo
    {
        return $this->belongsTo(ReturnPolicy::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnInvoiceItem::class);
    }
}
