<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Support\ReportFilters;
use App\Exports\ProductsReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ProductsReportService
{
    /**
     * Generate products report data
     */
    public function generate(array $filters, int $perPage = 15): array
    {
        $filters = ReportFilters::clean($filters);

        // Build query
        $query = Product::with([
            'company:id,first_name,last_name',
            'category:id,name',
        ]);

        // Apply filters
        $query = ReportFilters::apply($query, $filters);

        // Get paginated results
        $products = $query->latest('created_at')->paginate($perPage);

        // Transform data
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'company_name' => $product->company 
                    ? trim(($product->company->first_name ?? '') . ' ' . ($product->company->last_name ?? ''))
                    : null,
                'category' => $product->category?->name,
                'base_price' => (float) $product->base_price,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at?->format('Y-m-d H:i'),
            ];
        });

        // Generate summary
        $summary = $this->generateSummary($filters);

        return [
            'products' => $products,
            'summary' => $summary,
            'filters' => $filters,
        ];
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary(array $filters): array
    {
        $query = Product::query();
        $query = ReportFilters::apply($query, $filters);

        return [
            'total_count' => $query->count(),
            'active_count' => (clone $query)->where('is_active', true)->count(),
            'inactive_count' => (clone $query)->where('is_active', false)->count(),
            'avg_price' => (float) (clone $query)->avg('base_price') ?? 0,
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
            new ProductsReportExport($filters),
            'products-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    /**
     * Export to PDF
     */
    protected function exportPDF(array $filters)
    {
        $data = $this->getExportData($filters);
        
        $pdf = Pdf::loadView('reports.products-pdf', $data);
        
        return $pdf->download('products-report-' . now()->format('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Export to Word (DOCX)
     */
    protected function exportWord(array $filters)
    {
        $data = $this->getExportData($filters);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle('تقرير المنتجات / Products Report', 1);
        $section->addText('Generated: ' . now()->format('Y-m-d H:i:s'));
        $section->addTextBreak();

        $section->addTitle('Summary', 2);
        $section->addText('Total Products: ' . $data['summary']['total_count']);
        $section->addText('Active: ' . $data['summary']['active_count']);
        $section->addText('Average Price: ' . number_format($data['summary']['avg_price'], 2));
        $section->addTextBreak();

        $section->addTitle('Products', 2);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);

        $table->addRow();
        $table->addCell(3000)->addText('Name');
        $table->addCell(1500)->addText('SKU');
        $table->addCell(2000)->addText('Company');
        $table->addCell(2000)->addText('Category');
        $table->addCell(1500)->addText('Price');
        $table->addCell(1000)->addText('Status');

        foreach ($data['products'] as $product) {
            $table->addRow();
            $table->addCell(3000)->addText($product['name']);
            $table->addCell(1500)->addText($product['sku'] ?? '—');
            $table->addCell(2000)->addText($product['company_name'] ?? '—');
            $table->addCell(2000)->addText($product['category'] ?? '—');
            $table->addCell(1500)->addText(number_format($product['base_price'], 2));
            $table->addCell(1000)->addText($product['is_active'] ? 'Active' : 'Inactive');
        }

        $filename = 'products-report-' . now()->format('Y-m-d-H-i-s') . '.docx';
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
        $query = Product::with([
            'company:id,first_name,last_name',
            'category:id,name',
        ]);

        $query = ReportFilters::apply($query, $filters);

        $products = $query->latest('created_at')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'company_name' => $product->company 
                    ? trim(($product->company->first_name ?? '') . ' ' . ($product->company->last_name ?? ''))
                    : null,
                'category' => $product->category?->name,
                'base_price' => (float) $product->base_price,
                'is_active' => $product->is_active,
            ];
        })->toArray();

        $summary = $this->generateSummary($filters);

        return [
            'products' => $products,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
