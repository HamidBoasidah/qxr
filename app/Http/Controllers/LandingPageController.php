<?php

namespace App\Http\Controllers;

use App\Services\LandingPageService;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService
    ) {}

    /**
     * Display the landing page
     */
    public function index(string $slug = 'home')
    {
        $landingPage = $this->landingPageService->getActiveLandingPage($slug);

        if (!$landingPage) {
            abort(404);
        }

        return Inertia::render('LandingPage', [
            'landingPage' => $landingPage,
        ]);
    }

    /**
     * Show the home landing page
     */
    public function home ()
    {
        return $this->index('home');
    }
}
