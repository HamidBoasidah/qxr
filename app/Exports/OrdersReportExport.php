<?php

namespace App\Exports;

use App\Models\Order;
use App\Support\ReportFilters;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        $query = Order::with([
            'company:id,first_name,last_name',
            'customer:id,first_name,last_name',
            'items',
        ])->withCount('items');

        $query = ReportFilters::apply($query, $this->filters);

        return $query->latest('submitted_at')->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Order No',
            'Company',
            'Customer',
            'Items Count',
            'Total',
            'Status',
            'Submitted At',
            'Approved At',
            'Delivered At',
        ];
    }

    /**
     * Map each row
     */
    public function map($order): array
    {
        $total = $order->items->sum(function ($item) {
            return $item->qty * $item->price_snapshot;
        });

        return [
            $order->order_no,
            $order->company 
                ? trim(($order->company->first_name ?? '') . ' ' . ($order->company->last_name ?? ''))
                : '—',
            $order->customer 
                ? trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? ''))
                : '—',
            $order->items_count,
            number_format((float) $total, 2),
            ucfirst($order->status),
            $order->submitted_at?->format('Y-m-d H:i'),
            $order->approved_at?->format('Y-m-d H:i') ?? '—',
            $order->delivered_at?->format('Y-m-d H:i') ?? '—',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:I1')->applyFromArray([
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
        $sheet->insertNewRowBefore(1, 5);

        // Title
        $sheet->setCellValue('A1', 'Orders Report');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
        ]);

        // Generated at
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));

        // Summary
        $sheet->setCellValue('A4', 'Summary');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        
        $sheet->setCellValue('A5', 'Total Orders: ' . $this->summary['total_count']);
        $sheet->setCellValue('B5', 'Total Revenue: ' . number_format($this->summary['total_revenue'], 2));
        $sheet->setCellValue('C5', 'Delivered: ' . $this->summary['delivered_count']);

        return [];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Orders Report';
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateSummary(): array
    {
        $query = Order::query();
        $query = ReportFilters::apply($query, $this->filters);

        $totals = [
            'total_count' => $query->count(),
            'delivered_count' => (clone $query)->where('status', 'delivered')->count(),
        ];

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
}
