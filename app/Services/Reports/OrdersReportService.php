<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Support\ReportFilters;
use App\Exports\OrdersReportExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class OrdersReportService
{
    /**
     * Generate orders report data
     */
    public function generate(array $filters, int $perPage = 15): array
    {
        $filters = ReportFilters::clean($filters);

        // Build query
        $query = Order::with([
            'company:id,first_name,last_name',
            'customer:id,first_name,last_name',
            'items',
        ])->withCount('items');

        // Apply filters
        $query = ReportFilters::apply($query, $filters);

        // Get paginated results
        $orders = $query->latest('submitted_at')->paginate($perPage);

        // Transform data
        $orders->getCollection()->transform(function ($order) {
            $total = $order->items->sum(function ($item) {
                return $item->qty * $item->price_snapshot;
            });

            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'company_name' => $order->company 
                    ? trim(($order->company->first_name ?? '') . ' ' . ($order->company->last_name ?? ''))
                    : null,
                'customer_name' => $order->customer 
                    ? trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))
                    : null,
                'items_count' => $order->items_count,
                'total' => (float) $total,
                'status' => $order->status,
                'submitted_at' => $order->submitted_at?->format('Y-m-d H:i'),
                'approved_at' => $order->approved_at?->format('Y-m-d H:i'),
                'delivered_at' => $order->delivered_at?->format('Y-m-d H:i'),
            ];
        });

        // Generate summary
        $summary = $this->generateSummary($filters);

        return [
            'orders' => $orders,
            'summary' => $summary,
            'filters' => $filters,
        ];
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary(array $filters): array
    {
        $query = Order::query();
        $query = ReportFilters::apply($query, $filters);

        $totals = [
            'total_count' => $query->count(),
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'approved_count' => (clone $query)->where('status', 'approved')->count(),
            'delivered_count' => (clone $query)->where('status', 'delivered')->count(),
            'cancelled_count' => (clone $query)->where('status', 'cancelled')->count(),
        ];

        // Calculate total revenue from order items
        $ordersWithItems = (clone $query)->with('items')->get();
        $total_revenue = $ordersWithItems->sum(function ($order) {
            return $order->items->sum(function ($item) {
                return $item->qty * $item->price_snapshot;
            });
        });

        return array_merge($totals, [
            'total_revenue' => (float) $total_revenue,
        ]);
    }

    /**
     * Export report in requested format
     */
    public function export(array $filters, string $format = 'excel')
    {
        $filters = ReportFilters::clean($filters);

        return match($format) {
            'excel' => $this->exportExcel($filters),
            'pdf' => $this->exportPDF($filters),
            'word' => $this->exportWord($filters),
            default => $this->exportExcel($filters),
        };
    }

    /**
     * Export to Excel
     */
    protected function exportExcel(array $filters)
    {
        return Excel::download(
            new OrdersReportExport($filters),
            'orders-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    /**
     * Export to PDF
     */
    protected function exportPDF(array $filters)
    {
        $data = $this->getExportData($filters);
        
        $pdf = Pdf::loadView('reports.orders-pdf', $data);
        
        return $pdf->download('orders-report-' . now()->format('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Export to Word (DOCX)
     */
    protected function exportWord(array $filters)
    {
        $data = $this->getExportData($filters);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Title
        $section->addTitle('تقرير الطلبات / Orders Report', 1);
        $section->addText('Generated: ' . now()->format('Y-m-d H:i:s'));
        $section->addTextBreak();

        // Summary
        $section->addTitle('Summary', 2);
        $section->addText('Total Orders: ' . $data['summary']['total_count']);
        $section->addText('Total Revenue: ' . number_format($data['summary']['total_revenue'], 2));
        $section->addText('Delivered: ' . $data['summary']['delivered_count']);
        $section->addTextBreak();

        // Table
        $section->addTitle('Orders', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        // Header
        $table->addRow();
        $table->addCell(2000)->addText('Order No');
        $table->addCell(2000)->addText('Company');
        $table->addCell(2000)->addText('Customer');
        $table->addCell(1500)->addText('Items');
        $table->addCell(1500)->addText('Total');
        $table->addCell(1500)->addText('Status');
        $table->addCell(2000)->addText('Submitted At');

        // Data rows
        foreach ($data['orders'] as $order) {
            $table->addRow();
            $table->addCell(2000)->addText($order['order_no']);
            $table->addCell(2000)->addText($order['company_name'] ?? '—');
            $table->addCell(2000)->addText($order['customer_name'] ?? '—');
            $table->addCell(1500)->addText($order['items_count']);
            $table->addCell(1500)->addText(number_format($order['total'], 2));
            $table->addCell(1500)->addText($order['status']);
            $table->addCell(2000)->addText($order['submitted_at'] ?? '—');
        }

        // Save to temporary file
        $filename = 'orders-report-' . now()->format('Y-m-d-H-i-s') . '.docx';
        $tempFile = storage_path('app/temp/' . $filename);
        
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get data for export (all records, no pagination)
     */
    protected function getExportData(array $filters): array
    {
        $query = Order::with([
            'company:id,first_name,last_name',
            'customer:id,first_name,last_name',
            'items',
        ])->withCount('items');

        $query = ReportFilters::apply($query, $filters);

        $orders = $query->latest('submitted_at')->get()->map(function ($order) {
            $total = $order->items->sum(function ($item) {
                return $item->qty * $item->price_snapshot;
            });

            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'company_name' => $order->company 
                    ? trim(($order->company->first_name ?? '') . ' ' . ($order->company->last_name ?? ''))
                    : null,
                'customer_name' => $order->customer 
                    ? trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))
                    : null,
                'items_count' => $order->items_count,
                'total' => (float) $total,
                'status' => $order->status,
                'submitted_at' => $order->submitted_at?->format('Y-m-d H:i'),
            ];
        })->toArray();

        $summary = $this->generateSummary($filters);

        return [
            'orders' => $orders,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
