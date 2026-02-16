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

    /**
     * Get the target entity based on target_type
     */
    public function target()
    {
        switch ($this->target_type) {
            case 'customer':
                return $this->belongsTo(\App\Models\User::class, 'target_id');
            case 'customer_category':
                return $this->belongsTo(\App\Models\Category::class, 'target_id');
            case 'customer_tag':
                return $this->belongsTo(\App\Models\Tag::class, 'target_id');
            default:
                return null;
        }
    }

    /**
     * Get target name based on type
     */
    public function getTargetNameAttribute()
    {
        if (!$this->target_id) {
            return null;
        }

        switch ($this->target_type) {
            case 'customer':
                $user = \App\Models\User::find($this->target_id);
                return $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : null;
            
            case 'customer_category':
                $category = \App\Models\Category::find($this->target_id);
                return $category?->name;
            
            case 'customer_tag':
                $tag = \App\Models\Tag::find($this->target_id);
                return $tag?->name;
            
            default:
                return null;
        }
    }
}