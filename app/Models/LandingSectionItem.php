<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LandingSectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_section_id',
        'title',
        'description',
        'image_path',
        'icon',
        'link',
        'link_text',
        'order',
        'data',
        'is_active',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the section that owns this item
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(LandingSection::class, 'landing_section_id');
    }

    /**
     * Scope to get only active items
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
     * Get translated description
     */
    public function getTranslatedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->description[$locale] ?? $this->description['ar'] ?? null;
    }

    /**
     * Get the full image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return Storage::url($this->image_path);
    }
}
