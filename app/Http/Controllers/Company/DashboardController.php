<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the company dashboard.
     */
    public function index(): Response
    {
        $user = Auth::guard('web')->user();
        $profile = $user->companyProfile;
        
        $stats = [
            'total_products' => $user->products()->count(),
            'total_messages' => $user->conversations()->count(),
            'active_offers' => $user->offers()->where('status', 'active')->count(),
        ];
        
        return Inertia::render('Company/Dashboard', [
            'stats' => $stats,
            'profile' => $profile,
        ]);
    }
}
