<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfferTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'target_type',
        'target_id',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}