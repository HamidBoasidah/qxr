<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Services\LandingSectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingSectionController extends Controller
{
    public function __construct(
        private LandingSectionService $landingSectionService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display listing of sections for a landing page
     */
    public function index(LandingPage $landingPage)
    {
        $sections = $this->landingSectionService->getAllForLandingPage($landingPage);

        return Inertia::render('Admin/Landing/Sections/Index', [
            'landing_page' => $landingPage,
            'sections' => $sections,
        ]);
    }

    /**
     * Show the form for creating a new section
     */
    public function create(LandingPage $landingPage)
    {
        return Inertia::render('Admin/Landing/Sections/Create', [
            'landing_page' => $landingPage,
            'section_types' => $this->getSectionTypes(),
        ]);
    }

    /**
     * Store a newly created section
     */
    public function store(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:hero,features,services,steps,testimonials,faq,cta,stats,mobile_app',
            'title' => 'nullable|array',
            'title.ar' => 'nullable|string',
            'title.en' => 'nullable|string',
            'subtitle' => 'nullable|array',
            'subtitle.ar' => 'nullable|string',
            'subtitle.en' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $section = $this->landingSectionService->create($landingPage, $validated);

        return redirect()
            ->route('admin.landing.sections.items.index', ['landingPage' => $landingPage, 'landingSection' => $section])
            ->with('success', 'Section created successfully');
    }

    /**
     * Show the form for editing the section
     */
    public function edit(LandingPage $landingPage, LandingSection $landingSection)
    {
        return Inertia::render('Admin/Landing/Sections/Edit', [
            'landing_page' => $landingPage,
            'section' => $landingSection,
            'section_types' => $this->getSectionTypes(),
        ]);
    }

    /**
     * Update the section
     */
    public function update(Request $request, LandingPage $landingPage, LandingSection $landingSection)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:hero,features,services,steps,testimonials,faq,cta,stats,mobile_app',
            'title' => 'nullable|array',
            'title.ar' => 'nullable|string',
            'title.en' => 'nullable|string',
            'subtitle' => 'nullable|array',
            'subtitle.ar' => 'nullable|string',
            'subtitle.en' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $this->landingSectionService->update($landingSection, $validated);

        return redirect()
            ->route('admin.landing.sections.index', $landingPage)
            ->with('success', 'Section updated successfully');
    }

    /**
     * Remove the section
     */
    public function destroy(LandingPage $landingPage, LandingSection $landingSection)
    {
        $this->landingSectionService->delete($landingSection);

        return redirect()
            ->route('admin.landing.sections.index', $landingPage)
            ->with('success', 'Section deleted successfully');
    }

    /**
     * Reorder sections
     */
    public function reorder(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'required|exists:landing_sections,id',
        ]);

        $this->landingSectionService->reorder($landingPage, $validated['section_ids']);

        return response()->json(['message' => 'Sections reordered successfully']);
    }

    /**
     * Toggle section visibility
     */
    public function toggleVisibility(LandingPage $landingPage, LandingSection $landingSection)
    {
        $this->landingSectionService->toggleVisibility($landingSection);

        return redirect()
            ->route('admin.landing.sections.index', $landingPage)
            ->with('success', 'Section visibility toggled');
    }

    /**
     * Get available section types
     */
    private function getSectionTypes(): array
    {
        return [
            ['value' => 'hero', 'label' => 'Hero Section'],
            ['value' => 'features', 'label' => 'Features Section'],
            ['value' => 'services', 'label' => 'Services Section'],
            ['value' => 'steps', 'label' => 'Steps Section'],
            ['value' => 'testimonials', 'label' => 'Testimonials Section'],
            ['value' => 'faq', 'label' => 'FAQ Section'],
            ['value' => 'cta', 'label' => 'CTA Section'],
            ['value' => 'stats', 'label' => 'Stats Section'],
            ['value' => 'mobile_app', 'label' => 'Mobile App Section'],
        ];
    }
}
