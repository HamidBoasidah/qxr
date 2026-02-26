<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Support\Str;

class LandingPageService
{
    /**
     * Get landing page with all active sections and items
     */
    public function getActiveLandingPage(string $slug = 'home'): ?array
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_active', true)
            ->with(['activeSections.activeItems'])
            ->first();

        if (!$landingPage) {
            return null;
        }

        return [
            'id' => $landingPage->id,
            'title' => $landingPage->title,
            'slug' => $landingPage->slug,
            'meta_title' => $landingPage->meta_title,
            'meta_description' => $landingPage->meta_description,
            'sections' => $landingPage->activeSections->map(function ($section) {
                return $this->formatSection($section);
            }),
        ];
    }

    /**
     * Format section data
     */
    protected function formatSection(LandingSection $section): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $section->id,
            'type' => $section->type,
            'title' => $section->title[$locale] ?? $section->title['ar'] ?? null,
            'subtitle' => $section->subtitle[$locale] ?? $section->subtitle['ar'] ?? null,
            'settings' => $section->settings ?? [],
            'items' => $section->activeItems->map(function ($item) use ($locale) {
                return [
                    'id' => $item->id,
                    'title' => $item->title[$locale] ?? $item->title['ar'] ?? null,
                    'description' => $item->description[$locale] ?? $item->description['ar'] ?? null,
                    'image_url' => $item->image_url,
                    'icon' => $item->icon,
                    'link' => $item->link,
                    'link_text' => $item->link_text,
                    'data' => $item->data ?? [],
                ];
            }),
        ];
    }

    /**
     * Create new landing page
     */
    public function create(array $data): LandingPage
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        return LandingPage::create($data);
    }

    /**
     * Update landing page
     */
    public function update(LandingPage $landingPage, array $data): LandingPage
    {
        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $landingPage->update($data);
        return $landingPage->fresh();
    }

    /**
     * Delete landing page
     */
    public function delete(LandingPage $landingPage): bool
    {
        return $landingPage->delete();
    }

    /**
     * Get all landing pages for admin
     */
    public function getAllForAdmin()
    {
        return LandingPage::withCount('sections')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
