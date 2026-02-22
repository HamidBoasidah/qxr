#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Invoice;
use App\Services\InvoiceService;

echo "\n========= Testing Invoice Generation =========\n\n";

$invoiceService = app(InvoiceService::class);

$pendingOrders = Order::where('status', 'pending')
    ->whereDoesntHave('invoice')
    ->with(['items.product', 'items.bonuses'])
    ->limit(3)
    ->get();

if ($pendingOrders->isEmpty()) {
    echo "⚠️  No pending orders found without invoices.\n";
    exit(0);
}

echo "Found {$pendingOrders->count()} pending order(s) to test.\n\n";

foreach ($pendingOrders as $order) {
    echo "Testing Order #{$order->id} (Order No: {$order->order_no})\n";
    echo "  Status: {$order->status}\n";
    echo "  Items: {$order->items->count()}\n";
    
    $order->status = 'approved';
    $order->approved_at = now();
    $order->save();
    
    try {
        $invoice = $invoiceService->createInvoiceForOrder($order);
        
        echo "  ✅ Invoice created successfully!\n";
        echo "     Invoice No: {$invoice->invoice_no}\n";
        echo "     Subtotal: {$invoice->subtotal_snapshot}\n";
        echo "     Discount: {$invoice->discount_total_snapshot}\n";
        echo "     Total: {$invoice->total_snapshot}\n";
        echo "     Invoice Items: {$invoice->items()->count()}\n";
        echo "     Bonus Items: {$invoice->bonusItems()->count()}\n";
        
        $duplicateAttempt = $invoiceService->createInvoiceForOrder($order);
        
        if ($duplicateAttempt->id === $invoice->id) {
            echo "  ✅ Duplicate prevention works! Same invoice returned.\n";
        } else {
            echo "  ❌ ERROR: Created duplicate invoice!\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error: {$e->getMessage()}\n";
    }
    
    echo "\n";
}

echo "========= Test Complete =========\n\n";
