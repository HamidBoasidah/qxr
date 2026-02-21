<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Offer;
use App\Repositories\OrderRepository;

class PricingCalculator
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    /**
     * Calculate all pricing for an order item based on product and selected offer
     * 
     * @param Product $product The product being ordered
     * @param int $qty The quantity being ordered
     * @param Offer|null $offer The selected offer (nullable)
     * @return array Pricing data structure with unit_price, discount_amount, final_total, and bonuses
     */
    public function calculate(Product $product, int $qty, ?Offer $offer): array
    {
        // Step 1: Round unit price first
        $unitPrice = $this->round($product->base_price);

        // No offer applied
        if ($offer === null) {
            $lineSubtotal = $this->round($qty * $unitPrice);
            return [
                'unit_price' => $unitPrice,
                'discount_amount' => 0.0,
                'final_total' => $lineSubtotal,
                'bonuses' => []
            ];
        }

        // Get offer item
        $offerItem = $this->orderRepository->findOfferItem($offer->id, $product->id);
        $multiplier = floor($qty / $offerItem->min_qty);

        if ($offerItem->reward_type === 'discount_percent') {
            // Step 2: Calculate line subtotal
            $lineSubtotal = $this->round($qty * $unitPrice);

            // Step 3: Calculate and round discount
            $discountPerBlock = $this->round(
                ($offerItem->min_qty * $unitPrice * $offerItem->discount_percent) / 100
            );
            $discountAmount = $this->round($discountPerBlock * $multiplier);

            // Step 4: Calculate final total
            $finalTotal = $this->round($lineSubtotal - $discountAmount);

            return [
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'final_total' => $finalTotal,
                'bonuses' => []
            ];
        }

        if ($offerItem->reward_type === 'discount_fixed') {
            // Step 2: Calculate line subtotal
            $lineSubtotal = $this->round($qty * $unitPrice);

            // Step 3: Calculate and round discount
            $discountAmount = $this->round($offerItem->discount_fixed * $multiplier);

            // Step 4: Calculate final total
            $finalTotal = $this->round($lineSubtotal - $discountAmount);

            return [
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'final_total' => $finalTotal,
                'bonuses' => []
            ];
        }

        if ($offerItem->reward_type === 'bonus_qty') {
            $bonusQty = (int)($multiplier * $offerItem->bonus_qty);
            // bonus_product_id comes from offer_items.bonus_product_id
            // If null, defaults to the ordered product itself
            // OfferSelector guarantees this offer_item exists for the selected offer
            $bonusProductId = $offerItem->bonus_product_id ?? $product->id;
            $bonusProduct = $this->orderRepository->findProduct($bonusProductId);

            // Step 2: Calculate line subtotal (no discount for bonus offers)
            $lineSubtotal = $this->round($qty * $unitPrice);

            return [
                'unit_price' => $unitPrice,
                'discount_amount' => 0.0,
                'final_total' => $lineSubtotal,
                'bonuses' => [
                    [
                        'bonus_product_id' => $bonusProduct->id,
                        'bonus_product_name' => $bonusProduct->name,
                        'bonus_qty' => $bonusQty,
                        'offer_title' => $offer->title
                    ]
                ]
            ];
        }

        $lineSubtotal = $this->round($qty * $unitPrice);
        return [
            'unit_price' => $unitPrice,
            'discount_amount' => 0.0,
            'final_total' => $lineSubtotal,
            'bonuses' => []
        ];
    }

    /**
     * Round a value to 2 decimal places using ROUND_HALF_UP
     * 
     * @param float $value The value to round
     * @return float The rounded value
     */
    private function round(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
