<?php

namespace App\Http\Controllers\Admin;

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
        $this->middleware('auth:admin');
    }

    /**
     * Display reports index page with links to all reports
     */
    public function index()
    {
        return Inertia::render('Admin/Reports/Index', [
            'reports' => [
                [
                    'title' => 'تقرير الفواتير',
                    'title_en' => 'Invoices Report',
                    'description' => 'تقرير مفصل عن جميع الفواتير مع الإحصائيات والتصدير',
                    'route' => route('admin.reports.invoices'),
                    'icon' => 'invoice',
                ],
                [
                    'title' => 'تقرير الطلبات',
                    'title_en' => 'Orders Report',
                    'description' => 'تقرير شامل عن الطلبات وحالاتها',
                    'route' => route('admin.reports.orders'),
                    'icon' => 'orders',
                ],
                [
                    'title' => 'تقرير العروض',
                    'title_en' => 'Offers Report',
                    'description' => 'تقرير عن العروض النشطة والمنتهية',
                    'route' => route('admin.reports.offers'),
                    'icon' => 'offers',
                ],
                [
                    'title' => 'تقرير المنتجات',
                    'title_en' => 'Products Report',
                    'description' => 'تقرير شامل عن المنتجات والمبيعات',
                    'route' => route('admin.reports.products'),
                    'icon' => 'products',
                ],
            ],
        ]);
    }

    /**
     * Display invoices report
     */
    public function invoices(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        $data = $this->invoicesService->generate($request->all(), $perPage);

        return Inertia::render('Admin/Reports/Invoices', [
            'invoices' => $data['invoices'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export invoices report
     */
    public function exportInvoices(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        return $this->invoicesService->export($request->all(), $format);
    }

    /**
     * Display orders report
     */
    public function orders(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        $data = $this->ordersService->generate($request->all(), $perPage);

        return Inertia::render('Admin/Reports/Orders', [
            'orders' => $data['orders'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export orders report
     */
    public function exportOrders(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        return $this->ordersService->export($request->all(), $format);
    }

    /**
     * Display offers report
     */
    public function offers(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        $data = $this->offersService->generate($request->all(), $perPage);

        return Inertia::render('Admin/Reports/Offers', [
            'offers' => $data['offers'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export offers report
     */
    public function exportOffers(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        return $this->offersService->export($request->all(), $format);
    }

    /**
     * Display products report
     */
    public function products(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        
        $data = $this->productsService->generate($request->all(), $perPage);

        return Inertia::render('Admin/Reports/Products', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export products report
     */
    public function exportProducts(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        return $this->productsService->export($request->all(), $format);
    }
}
