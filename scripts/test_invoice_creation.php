#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;

echo "\n========= Creating Test Order and Invoice =========\n\n";

DB::beginTransaction();

try {
    $company = User::where('user_type', 'company')->where('is_active', true)->first();
    $customer = User::where('user_type', 'customer')->where('is_active', true)->first();
    
    if (!$company || !$customer) {
        echo "❌ Need active company and customer users\n";
        exit(1);
    }
    
    $products = Product::where('company_user_id', $company->id)
        ->where('is_active', true)
        ->whereNotNull('base_price')
        ->where('base_price', '>', 0)
        ->limit(3)
        ->get();
    
    if ($products->count() < 2) {
        echo "❌ Need at least 2 active products with valid prices for this company\n";
        exit(1);
    }
    
    echo "Creating test order...\n";
    
    $order = Order::create([
        'order_no' => 'ORD-TEST-' . time(),
        'company_user_id' => $company->id,
        'customer_user_id' => $customer->id,
        'status' => 'pending',
        'submitted_at' => now(),
    ]);
    
    foreach ($products as $product) {
        $qty = rand(2, 10);
        $unitPrice = (float) $product->base_price;
        $discount = rand(0, 20);
        $lineTotal = ($unitPrice * $qty) - $discount;
        
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => $qty,
            'unit_price_snapshot' => $unitPrice,
            'discount_amount_snapshot' => $discount,
            'final_line_total_snapshot' => $lineTotal,
        ]);
    }
    
    $order->refresh();
    echo "✅ Order #{$order->id} created with {$order->items->count()} items\n\n";
    
    echo "Approving order and creating invoice...\n";
    $order->update([
        'status' => 'approved',
        'approved_at' => now(),
        'approved_by_user_id' => $company->id,
    ]);
    
    $invoiceService = app(InvoiceService::class);
    $invoice = $invoiceService->createInvoiceForOrder($order);
    
    echo "✅ Invoice created!\n";
    echo "   Invoice No: {$invoice->invoice_no}\n";
    echo "   Order ID: {$invoice->order_id}\n";
    echo "   Subtotal: {$invoice->subtotal_snapshot}\n";
    echo "   Discount: {$invoice->discount_total_snapshot}\n";
    echo "   Total: {$invoice->total_snapshot}\n";
    echo "   Invoice Items: {$invoice->items()->count()}\n";
    echo "   Status: {$invoice->status}\n";
    echo "   Issued At: {$invoice->issued_at}\n\n";
    
    echo "Testing duplicate prevention...\n";
    $invoice2 = $invoiceService->createInvoiceForOrder($order);
    
    if ($invoice2->id === $invoice->id) {
        echo "✅ Duplicate prevention works! Same invoice returned (ID: {$invoice->id})\n\n";
    } else {
        echo "❌ ERROR: Created duplicate invoice (ID1: {$invoice->id}, ID2: {$invoice2->id})\n\n";
    }
    
    echo "Verifying invoice items...\n";
    $invoiceItems = $invoice->items;
    foreach ($invoiceItems as $item) {
        echo "  - {$item->description_snapshot}: {$item->qty} x {$item->unit_price_snapshot} = {$item->line_total_snapshot}\n";
    }
    
    DB::commit();
    
    echo "\n========= Test Successful! =========\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
