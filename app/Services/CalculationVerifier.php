<?php

namespace App\Services;

use App\Exceptions\TamperingException;
use App\Repositories\OrderRepository;

class CalculationVerifier
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    /**
     * Verify client-submitted calculations match server expectations
     * 
     * @param array $orderItems Array of order items from request
     * @param array $orderItemBonuses Array of bonuses from request
     * @throws TamperingException if calculations don't match
     */
    public function verifyCalculations(array $orderItems, array $orderItemBonuses): void
    {
        // Build bonus lookup by order_item_index
        $bonusesByIndex = [];
        foreach ($orderItemBonuses as $bonus) {
            $index = $bonus['order_item_index'];
            $bonusesByIndex[$index] = $bonus;
        }
        
        foreach ($orderItems as $index => $item) {
            $this->verifyItemCalculation($item, $bonusesByIndex[$index] ?? null);
        }
    }

    /**
     * Verify calculations for a single order item
     * 
     * @param array $item Order item data
     * @param array|null $bonus Bonus data if present
     * @throws TamperingException if calculations don't match
     */
    private function verifyItemCalculation(array $item, ?array $bonus): void
    {
        $qty = $item['qty'];
        $unitPrice = $this->round($item['unit_price_snapshot']);
        $submittedDiscount = $this->round($item['discount_amount_snapshot']);
        $submittedTotal = $this->round($item['final_line_total_snapshot']);
        
        // Explicitly check for null to handle both missing and null values
        if (($item['selected_offer_id'] ?? null) === null) {
            // No offer: discount must be zero, no bonuses
            if ($submittedDiscount != 0) {
                throw new TamperingException(
                    "Product {$item['product_id']} has no offer but discount is non-zero"
                );
            }
            if ($bonus) {
                throw new TamperingException(
                    "Product {$item['product_id']} has no offer but bonus is present"
                );
            }
            
            $expectedTotal = $this->round($qty * $unitPrice);
            if (abs($expectedTotal - $submittedTotal) > 0.01) {
                throw new TamperingException(
                    "Final total mismatch for product {$item['product_id']}: " .
                    "expected {$expectedTotal}, got {$submittedTotal}"
                );
            }
            return;
        }
        
        // Offer is selected: verify discount/bonus calculations
        $offerId = $item['selected_offer_id'];
        $offer = $this->orderRepository->findOffer($offerId);
        $offerItem = $this->orderRepository->findOfferItem($offerId, $item['product_id']);
        
        $multiplier = floor($qty / $offerItem->min_qty);
        
        if ($offer->reward_type === 'percentage_discount') {
            $this->verifyPercentageDiscount($item, $offer, $offerItem, $multiplier, $bonus);
        } elseif ($offer->reward_type === 'fixed_discount') {
            $this->verifyFixedDiscount($item, $offer, $multiplier, $bonus);
        } elseif ($offer->reward_type === 'bonus_qty') {
            $this->verifyBonusQty($item, $offer, $multiplier, $bonus);
        }
    }

    /**
     * Verify percentage discount calculations
     * 
     * @param array $item Order item data
     * @param object $offer Offer model
     * @param object $offerItem OfferItem model
     * @param int $multiplier Quantity multiplier (floor(qty / min_qty))
     * @param array|null $bonus Bonus data if present
     * @throws TamperingException if calculations don't match
     */
    private function verifyPercentageDiscount(
        array $item,
        $offer,
        $offerItem,
        int $multiplier,
        ?array $bonus
    ): void {
        if ($bonus) {
            throw new TamperingException(
                "Product {$item['product_id']} has percentage_discount offer " .
                "but bonus is present"
            );
        }
        
        $qty = $item['qty'];
        $unitPrice = $this->round($item['unit_price_snapshot']);
        
        // Calculate discount per block: (min_qty × unitPrice × percent) / 100
        $discountPerBlock = $this->round(
            ($offerItem->min_qty * $unitPrice * $offer->reward_value) / 100
        );
        
        // Total discount: discountPerBlock × multiplier
        $expectedDiscount = $this->round($discountPerBlock * $multiplier);
        $submittedDiscount = $this->round($item['discount_amount_snapshot']);
        
        if (abs($expectedDiscount - $submittedDiscount) > 0.01) {
            throw new TamperingException(
                "Discount mismatch for product {$item['product_id']}: " .
                "expected {$expectedDiscount}, got {$submittedDiscount}"
            );
        }
        
        $subtotal = $this->round($qty * $unitPrice);
        $expectedTotal = $this->round($subtotal - $expectedDiscount);
        $submittedTotal = $this->round($item['final_line_total_snapshot']);
        
        if (abs($expectedTotal - $submittedTotal) > 0.01) {
            throw new TamperingException(
                "Final total mismatch for product {$item['product_id']}: " .
                "expected {$expectedTotal}, got {$submittedTotal}"
            );
        }
    }

    /**
     * Verify fixed discount calculations
     * 
     * @param array $item Order item data
     * @param object $offer Offer model
     * @param int $multiplier Quantity multiplier (floor(qty / min_qty))
     * @param array|null $bonus Bonus data if present
     * @throws TamperingException if calculations don't match
     */
    private function verifyFixedDiscount(
        array $item,
        $offer,
        int $multiplier,
        ?array $bonus
    ): void {
        if ($bonus) {
            throw new TamperingException(
                "Product {$item['product_id']} has fixed_discount offer " .
                "but bonus is present"
            );
        }
        
        $qty = $item['qty'];
        $unitPrice = $this->round($item['unit_price_snapshot']);
        $subtotal = $this->round($qty * $unitPrice);
        
        $expectedDiscount = $this->round($multiplier * $offer->reward_value);
        $submittedDiscount = $this->round($item['discount_amount_snapshot']);
        
        if (abs($expectedDiscount - $submittedDiscount) > 0.01) {
            throw new TamperingException(
                "Discount mismatch for product {$item['product_id']}: " .
                "expected {$expectedDiscount}, got {$submittedDiscount}"
            );
        }
        
        $expectedTotal = $this->round($subtotal - $expectedDiscount);
        $submittedTotal = $this->round($item['final_line_total_snapshot']);
        
        if (abs($expectedTotal - $submittedTotal) > 0.01) {
            throw new TamperingException(
                "Final total mismatch for product {$item['product_id']}: " .
                "expected {$expectedTotal}, got {$submittedTotal}"
            );
        }
    }

    /**
     * Verify bonus quantity calculations
     * 
     * @param array $item Order item data
     * @param object $offer Offer model
     * @param int $multiplier Quantity multiplier (floor(qty / min_qty))
     * @param array|null $bonus Bonus data if present
     * @throws TamperingException if calculations don't match
     */
    private function verifyBonusQty(
        array $item,
        $offer,
        int $multiplier,
        ?array $bonus
    ): void {
        $submittedDiscount = $this->round($item['discount_amount_snapshot']);
        
        if ($submittedDiscount != 0) {
            throw new TamperingException(
                "Product {$item['product_id']} has bonus_qty offer " .
                "but discount is non-zero"
            );
        }
        
        // Bonus_qty offers MUST have exactly one bonus
        if (!$bonus) {
            throw new TamperingException(
                "Product {$item['product_id']} has bonus_qty offer " .
                "but no bonus is present"
            );
        }
        
        $expectedBonusQty = $multiplier * $offer->reward_value;
        $submittedBonusQty = $bonus['bonus_qty'];
        
        if ($expectedBonusQty != $submittedBonusQty) {
            throw new TamperingException(
                "Bonus quantity mismatch for product {$item['product_id']}: " .
                "expected {$expectedBonusQty}, got {$submittedBonusQty}"
            );
        }
        
        $qty = $item['qty'];
        $unitPrice = $this->round($item['unit_price_snapshot']);
        $expectedTotal = $this->round($qty * $unitPrice);
        $submittedTotal = $this->round($item['final_line_total_snapshot']);
        
        if (abs($expectedTotal - $submittedTotal) > 0.01) {
            throw new TamperingException(
                "Final total mismatch for product {$item['product_id']}: " .
                "expected {$expectedTotal}, got {$submittedTotal}"
            );
        }
    }

    /**
     * Round value to 2 decimal places using ROUND_HALF_UP
     * 
     * @param float $value Value to round
     * @return float Rounded value
     */
    private function round(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
