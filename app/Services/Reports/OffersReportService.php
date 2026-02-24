<?php

namespace App\Services\Reports;

use App\Models\Offer;
use App\Support\ReportFilters;
use App\Exports\OffersReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class OffersReportService
{
    /**
     * Generate offers report data
     */
    public function generate(array $filters, int $perPage = 15): array
    {
        $filters = ReportFilters::clean($filters);

        // Build query
        $query = Offer::with([
            'company:id,first_name,last_name',
            'items',
            'targets',
        ])
        ->withCount(['items', 'targets']);

        // Apply filters
        $query = ReportFilters::apply($query, $filters);

        // Get paginated results
        $offers = $query->latest('start_at')->paginate($perPage);

        // Transform data
        $offers->getCollection()->transform(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'company_name' => $offer->company 
                    ? trim(($offer->company->first_name ?? '') . ' ' . ($offer->company->last_name ?? ''))
                    : null,
                'scope' => $offer->scope,
                'status' => $offer->status,
                'items_count' => $offer->items_count,
                'targets_count' => $offer->targets_count,
                'start_at' => $offer->start_at?->format('Y-m-d'),
                'end_at' => $offer->end_at?->format('Y-m-d'),
                'created_at' => $offer->created_at?->format('Y-m-d H:i'),
            ];
        });

        // Generate summary
        $summary = $this->generateSummary($filters);

        return [
            'offers' => $offers,
            'summary' => $summary,
            'filters' => $filters,
        ];
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary(array $filters): array
    {
        $query = Offer::query();
        $query = ReportFilters::apply($query, $filters);

        return [
            'total_count' => $query->count(),
            'active_count' => (clone $query)->where('status', 'active')->count(),
            'inactive_count' => (clone $query)->where('status', 'inactive')->count(),
            'expired_count' => (clone $query)->where('status', 'expired')->count(),
            'public_count' => (clone $query)->where('scope', 'public')->count(),
            'private_count' => (clone $query)->where('scope', 'private')->count(),
        ];
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
            new OffersReportExport($filters),
            'offers-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    /**
     * Export to PDF
     */
    protected function exportPDF(array $filters)
    {
        $data = $this->getExportData($filters);
        
        $pdf = Pdf::loadView('reports.offers-pdf', $data);
        
        return $pdf->download('offers-report-' . now()->format('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Export to Word (DOCX)
     */
    protected function exportWord(array $filters)
    {
        $data = $this->getExportData($filters);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle('تقرير العروض / Offers Report', 1);
        $section->addText('Generated: ' . now()->format('Y-m-d H:i:s'));
        $section->addTextBreak();

        $section->addTitle('Summary', 2);
        $section->addText('Total Offers: ' . $data['summary']['total_count']);
        $section->addText('Active: ' . $data['summary']['active_count']);
        $section->addText('Public: ' . $data['summary']['public_count']);
        $section->addTextBreak();

        $section->addTitle('Offers', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        $table->addRow();
        $table->addCell(3000)->addText('Title');
        $table->addCell(2000)->addText('Company');
        $table->addCell(1500)->addText('Scope');
        $table->addCell(1500)->addText('Status');
        $table->addCell(1000)->addText('Items');
        $table->addCell(2000)->addText('Start Date');
        $table->addCell(2000)->addText('End Date');

        foreach ($data['offers'] as $offer) {
            $table->addRow();
            $table->addCell(3000)->addText($offer['title']);
            $table->addCell(2000)->addText($offer['company_name'] ?? '—');
            $table->addCell(1500)->addText($offer['scope']);
            $table->addCell(1500)->addText($offer['status']);
            $table->addCell(1000)->addText($offer['items_count']);
            $table->addCell(2000)->addText($offer['start_at'] ?? '—');
            $table->addCell(2000)->addText($offer['end_at'] ?? '—');
        }

        $filename = 'offers-report-' . now()->format('Y-m-d-H-i-s') . '.docx';
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
        $query = Offer::with([
            'company:id,first_name,last_name',
            'items',
            'targets',
        ])->withCount(['items', 'targets']);

        $query = ReportFilters::apply($query, $filters);

        $offers = $query->latest('start_at')->get()->map(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'company_name' => $offer->company 
                    ? trim(($offer->company->first_name ?? '') . ' ' . ($offer->company->last_name ?? ''))
                    : null,
                'scope' => $offer->scope,
                'status' => $offer->status,
                'items_count' => $offer->items_count,
                'targets_count' => $offer->targets_count,
                'start_at' => $offer->start_at?->format('Y-m-d'),
                'end_at' => $offer->end_at?->format('Y-m-d'),
            ];
        })->toArray();

        $summary = $this->generateSummary($filters);

        return [
            'offers' => $offers,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
