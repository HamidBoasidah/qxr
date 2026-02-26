<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\LandingSection;
use App\Models\LandingSectionItem;
use App\Services\LandingSectionItemService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingSectionItemController extends Controller
{
    public function __construct(
        private LandingSectionItemService $landingSectionItemService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display listing of items for a section
     */
    public function index(LandingPage $landingPage, LandingSection $landingSection)
    {
        $items = $this->landingSectionItemService->getAllForSection($landingSection);

        return Inertia::render('Admin/Landing/Sections/Items/Index', [
            'landing_page' => $landingPage,
            'section' => $landingSection,
            'items' => $items,
        ]);
    }

    /**
     * Show the form for creating a new item
     */
    public function create(LandingPage $landingPage, LandingSection $landingSection)
    {
        return Inertia::render('Admin/Landing/Sections/Items/Create', [
            'landing_page' => $landingPage,
            'section' => $landingSection,
        ]);
    }

    /**
     * Store a newly created item
     */
    public function store(Request $request, LandingPage $landingPage, LandingSection $landingSection)
    {
        $validated = $request->validate([
            'title' => 'nullable|array',
            'title.ar' => 'nullable|string',
            'title.en' => 'nullable|string',
            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'icon' => 'nullable|string',
            'link' => 'nullable|url',
            'link_text' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'data' => 'nullable|array',
        ]);

        $this->landingSectionItemService->create($landingSection, $validated);

        return redirect()
            ->route('admin.landing.sections.items.index', ['landingPage' => $landingPage, 'landingSection' => $landingSection])
            ->with('success', 'Item created successfully');
    }

    /**
     * Show the form for editing the item
     */
    public function edit(LandingPage $landingPage, LandingSection $landingSection, LandingSectionItem $landingSectionItem)
    {
        return Inertia::render('Admin/Landing/Sections/Items/Edit', [
            'landing_page' => $landingPage,
            'section' => $landingSection,
            'item' => $landingSectionItem,
        ]);
    }

    /**
     * Update the item
     */
    public function update(Request $request, LandingPage $landingPage, LandingSection $landingSection, LandingSectionItem $landingSectionItem)
    {
        $validated = $request->validate([
            'title' => 'nullable|array',
            'title.ar' => 'nullable|string',
            'title.en' => 'nullable|string',
            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'icon' => 'nullable|string',
            'link' => 'nullable|url',
            'link_text' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'data' => 'nullable|array',
        ]);

        $this->landingSectionItemService->update($landingSectionItem, $validated);

        return redirect()
            ->route('admin.landing.sections.items.index', ['landingPage' => $landingPage, 'landingSection' => $landingSection])
            ->with('success', 'Item updated successfully');
    }

    /**
     * Remove the item
     */
    public function destroy(LandingPage $landingPage, LandingSection $landingSection, LandingSectionItem $landingSectionItem)
    {
        $this->landingSectionItemService->delete($landingSectionItem);

        return redirect()
            ->route('admin.landing.sections.items.index', ['landingPage' => $landingPage, 'landingSection' => $landingSection])
            ->with('success', 'Item deleted successfully');
    }

    /**
     * Reorder items
     */
    public function reorder(Request $request, LandingPage $landingPage, LandingSection $landingSection)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'required|exists:landing_section_items,id',
        ]);

        $this->landingSectionItemService->reorder($landingSection, $validated['item_ids']);

        return response()->json(['message' => 'Items reordered successfully']);
    }

    /**
     * Toggle item visibility
     */
    public function toggleVisibility(LandingPage $landingPage, LandingSection $landingSection, LandingSectionItem $landingSectionItem)
    {
        $this->landingSectionItemService->toggleVisibility($landingSectionItem);

        return redirect()
            ->route('admin.landing.sections.items.index', ['landingPage' => $landingPage, 'landingSection' => $landingSection])
            ->with('success', 'Item visibility toggled');
    }
}
