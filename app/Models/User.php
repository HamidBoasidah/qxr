<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'avatar',
        'phone_number',
        'whatsapp_number',
        'password',

        // ✅ كانت ناقصة رغم وجودها في جدول users
        'user_type',
        'gender',

        'facebook',
        'x_url',
        'linkedin',
        'instagram',
        'is_active',
        'locale',

        // ملاحظة: لاحقًا في لوحة الإدارة سنجعلها تُملأ من السيرفر وليس من الفورم
        'created_by',
        'updated_by',
    ];

    protected array $dontLog = ['password', 'remember_token'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function updatedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'updated_by');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // ✅ بروفايل العميل (1:1)
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    // ✅ بروفايل الشركة (1:1)
    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    public function getNameAttribute(): ?string
    {
        $parts = array_filter([
            $this->first_name ?? null,
            $this->last_name ?? null,
        ], fn ($p) => !is_null($p) && $p !== '');

        return $parts ? implode(' ', $parts) : null;
    }

    /**
     * يرجّع البروفايل المناسب حسب نوع المستخدم
     * (مفيد في لوحة الإدارة عند العرض)
     */
    public function profile(): ?object
    {
        return $this->user_type === 'company'
            ? $this->companyProfile
            : $this->customerProfile;
    }
}