<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_user_id',
        'scope',
        'status',
        'title',
        'description',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    public function items()
    {
        return $this->hasMany(OfferItem::class);
    }

    public function targets()
    {
        return $this->hasMany(OfferTarget::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForCompany(Builder $query, int $companyUserId): Builder
    {
        return $query->where('company_user_id', $companyUserId);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('scope', 'public');
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('scope', 'private');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * عروض فعّالة الآن:
     * - status = active
     * - start_at <= now (إذا كانت موجودة)
     * - end_at   >= now (إذا كانت موجودة)
     */
    public function scopeActiveNow(Builder $query): Builder
    {
        $now = now();

        return $query->active()
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $now);
            });
    }
}