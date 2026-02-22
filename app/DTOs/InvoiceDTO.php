<?php

namespace App\DTOs;

use App\Models\Invoice;

class InvoiceDTO
{
    public function __construct(
        public int $id,
        public string $invoice_no,
        public int $order_id,
        public ?string $order_no,
        public ?string $company_name,
        public ?string $customer_name,
        public float $subtotal_snapshot,
        public float $discount_total_snapshot,
        public float $total_snapshot,
        public ?string $issued_at,
        public string $status,
        public ?string $created_at,
        public ?array $items = null,
        public ?array $bonusItems = null,
        public ?array $order = null,
    ) {
    }

    public static function fromModel(Invoice $invoice): self
    {
        $orderNo = null;
        $companyName = null;
        $customerName = null;
        $order = null;

        if ($invoice->relationLoaded('order') && $invoice->order) {
            $ord = $invoice->order;
            $orderNo = $ord->order_no;

            if ($ord->relationLoaded('company') && $ord->company) {
                $companyName = $ord->company->first_name . ' ' . $ord->company->last_name;
            }

            if ($ord->relationLoaded('customer') && $ord->customer) {
                $customerName = $ord->customer->first_name . ' ' . $ord->customer->last_name;
            }

            $order = [
                'id' => $ord->id,
                'order_no' => $ord->order_no,
                'status' => $ord->status,
                'submitted_at' => $ord->submitted_at?->format('Y-m-d H:i'),
                'approved_at' => $ord->approved_at?->format('Y-m-d H:i'),
                'delivered_at' => $ord->delivered_at?->format('Y-m-d H:i'),
            ];
        }

        $items = null;
        if ($invoice->relationLoaded('items')) {
            $items = $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? $item->description_snapshot,
                    'description_snapshot' => $item->description_snapshot,
                    'qty' => $item->qty,
                    'unit_price_snapshot' => $item->unit_price_snapshot,
                    'line_total_snapshot' => $item->line_total_snapshot,
                ];
            })->toArray();
        }

        $bonusItems = null;
        if ($invoice->relationLoaded('bonusItems')) {
            $bonusItems = $invoice->bonusItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? $item->note,
                    'qty' => $item->qty,
                    'note' => $item->note,
                ];
            })->toArray();
        }

        return new self(
            id: $invoice->id,
            invoice_no: $invoice->invoice_no,
            order_id: $invoice->order_id,
            order_no: $orderNo,
            company_name: $companyName,
            customer_name: $customerName,
            subtotal_snapshot: (float) $invoice->subtotal_snapshot,
            discount_total_snapshot: (float) $invoice->discount_total_snapshot,
            total_snapshot: (float) $invoice->total_snapshot,
            issued_at: $invoice->issued_at?->format('Y-m-d H:i'),
            status: $invoice->status,
            created_at: $invoice->created_at?->format('Y-m-d H:i'),
            items: $items,
            bonusItems: $bonusItems,
            order: $order,
        );
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'order_id' => $this->order_id,
            'order_no' => $this->order_no,
            'company_name' => $this->company_name,
            'customer_name' => $this->customer_name,
            'total_snapshot' => $this->total_snapshot,
            'issued_at' => $this->issued_at,
            'status' => $this->status,
        ];
    }

    public function toDetailArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'order_id' => $this->order_id,
            'order_no' => $this->order_no,
            'company_name' => $this->company_name,
            'customer_name' => $this->customer_name,
            'subtotal_snapshot' => $this->subtotal_snapshot,
            'discount_total_snapshot' => $this->discount_total_snapshot,
            'total_snapshot' => $this->total_snapshot,
            'issued_at' => $this->issued_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'items' => $this->items,
            'bonus_items' => $this->bonusItems,
            'order' => $this->order,
        ];
    }
}
