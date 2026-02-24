<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Support\ReportFilters;
use App\Exports\InvoicesReportExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class InvoicesReportService
{
    /**
     * Generate invoices report data
     */
    public function generate(array $filters, int $perPage = 15): array
    {
        $filters = ReportFilters::clean($filters);

        // Build query
        $query = Invoice::with([
            'order:id,order_no,company_user_id,customer_user_id',
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
        ]);

        // Apply filters
        $query = ReportFilters::apply($query, $filters);

        // Get paginated results
        $invoices = $query->latest('issued_at')->paginate($perPage);

        // Transform data
        $invoices->getCollection()->transform(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'order_no' => $invoice->order?->order_no,
                'company_name' => $invoice->order?->company 
                    ? trim(($invoice->order->company->first_name ?? '') . ' ' . ($invoice->order->company->last_name ?? ''))
                    : null,
                'customer_name' => $invoice->order?->customer 
                    ? trim(($invoice->order->customer->first_name ?? '') . ' ' . ($invoice->order->customer->last_name ?? ''))
                    : null,
                'subtotal' => (float) $invoice->subtotal_snapshot,
                'discount' => (float) $invoice->discount_total_snapshot,
                'total' => (float) $invoice->total_snapshot,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->format('Y-m-d H:i'),
                'created_at' => $invoice->created_at?->format('Y-m-d H:i'),
            ];
        });

        // Generate summary
        $summary = $this->generateSummary($filters);

        return [
            'invoices' => $invoices,
            'summary' => $summary,
            'filters' => $filters,
        ];
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary(array $filters): array
    {
        $query = Invoice::query();
        $query = ReportFilters::apply($query, $filters);

        $totalsQuery = clone $query;

        $totals = [
            'total_count' => $query->count(),
            'paid_count' => (clone $query)->where('status', 'paid')->count(),
            'unpaid_count' => (clone $query)->where('status', 'unpaid')->count(),
            'void_count' => (clone $query)->where('status', 'void')->count(),
        ];

        // Revenue calculations
        $revenue = $totalsQuery->selectRaw('
            SUM(CASE WHEN status = "paid" THEN total_snapshot ELSE 0 END) as paid_revenue,
            SUM(CASE WHEN status = "unpaid" THEN total_snapshot ELSE 0 END) as unpaid_amount,
            SUM(subtotal_snapshot) as total_subtotal,
            SUM(discount_total_snapshot) as total_discounts,
            SUM(total_snapshot) as total_amount
        ')->first();

        return array_merge($totals, [
            'paid_revenue' => (float) ($revenue->paid_revenue ?? 0),
            'unpaid_amount' => (float) ($revenue->unpaid_amount ?? 0),
            'total_subtotal' => (float) ($revenue->total_subtotal ?? 0),
            'total_discounts' => (float) ($revenue->total_discounts ?? 0),
            'total_amount' => (float) ($revenue->total_amount ?? 0),
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
            new InvoicesReportExport($filters),
            'invoices-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    /**
     * Export to PDF
     */
    protected function exportPDF(array $filters)
    {
        $data = $this->getExportData($filters);
        
        $pdf = Pdf::loadView('reports.invoices-pdf', $data);
        
        return $pdf->download('invoices-report-' . now()->format('Y-m-d-H-i-s') . '.pdf');
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
        $section->addTitle('تقرير الفواتير / Invoices Report', 1);
        $section->addText('Generated: ' . now()->format('Y-m-d H:i:s'));
        $section->addTextBreak();

        // Summary
        $section->addTitle('Summary', 2);
        $section->addText('Total Invoices: ' . $data['summary']['total_count']);
        $section->addText('Paid Revenue: ' . number_format($data['summary']['paid_revenue'], 2));
        $section->addText('Unpaid Amount: ' . number_format($data['summary']['unpaid_amount'], 2));
        $section->addText('Total Discounts: ' . number_format($data['summary']['total_discounts'], 2));
        $section->addTextBreak();

        // Table
        $section->addTitle('Invoices', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        // Header
        $table->addRow();
        $table->addCell(2000)->addText('Invoice No');
        $table->addCell(2000)->addText('Order No');
        $table->addCell(2000)->addText('Company');
        $table->addCell(2000)->addText('Customer');
        $table->addCell(1500)->addText('Total');
        $table->addCell(1500)->addText('Status');
        $table->addCell(2000)->addText('Issued At');

        // Data rows
        foreach ($data['invoices'] as $invoice) {
            $table->addRow();
            $table->addCell(2000)->addText($invoice['invoice_no']);
            $table->addCell(2000)->addText($invoice['order_no'] ?? '—');
            $table->addCell(2000)->addText($invoice['company_name'] ?? '—');
            $table->addCell(2000)->addText($invoice['customer_name'] ?? '—');
            $table->addCell(1500)->addText(number_format($invoice['total'], 2));
            $table->addCell(1500)->addText($invoice['status']);
            $table->addCell(2000)->addText($invoice['issued_at'] ?? '—');
        }

        // Save to temporary file
        $filename = 'invoices-report-' . now()->format('Y-m-d-H-i-s') . '.docx';
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
        $query = Invoice::with([
            'order:id,order_no,company_user_id,customer_user_id',
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
        ]);

        $query = ReportFilters::apply($query, $filters);

        $invoices = $query->latest('issued_at')->get()->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'order_no' => $invoice->order?->order_no,
                'company_name' => $invoice->order?->company 
                    ? trim(($invoice->order->company->first_name ?? '') . ' ' . ($invoice->order->company->last_name ?? ''))
                    : null,
                'customer_name' => $invoice->order?->customer 
                    ? trim(($invoice->order->customer->first_name ?? '') . ' ' . ($invoice->order->customer->last_name ?? ''))
                    : null,
                'subtotal' => (float) $invoice->subtotal_snapshot,
                'discount' => (float) $invoice->discount_total_snapshot,
                'total' => (float) $invoice->total_snapshot,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->format('Y-m-d H:i'),
            ];
        })->toArray();

        $summary = $this->generateSummary($filters);

        return [
            'invoices' => $invoices,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
