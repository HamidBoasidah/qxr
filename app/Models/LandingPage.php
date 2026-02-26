<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all sections for this landing page
     */
    public function sections(): HasMany
    {
        return $this->hasMany(LandingSection::class)->orderBy('order');
    }

    /**
     * Get active sections only
     */
    public function activeSections(): HasMany
    {
        return $this->sections()->where('is_active', true);
    }

    /**
     * Scope to get only active landing pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
