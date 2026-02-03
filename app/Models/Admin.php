<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $table = 'admins';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'avatar',
        'phone_number',
        'whatsapp_number',
        'address',
        'password',
        'facebook',
        'x_url',
        'linkedin',
        'instagram',
        'is_active',
        'locale',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Relations to track who created/updated the admin (points to users table)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getNameAttribute()
    {
        $parts = array_filter([
            $this->first_name ?? null,
            $this->last_name ?? null,
        ], fn($p) => !is_null($p) && $p !== '');

        return $parts ? implode(' ', $parts) : null;
    }
}
