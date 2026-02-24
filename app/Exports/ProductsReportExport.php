<?php

namespace App\Exports;

use App\Models\Product;
use App\Support\ReportFilters;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $filters;
    protected array $summary;

    public function __construct(array $filters)
    {
        $this->filters = ReportFilters::clean($filters);
        $this->summary = $this->calculateSummary();
    }

    public function collection()
    {
        $query = Product::with([
            'company:id,first_name,last_name',
            'category:id,name',
        ]);

        $query = ReportFilters::apply($query, $this->filters);

        return $query->latest('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'SKU',
            'Company',
            'Category',
            'Base Price',
            'Status',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku ?? '—',
            $product->company 
                ? trim(($product->company->first_name ?? '') . ' ' . ($product->company->last_name ?? ''))
                : '—',
            $product->category?->name ?? '—',
            number_format((float) $product->base_price, 2),
            $product->is_active ? 'Active' : 'Inactive',
            $product->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        $sheet->insertNewRowBefore(1, 5);
        $sheet->setCellValue('A1', 'Products Report');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16]]);
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));
        
        $sheet->setCellValue('A4', 'Summary');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->setCellValue('A5', 'Total: ' . $this->summary['total_count']);
        $sheet->setCellValue('B5', 'Active: ' . $this->summary['active_count']);
        $sheet->setCellValue('C5', 'Avg Price: ' . number_format($this->summary['avg_price'], 2));

        return [];
    }

    public function title(): string
    {
        return 'Products Report';
    }

    protected function calculateSummary(): array
    {
        $query = Product::query();
        $query = ReportFilters::apply($query, $this->filters);

        return [
            'total_count' => $query->count(),
            'active_count' => (clone $query)->where('is_active', true)->count(),
            'avg_price' => (float) (clone $query)->avg('base_price') ?? 0,
        ];
    }
}
