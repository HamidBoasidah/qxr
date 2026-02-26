<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingSection;
use Illuminate\Support\Facades\DB;

class LandingSectionService
{
    /**
     * Get all sections for a landing page
     */
    public function getAllForLandingPage(LandingPage $landingPage)
    {
        return $landingPage->sections()
            ->withCount('items')
            ->orderBy('order')
            ->get();
    }

    /**
     * Create new section
     */
    public function create(LandingPage $landingPage, array $data): LandingSection
    {
        // Get the max order and increment
        $maxOrder = $landingPage->sections()->max('order') ?? 0;
        $data['order'] = $data['order'] ?? ($maxOrder + 1);
        $data['landing_page_id'] = $landingPage->id;

        return LandingSection::create($data);
    }

    /**
     * Update section
     */
    public function update(LandingSection $section, array $data): LandingSection
    {
        $section->update($data);
        return $section->fresh();
    }

    /**
     * Delete section
     */
    public function delete(LandingSection $section): bool
    {
        return $section->delete();
    }

    /**
     * Reorder sections
     */
    public function reorder(LandingPage $landingPage, array $sectionIds): void
    {
        DB::transaction(function () use ($landingPage, $sectionIds) {
            foreach ($sectionIds as $index => $sectionId) {
                LandingSection::where('id', $sectionId)
                    ->where('landing_page_id', $landingPage->id)
                    ->update(['order' => $index + 1]);
            }
        });
    }

    /**
     * Toggle section visibility
     */
    public function toggleVisibility(LandingSection $section): LandingSection
    {
        $section->update(['is_active' => !$section->is_active]);
        return $section->fresh();
    }

    /**
     * Get section with items
     */
    public function getWithItems(LandingSection $section): LandingSection
    {
        return $section->load('items');
    }
}
