<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Repositories\OrderRepository;

class OfferSelector
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    /**
     * Select the best offer for a product based on eligibility and effective value
     * 
     * @param Product $product The product to find offers for
     * @param int $qty The quantity being ordered
     * @param int $customerId The customer's user ID
     * @return Offer|null The best offer or null if none eligible
     */
    public function selectBestOffer(Product $product, int $qty, int $customerId): ?Offer
    {
        // Load all offers that include this product
        $offers = $this->orderRepository->findOffersForProduct($product->id);

        $eligibleOffers = [];

        foreach ($offers as $offer) {
            // Check if offer is active
            if ($offer->status !== 'active') {
                continue;
            }

            // Check date range
            $now = now();
            if ($offer->start_at !== null && $now < $offer->start_at) {
                continue;
            }
            if ($offer->end_at !== null && $now > $offer->end_at) {
                continue;
            }

            // Get offer item for this product
            // This MUST exist because findOffersForProduct only returns offers
            // that have an offer_item for this product_id
            $offerItem = $this->orderRepository->findOfferItem($offer->id, $product->id);
            if (!$offerItem) {
                continue; // Safety check, should never happen
            }

            // Calculate multiplier
            $multiplier = floor($qty / $offerItem->min_qty);

            // Skip if multiplier is 0 (qty < min_qty)
            if ($multiplier == 0) {
                continue;
            }

            // Check private offer targeting
            if ($offer->scope === 'private') {
                $isTargeted = $this->orderRepository->isCustomerTargeted($offer->id, $customerId);
                if (!$isTargeted) {
                    continue;
                }
            }

            // Calculate effective value
            $effectiveValue = $this->calculateEffectiveValue(
                $offer,
                $offerItem,
                $product,
                $multiplier
            );

            $eligibleOffers[] = [
                'offer' => $offer,
                'offer_item' => $offerItem,
                'multiplier' => $multiplier,
                'effective_value' => $effectiveValue
            ];
        }

        // No eligible offers
        if (empty($eligibleOffers)) {
            return null;
        }

        // Sort by effective value (descending), then by tie-breakers
        usort($eligibleOffers, function ($a, $b) {
            // Compare effective values
            if ($a['effective_value'] > $b['effective_value']) {
                return -1;
            }
            if ($a['effective_value'] < $b['effective_value']) {
                return 1;
            }

            // Tie-breaker 1: Prefer discount offers over bonus offers
            $aIsDiscount = in_array($a['offer_item']->reward_type, ['discount_percent', 'discount_fixed']);
            $bIsDiscount = in_array($b['offer_item']->reward_type, ['discount_percent', 'discount_fixed']);
            if ($aIsDiscount && !$bIsDiscount) {
                return -1;
            }
            if (!$aIsDiscount && $bIsDiscount) {
                return 1;
            }

            // Tie-breaker 2: Prefer offer with earlier end_at (null = infinite, goes last)
            if ($a['offer']->end_at === null && $b['offer']->end_at !== null) {
                return 1;
            }
            if ($a['offer']->end_at !== null && $b['offer']->end_at === null) {
                return -1;
            }
            if ($a['offer']->end_at !== null && $b['offer']->end_at !== null) {
                if ($a['offer']->end_at < $b['offer']->end_at) {
                    return -1;
                }
                if ($a['offer']->end_at > $b['offer']->end_at) {
                    return 1;
                }
            }

            // Tie-breaker 3: Prefer offer with lowest offer_id
            return $a['offer']->id <=> $b['offer']->id;
        });

        return $eligibleOffers[0]['offer'];
    }

    /**
     * Calculate the effective value of an offer
     * 
     * @param Offer $offer The offer to calculate value for
     * @param OfferItem $offerItem The offer item with min_qty and bonus_product_id
     * @param Product $product The product being ordered
     * @param int $multiplier The quantity multiplier
     * @return float The effective value rounded to 2 decimal places
     */
    private function calculateEffectiveValue(
        Offer $offer,
        OfferItem $offerItem,
        Product $product,
        int $multiplier
    ): float {
        $unitPrice = $this->round($product->base_price);

        if ($offerItem->reward_type === 'discount_percent') {
            // (min_qty × unit_price × discount_percent / 100) × multiplier
            $discountPerBlock = $this->round(
                ($offerItem->min_qty * $unitPrice * $offerItem->discount_percent) / 100
            );
            return $this->round($discountPerBlock * $multiplier);
        }

        if ($offerItem->reward_type === 'discount_fixed') {
            // discount_fixed × multiplier
            return $this->round($offerItem->discount_fixed * $multiplier);
        }

        if ($offerItem->reward_type === 'bonus_qty') {
            // (bonus_qty × bonus_product_price) × multiplier
            // Note: bonus_product_id comes from offer_items.bonus_product_id
            // The bonus product may differ from the ordered product
            $bonusProduct = $this->orderRepository->findProduct($offerItem->bonus_product_id ?? $product->id);
            $bonusProductPrice = $this->round($bonusProduct->base_price);
            return $this->round($offerItem->bonus_qty * $bonusProductPrice * $multiplier);
        }

        return 0.0;
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
