<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\Reports\InvoicesReportService;
use App\Services\Reports\OrdersReportService;
use App\Services\Reports\OffersReportService;
use App\Services\Reports\ProductsReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(
        private InvoicesReportService $invoicesService,
        private OrdersReportService $ordersService,
        private OffersReportService $offersService,
        private ProductsReportService $productsService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display reports index page with links to all reports
     */
    public function index()
    {
        return Inertia::render('Company/Reports/Index', [
            'reports' => [
                [
                    'title' => 'تقرير الفواتير',
                    'title_en' => 'Invoices Report',
                    'description' => 'تقرير مفصل عن فواتير شركتي مع الإحصائيات والتصدير',
                    'route' => route('company.reports.invoices'),
                    'icon' => 'invoice',
                ],
                [
                    'title' => 'تقرير الطلبات',
                    'title_en' => 'Orders Report',
                    'description' => 'تقرير شامل عن طلبات شركتي وحالاتها',
                    'route' => route('company.reports.orders'),
                    'icon' => 'orders',
                ],
                [
                    'title' => 'تقرير العروض',
                    'title_en' => 'Offers Report',
                    'description' => 'تقرير عن عروض شركتي النشطة والمنتهية',
                    'route' => route('company.reports.offers'),
                    'icon' => 'offers',
                ],
                [
                    'title' => 'تقرير المنتجات',
                    'title_en' => 'Products Report',
                    'description' => 'تقرير شامل عن منتجات شركتي والمبيعات',
                    'route' => route('company.reports.products'),
                    'icon' => 'products',
                ],
            ],
        ]);
    }

    /**
     * Display invoices report (company-specific)
     */
    public function invoices(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        $data = $this->invoicesService->generate($filters, $perPage);

        return Inertia::render('Company/Reports/Invoices', [
            'invoices' => $data['invoices'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export invoices report (company-specific)
     */
    public function exportInvoices(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        return $this->invoicesService->export($filters, $format);
    }

    /**
     * Display orders report (company-specific)
     */
    public function orders(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        $data = $this->ordersService->generate($filters, $perPage);

        return Inertia::render('Company/Reports/Orders', [
            'orders' => $data['orders'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export orders report (company-specific)
     */
    public function exportOrders(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        return $this->ordersService->export($filters, $format);
    }

    /**
     * Display offers report (company-specific)
     */
    public function offers(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        $data = $this->offersService->generate($filters, $perPage);

        return Inertia::render('Company/Reports/Offers', [
            'offers' => $data['offers'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export offers report (company-specific)
     */
    public function exportOffers(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        return $this->offersService->export($filters, $format);
    }

    /**
     * Display products report (company-specific)
     */
    public function products(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        $data = $this->productsService->generate($filters, $perPage);

        return Inertia::render('Company/Reports/Products', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export products report (company-specific)
     */
    public function exportProducts(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        // Add company filter automatically
        $filters = $request->all();
        $filters['company_user_id'] = $request->user()->id;
        
        return $this->productsService->export($filters, $format);
    }
}
