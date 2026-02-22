<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceBonusItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function createInvoiceForOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            if ($order->relationLoaded('invoice')) {
                $existingInvoice = $order->invoice;
            } else {
                $existingInvoice = Invoice::where('order_id', $order->id)->first();
            }

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $order->loadMissing(['items.product', 'items.bonuses.bonusProduct']);

            $subtotal = 0;
            $discountTotal = 0;

            $invoice = Invoice::create([
                'invoice_no' => $this->generateInvoiceNumber(),
                'order_id' => $order->id,
                'issued_at' => $order->approved_at ?? now(),
                'status' => 'unpaid',
                'subtotal_snapshot' => 0,
                'discount_total_snapshot' => 0,
                'total_snapshot' => 0,
            ]);

            foreach ($order->items as $orderItem) {
                $lineTotal = $orderItem->final_line_total_snapshot;
                $lineSubtotal = round($orderItem->qty * $orderItem->unit_price_snapshot, 2);
                
                $subtotal += $lineSubtotal;
                $discountTotal += $orderItem->discount_amount_snapshot;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $orderItem->product_id,
                    'description_snapshot' => $orderItem->product->name ?? null,
                    'qty' => $orderItem->qty,
                    'unit_price_snapshot' => $orderItem->unit_price_snapshot,
                    'line_total_snapshot' => $lineTotal,
                ]);

                if ($orderItem->bonuses->isNotEmpty()) {
                    foreach ($orderItem->bonuses as $bonus) {
                        InvoiceBonusItem::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $bonus->bonus_product_id,
                            'qty' => $bonus->bonus_qty,
                            'note' => $bonus->bonusProduct->name ?? null,
                        ]);
                    }
                }
            }

            $total = round($subtotal - $discountTotal, 2);

            $invoice->update([
                'subtotal_snapshot' => $subtotal,
                'discount_total_snapshot' => $discountTotal,
                'total_snapshot' => $total,
            ]);

            return $invoice->fresh();
        });
    }

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        return "INV-{$date}-{$random}";
    }
}
