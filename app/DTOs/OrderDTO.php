<?php

namespace App\DTOs;

use App\Models\Order;

class OrderDTO extends BaseDTO
{
    public function __construct(
        public int $id,
        public string $order_no,
        public string $status,
        public ?string $submitted_at,
        public ?string $notes,
        public array $items,
        public float $subtotal,
        public float $total_discount,
        public float $final_total,
        public ?array $company = null,
        public ?array $customer = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {
    }

    /**
     * Create OrderDTO from Order model
     */
    public static function fromModel(Order $order): self
    {
        $items = self::formatItems($order);
        [$subtotal, $totalDiscount, $finalTotal] = self::calculateTotals($order);

        return new self(
            $order->id,
            $order->order_no,
            $order->status,
            $order->submitted_at?->toIso8601String(),
            $order->notes_customer,
            $items,
            round($subtotal, 2, PHP_ROUND_HALF_UP),
            round($totalDiscount, 2, PHP_ROUND_HALF_UP),
            round($finalTotal, 2, PHP_ROUND_HALF_UP),
            self::formatUser($order->company),
            self::formatUser($order->customer),
            $order->created_at?->toIso8601String(),
            $order->updated_at?->toIso8601String()
        );
    }

    /**
     * Map limited user fields (id, first_name, last_name)
     */
    private static function formatUser($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ];
    }

    /**
     * Format order items with products and bonuses
     */
    private static function formatItems(Order $order): array
    {
        return $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'qty' => $item->qty,
                'unit_price' => round($item->unit_price_snapshot, 2, PHP_ROUND_HALF_UP),
                'discount_amount' => round($item->discount_amount_snapshot, 2, PHP_ROUND_HALF_UP),
                'final_total' => round($item->final_line_total_snapshot, 2, PHP_ROUND_HALF_UP),
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
    }

    /**
     * Calculate totals using rounded snapshots
     */
    private static function calculateTotals(Order $order): array
    {
        $lineSubtotals = $order->items->map(function ($item) {
            return round($item->qty * $item->unit_price_snapshot, 2, PHP_ROUND_HALF_UP);
        });

        $subtotal = round($lineSubtotals->sum(), 2, PHP_ROUND_HALF_UP);
        $totalDiscount = round($order->items->sum('discount_amount_snapshot'), 2, PHP_ROUND_HALF_UP);
        $finalTotal = round($order->items->sum('final_line_total_snapshot'), 2, PHP_ROUND_HALF_UP);

        return [$subtotal, $totalDiscount, $finalTotal];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'notes' => $this->notes,
            'company' => $this->company,
            'customer' => $this->customer,
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'total_discount' => $this->total_discount,
            'final_total' => $this->final_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
