<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnPolicy extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'return_window_days',
        'max_return_ratio',
        'bonus_return_enabled',
        'bonus_return_ratio',
        'discount_deduction_enabled',
        'min_days_before_expiry',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'bonus_return_enabled'      => 'boolean',
        'discount_deduction_enabled' => 'boolean',
        'is_default'                => 'boolean',
        'is_active'                 => 'boolean',
        'max_return_ratio'          => 'decimal:4',
        'bonus_return_ratio'        => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'return_policy_id');
    }

    public function returnInvoices(): HasMany
    {
        return $this->hasMany(ReturnInvoice::class, 'return_policy_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
