<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;

class InvoiceSeeder extends Seeder
{
    /**
     * Seed invoices for approved orders (or other later states) that don't have an invoice yet.
     */
    public function run(): void
    {
        $orders = Order::whereIn('status', ['approved', 'preparing', 'shipped', 'delivered'])
            ->whereDoesntHave('invoice')
            ->inRandomOrder()
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $invoiceService = app(\App\Services\InvoiceService::class);

        foreach ($orders as $order) {
            try {
                $invoiceService->createInvoiceForOrder($order);
            } catch (\Exception $e) {
                // continue on error but log to stderr for debugging during local seeding
                fwrite(STDERR, "InvoiceSeeder: failed for order {$order->id}: {$e->getMessage()}\n");
            }
        }
    }
}
