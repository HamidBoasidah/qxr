<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Services\LandingPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display listing of landing pages
     */
    public function index()
    {
        $landingPages = $this->landingPageService->getAllForAdmin();

        return Inertia::render('Admin/Landing/Index', [
            'landing_pages' => $landingPages,
        ]);
    }

    /**
     * Show the form for creating a new landing page
     */
    public function create()
    {
        return Inertia::render('Admin/Landing/Create');
    }

    /**
     * Store a newly created landing page
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:landing_pages,slug',
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $landingPage = $this->landingPageService->create($validated);

        return redirect()
            ->route('admin.landing.sections.index', $landingPage)
            ->with('success', 'Landing page created successfully');
    }

    /**
     * Show the form for editing the landing page
     */
    public function edit(LandingPage $landingPage)
    {
        return Inertia::render('Admin/Landing/Edit', [
            'landing_page' => $landingPage,
        ]);
    }

    /**
     * Update the landing page
     */
    public function update(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:landing_pages,slug,' . $landingPage->id,
            'is_active' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $this->landingPageService->update($landingPage, $validated);

        return redirect()
            ->route('admin.landing.index')
            ->with('success', 'Landing page updated successfully');
    }

    /**
     * Remove the landing page
     */
    public function destroy(LandingPage $landingPage)
    {
        $this->landingPageService->delete($landingPage);

        return redirect()
            ->route('admin.landing.index')
            ->with('success', 'Landing page deleted successfully');
    }
}
