<?php

namespace App\DTOs;

use App\Models\Order;

class OrderDetailDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $order_no,
        public string $status,
        public ?string $submitted_at,
        public ?string $approved_at,
        public ?string $delivered_at,
        public ?string $company_name,
        public ?string $customer_name,
        public ?string $notes_customer,
        public ?string $notes_company,
        public array $items,
        public float $subtotal,
        public float $total_discount,
        public float $final_total,
        public array $status_logs = [],
        public ?array $delivery_address = null,
    ) {
    }

    public static function fromModel(Order $order): self
    {
        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product?->name,
                'unit' => $item->product?->unit_name,
                'qty' => $item->qty,
                'unit_price' => round($item->unit_price_snapshot, 2),
                'discount_amount' => round($item->discount_amount_snapshot, 2),
                'final_total' => round($item->final_line_total_snapshot, 2),
                'selected_offer_title' => $item->selectedOffer?->title,
                'bonuses' => $item->bonuses->map(function ($bonus) {
                    return [
                        'bonus_product_name' => $bonus->bonusProduct?->name,
                        'bonus_qty' => $bonus->bonus_qty,
                        'offer_title' => $bonus->offer?->title,
                    ];
                })->toArray(),
            ];
        })->toArray();

        $subtotal = $order->items->sum(fn ($item) => $item->qty * $item->unit_price_snapshot);
        $totalDiscount = $order->items->sum('discount_amount_snapshot');
        $finalTotal = $order->items->sum('final_line_total_snapshot');

        $statusLogs = $order->relationLoaded('statusLogs')
            ? $order->statusLogs->map(function ($log) {
                return [
                    'from_status' => $log->from_status,
                    'to_status' => $log->to_status,
                    'changed_by' => trim(($log->changedBy?->first_name ?? '') . ' ' . ($log->changedBy?->last_name ?? '')),
                    'note' => $log->note,
                    'changed_at' => optional($log->changed_at)->toDateTimeString(),
                ];
            })->toArray()
            : [];

        $companyName = $order->company?->companyProfile?->company_name
            ?? trim(($order->company?->first_name ?? '') . ' ' . ($order->company?->last_name ?? ''))
            ?: null;
        $customerName = trim(($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? '')) ?: null;

        $deliveryAddress = null;
        if ($order->relationLoaded('deliveryAddress') && $order->deliveryAddress) {
            $addr = $order->deliveryAddress;

            $gov = $addr->governorate;
            $district = $addr->district;
            $area = $addr->area;

            $deliveryAddress = [
                'id'           => $addr->id,
                'label'        => $addr->label,
                'address'      => $addr->address,
                'governorate'  => $gov?->name_ar ?? $gov?->name_en ?? null,
                'district'     => $district?->name_ar ?? $district?->name_en ?? null,
                'area'         => $area?->name_ar ?? $area?->name_en ?? null,
                'lat'          => $addr->lat,
                'lng'          => $addr->lang,
            ];
        }

        return new self(
            $order->id,
            $order->order_no,
            $order->status,
            optional($order->submitted_at)->toDateTimeString(),
            optional($order->approved_at)->toDateTimeString(),
            optional($order->delivered_at)->toDateTimeString(),
            $companyName,
            $customerName,
            $order->notes_customer,
            $order->notes_company,
            $items,
            round($subtotal, 2),
            round($totalDiscount, 2),
            round($finalTotal, 2),
            $statusLogs,
            $deliveryAddress,
        );
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'company_name' => $this->company_name,
            'customer_name' => $this->customer_name,
            'items_count' => count($this->items),
            'subtotal' => $this->subtotal,
            'total_discount' => $this->total_discount,
            'final_total' => $this->final_total,
        ];
    }

    public function toDetailArray(): array
    {
        return [
            'id'               => $this->id,
            'order_no'         => $this->order_no,
            'status'           => $this->status,
            'submitted_at'     => $this->submitted_at,
            'approved_at'      => $this->approved_at,
            'delivered_at'     => $this->delivered_at,
            'company_name'     => $this->company_name,
            'customer_name'    => $this->customer_name,
            'notes_customer'   => $this->notes_customer,
            'notes_company'    => $this->notes_company,
            'delivery_address' => $this->delivery_address,
            'items'            => $this->items,
            'subtotal'         => $this->subtotal,
            'total_discount'   => $this->total_discount,
            'final_total'      => $this->final_total,
            'status_logs'      => $this->status_logs,
        ];
    }
}
