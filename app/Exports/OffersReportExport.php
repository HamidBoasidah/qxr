<?php

namespace App\Exports;

use App\Models\Offer;
use App\Support\ReportFilters;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OffersReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
        $query = Offer::with([
            'company:id,first_name,last_name',
        ])->withCount(['items', 'targets']);

        $query = ReportFilters::apply($query, $this->filters);

        return $query->latest('start_at')->get();
    }

    public function headings(): array
    {
        return [
            'Title',
            'Company',
            'Scope',
            'Status',
            'Items Count',
            'Targets Count',
            'Start Date',
            'End Date',
        ];
    }

    public function map($offer): array
    {
        return [
            $offer->title,
            $offer->company 
                ? trim(($offer->company->first_name ?? '') . ' ' . ($offer->company->last_name ?? ''))
                : '—',
            ucfirst($offer->scope),
            ucfirst($offer->status),
            $offer->items_count,
            $offer->targets_count,
            $offer->start_at?->format('Y-m-d'),
            $offer->end_at?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        $sheet->insertNewRowBefore(1, 5);
        $sheet->setCellValue('A1', 'Offers Report');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16]]);
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));
        
        $sheet->setCellValue('A4', 'Summary');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->setCellValue('A5', 'Total: ' . $this->summary['total_count']);
        $sheet->setCellValue('B5', 'Active: ' . $this->summary['active_count']);
        $sheet->setCellValue('C5', 'Public: ' . $this->summary['public_count']);

        return [];
    }

    public function title(): string
    {
        return 'Offers Report';
    }

    protected function calculateSummary(): array
    {
        $query = Offer::query();
        $query = ReportFilters::apply($query, $this->filters);

        return [
            'total_count' => $query->count(),
            'active_count' => (clone $query)->where('status', 'active')->count(),
            'public_count' => (clone $query)->where('scope', 'public')->count(),
        ];
    }
}
