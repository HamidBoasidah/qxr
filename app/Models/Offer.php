<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
}