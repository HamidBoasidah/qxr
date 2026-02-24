<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminDashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private AdminDashboardStatsService $statsService
    ) {
        $this->middleware('permission:dashboard.view')->only(['index']);
    }

    /**
     * Display the admin dashboard
     */
    public function index(Request $request): Response
    {
        // Parse filters from request
        $filters = $this->parseFilters($request);

        // Get dashboard statistics
        $stats = $this->statsService->getStats($filters);

        // Get companies list for filter dropdown
        $companies = User::where('user_type', 'company')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'companies' => $companies,
            'filters' => $filters,
            'presets' => $this->getDatePresets(),
        ]);
    }

    /**
     * API endpoint for refreshing charts data
     */
    public function chartData(Request $request)
    {
        $filters = $this->parseFilters($request);
        $stats = $this->statsService->getStats($filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache()
    {
        $this->statsService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
        ]);
    }

    /**
     * Parse and validate filters from request
     */
    protected function parseFilters(Request $request): array
    {
        $filters = [];

        // Date range filter
        if ($request->filled('date_preset')) {
            $preset = $request->input('date_preset');
            $dateRange = $this->getDateRangeFromPreset($preset);
            $filters['date_from'] = $dateRange['from'];
            $filters['date_to'] = $dateRange['to'];
            $filters['date_preset'] = $preset;
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            $filters['date_from'] = $request->input('date_from');
            $filters['date_to'] = $request->input('date_to');
            $filters['date_preset'] = 'custom';
        } else {
            // Default: last 30 days
            $dateRange = $this->getDateRangeFromPreset('last_30_days');
            $filters['date_from'] = $dateRange['from'];
            $filters['date_to'] = $dateRange['to'];
            $filters['date_preset'] = 'last_30_days';
        }

        // Company filter
        if ($request->filled('company_id')) {
            $filters['company_id'] = (int) $request->input('company_id');
        }

        // Status filter
        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }

        return $filters;
    }

    /**
     * Get date range from preset
     */
    protected function getDateRangeFromPreset(string $preset): array
    {
        $now = now();

        return match($preset) {
            'today' => [
                'from' => $now->clone()->startOfDay()->toDateString(),
                'to' => $now->clone()->endOfDay()->toDateString(),
            ],
            'yesterday' => [
                'from' => $now->clone()->subDay()->startOfDay()->toDateString(),
                'to' => $now->clone()->subDay()->endOfDay()->toDateString(),
            ],
            'last_7_days' => [
                'from' => $now->clone()->subDays(6)->startOfDay()->toDateString(),
                'to' => $now->clone()->endOfDay()->toDateString(),
            ],
            'last_30_days' => [
                'from' => $now->clone()->subDays(29)->startOfDay()->toDateString(),
                'to' => $now->clone()->endOfDay()->toDateString(),
            ],
            'this_month' => [
                'from' => $now->clone()->startOfMonth()->toDateString(),
                'to' => $now->clone()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'from' => $now->clone()->subMonth()->startOfMonth()->toDateString(),
                'to' => $now->clone()->subMonth()->endOfMonth()->toDateString(),
            ],
            'last_90_days' => [
                'from' => $now->clone()->subDays(89)->startOfDay()->toDateString(),
                'to' => $now->clone()->endOfDay()->toDateString(),
            ],
            'this_year' => [
                'from' => $now->clone()->startOfYear()->toDateString(),
                'to' => $now->clone()->endOfYear()->toDateString(),
            ],
            default => [
                'from' => $now->clone()->subDays(29)->startOfDay()->toDateString(),
                'to' => $now->clone()->endOfDay()->toDateString(),
            ],
        };
    }

    /**
     * Get available date presets
     */
    protected function getDatePresets(): array
    {
        return [
            ['value' => 'today', 'label' => 'اليوم'],
            ['value' => 'yesterday', 'label' => 'أمس'],
            ['value' => 'last_7_days', 'label' => 'آخر 7 أيام'],
            ['value' => 'last_30_days', 'label' => 'آخر 30 يوم'],
            ['value' => 'this_month', 'label' => 'هذا الشهر'],
            ['value' => 'last_month', 'label' => 'الشهر الماضي'],
            ['value' => 'last_90_days', 'label' => 'آخر 90 يوم'],
            ['value' => 'this_year', 'label' => 'هذا العام'],
            ['value' => 'custom', 'label' => 'تخصيص'],
        ];
    }
}
