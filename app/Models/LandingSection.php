<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'type',
        'title',
        'subtitle',
        'order',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the landing page that owns this section
     */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Get all items for this section
     */
    public function items(): HasMany
    {
        return $this->hasMany(LandingSectionItem::class)->orderBy('order');
    }

    /**
     * Get active items only
     */
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    /**
     * Scope to get only active sections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get translated title
     */
    public function getTranslatedTitle(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->title[$locale] ?? $this->title['ar'] ?? null;
    }

    /**
     * Get translated subtitle
     */
    public function getTranslatedSubtitle(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->subtitle[$locale] ?? $this->subtitle['ar'] ?? null;
    }
}
