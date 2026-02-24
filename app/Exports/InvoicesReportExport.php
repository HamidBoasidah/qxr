<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Support\ReportFilters;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $filters;
    protected array $summary;

    public function __construct(array $filters)
    {
        $this->filters = ReportFilters::clean($filters);
        $this->summary = $this->calculateSummary();
    }

    /**
     * Get the data collection
     */
    public function collection()
    {
        $query = Invoice::with([
            'order:id,order_no,company_user_id,customer_user_id',
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
        ]);

        $query = ReportFilters::apply($query, $this->filters);

        return $query->latest('issued_at')->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Invoice No',
            'Order No',
            'Company',
            'Customer',
            'Subtotal',
            'Discount',
            'Total',
            'Status',
            'Issued At',
            'Created At',
        ];
    }

    /**
     * Map each row
     */
    public function map($invoice): array
    {
        return [
            $invoice->invoice_no,
            $invoice->order?->order_no ?? '—',
            $invoice->order?->company 
                ? trim(($invoice->order->company->first_name ?? '') . ' ' . ($invoice->order->company->last_name ?? ''))
                : '—',
            $invoice->order?->customer 
                ? trim(($invoice->order->customer->first_name ?? '') . ' ' . ($invoice->order->customer->last_name ?? ''))
                : '—',
            number_format((float) $invoice->subtotal_snapshot, 2),
            number_format((float) $invoice->discount_total_snapshot, 2),
            number_format((float) $invoice->total_snapshot, 2),
            ucfirst($invoice->status),
            $invoice->issued_at?->format('Y-m-d H:i'),
            $invoice->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        // Add summary section above the data
        $sheet->insertNewRowBefore(1, 6);

        // Title
        $sheet->setCellValue('A1', 'Invoices Report');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
        ]);

        // Generated at
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));

        // Summary
        $sheet->setCellValue('A4', 'Summary');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        
        $sheet->setCellValue('A5', 'Total Invoices: ' . $this->summary['total_count']);
        $sheet->setCellValue('B5', 'Paid: ' . $this->summary['paid_count']);
        $sheet->setCellValue('C5', 'Unpaid: ' . $this->summary['unpaid_count']);
        $sheet->setCellValue('D5', 'Void: ' . $this->summary['void_count']);
        
        $sheet->setCellValue('A6', 'Paid Revenue: ' . number_format($this->summary['paid_revenue'], 2));
        $sheet->setCellValue('B6', 'Unpaid Amount: ' . number_format($this->summary['unpaid_amount'], 2));
        $sheet->setCellValue('C6', 'Total Discounts: ' . number_format($this->summary['total_discounts'], 2));

        return [];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Invoices Report';
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateSummary(): array
    {
        $query = Invoice::query();
        $query = ReportFilters::apply($query, $this->filters);

        $totals = [
            'total_count' => $query->count(),
            'paid_count' => (clone $query)->where('status', 'paid')->count(),
            'unpaid_count' => (clone $query)->where('status', 'unpaid')->count(),
            'void_count' => (clone $query)->where('status', 'void')->count(),
        ];

        $revenue = (clone $query)->selectRaw('
            SUM(CASE WHEN status = "paid" THEN total_snapshot ELSE 0 END) as paid_revenue,
            SUM(CASE WHEN status = "unpaid" THEN total_snapshot ELSE 0 END) as unpaid_amount,
            SUM(discount_total_snapshot) as total_discounts
        ')->first();

        return array_merge($totals, [
            'paid_revenue' => (float) ($revenue->paid_revenue ?? 0),
            'unpaid_amount' => (float) ($revenue->unpaid_amount ?? 0),
            'total_discounts' => (float) ($revenue->total_discounts ?? 0),
        ]);
    }
}
