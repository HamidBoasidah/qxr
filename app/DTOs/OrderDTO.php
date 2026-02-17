<?php

namespace App\DTOs;

use App\Models\Order;

class OrderDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $order_no,
        public string $status,
        public string $submitted_at,
        public ?string $notes,
        public array $items,
        public float $subtotal,
        public float $total_discount,
        public float $final_total
    ) {
    }

    /**
     * Create OrderDTO from Order model
     * 
     * @param Order $order Order model with loaded relationships
     * @return array Array representation of the order
     */
    public static function fromModel(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'qty' => $item->qty,
                'unit_price' => round($item->unit_price_snapshot, 2),
                'discount_amount' => round($item->discount_amount_snapshot, 2),
                'final_total' => round($item->final_line_total_snapshot, 2),
                'selected_offer_id' => $item->selected_offer_id,
                'bonuses' => $item->bonuses->map(function ($bonus) {
                    return [
                        'bonus_product_id' => $bonus->bonus_product_id,
                        'bonus_product_name' => $bonus->bonusProduct->name,
                        'bonus_qty' => $bonus->bonus_qty,
                        'offer_title' => $bonus->offer->title ?? null
                    ];
                })->toArray()
            ];
        })->toArray();

        $subtotal = $order->items->sum(function ($item) {
            return $item->qty * $item->unit_price_snapshot;
        });

        $totalDiscount = $order->items->sum('discount_amount_snapshot');
        $finalTotal = $order->items->sum('final_line_total_snapshot');

        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'submitted_at' => $order->submitted_at->toIso8601String(),
            'notes' => $order->notes_customer,
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'final_total' => round($finalTotal, 2)
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'notes' => $this->notes,
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'total_discount' => $this->total_discount,
            'final_total' => $this->final_total
        ];
    }
}
