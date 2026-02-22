#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;
use App\DTOs\InvoiceDTO;

echo "\n========= Testing Invoice Frontend Integration =========\n\n";

$invoices = Invoice::with(['order.company', 'order.customer'])
    ->latest()
    ->take(5)
    ->get();

if ($invoices->isEmpty()) {
    echo "⚠️  No invoices found. Run the invoice creation test first.\n";
    exit(0);
}

echo "Found {$invoices->count()} invoice(s) in database.\n\n";

echo "Testing InvoiceDTO transformation...\n";

foreach ($invoices as $invoice) {
    $dto = InvoiceDTO::fromModel($invoice);
    
    echo "✅ Invoice #{$dto->id} ({$dto->invoice_no})\n";
    echo "   Order: {$dto->order_no}\n";
    echo "   Company: {$dto->company_name}\n";
    echo "   Customer: {$dto->customer_name}\n";
    echo "   Total: {$dto->total_snapshot}\n";
    echo "   Status: {$dto->status}\n";
    
    $indexArray = $dto->toIndexArray();
    echo "   Index Array keys: " . implode(', ', array_keys($indexArray)) . "\n";
    
    $detailArray = $dto->toDetailArray();
    echo "   Detail Array keys: " . implode(', ', array_keys($detailArray)) . "\n";
    
    echo "\n";
}

echo "Testing full detail with items...\n";

$invoiceWithDetails = Invoice::with([
    'order.company',
    'order.customer',
    'items.product',
    'bonusItems.product',
])->first();

if ($invoiceWithDetails) {
    $dto = InvoiceDTO::fromModel($invoiceWithDetails);
    $detail = $dto->toDetailArray();
    
    echo "✅ Invoice #{$dto->invoice_no}\n";
    echo "   Items count: " . count($detail['items'] ?? []) . "\n";
    echo "   Bonus items count: " . count($detail['bonus_items'] ?? []) . "\n";
    echo "   Order info: " . ($detail['order'] ? 'Yes' : 'No') . "\n";
    
    if (!empty($detail['items'])) {
        echo "\n   Invoice Items:\n";
        foreach ($detail['items'] as $item) {
            echo "   - {$item['product_name']}: {$item['qty']} x {$item['unit_price_snapshot']} = {$item['line_total_snapshot']}\n";
        }
    }
    
    if (!empty($detail['bonus_items'])) {
        echo "\n   Bonus Items:\n";
        foreach ($detail['bonus_items'] as $item) {
            echo "   - {$item['product_name']}: {$item['qty']}\n";
        }
    }
}

echo "\n========= Frontend Routes Available =========\n\n";
echo "Admin:\n";
echo "  GET /admin/invoices       - List all invoices\n";
echo "  GET /admin/invoices/{id}  - Show invoice details\n\n";

echo "Company:\n";
echo "  GET /company/invoices     - List company invoices\n";
echo "  GET /company/invoices/{id} - Show invoice details\n\n";

echo "========= Test Complete =========\n\n";

echo "Next: Visit the routes above to see the UI!\n\n";
