<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\OrderRepository;

class PreviewValidator
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OfferSelector $offerSelector,
        private PricingCalculator $pricingCalculator
    ) {
    }

    /**
     * Revalidate preview data before confirmation
     * 
     * Compares preview data with current database state to detect:
     * - Price changes (tolerance: 0.01 after rounding)
     * - Best offer changes (different offer selected, offer expired/inactive/not started, etc.)
     * 
     * @param array $previewData The cached preview data
     * @param User $customer The authenticated customer
     * @return array Validation result with 'valid' boolean and 'changes' array
     */
    public function revalidate(array $previewData, User $customer): array
    {
        $changes = [];

        foreach ($previewData['items'] as $previewItem) {
            $product = $this->orderRepository->findProduct($previewItem['product_id']);

            // Check if price changed
            $currentPrice = $this->round($product->base_price);
            $previewPrice = $this->round($previewItem['unit_price']);

            if (abs($currentPrice - $previewPrice) > 0.01) {
                $changes[] = [
                    'type' => 'price_changed',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'preview_price' => $previewPrice,
                    'current_price' => $currentPrice
                ];
            }

            // Re-run best offer selection
            $currentBestOffer = $this->offerSelector->selectBestOffer(
                $product,
                $previewItem['qty'],
                $customer->id
            );

            $previewOfferId = $previewItem['selected_offer_id'];
            $currentOfferId = $currentBestOffer?->id;

            // Check if best offer changed
            if ($previewOfferId !== $currentOfferId) {
                // Determine change reason
                $changeReason = $this->determineOfferChangeReason(
                    $previewOfferId,
                    $currentOfferId,
                    $customer->id
                );

                // Get offer details for client UX
                $previewOffer = $previewOfferId ? $this->orderRepository->findOffer($previewOfferId) : null;

                $changes[] = [
                    'type' => 'best_offer_changed',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'previous_offer_id' => $previewOfferId,
                    'previous_offer_title' => $previewOffer?->title,
                    'previous_reward_type' => $previewOffer ? $this->getOfferRewardType($previewOffer, $product->id) : null,
                    'current_offer_id' => $currentOfferId,
                    'current_offer_title' => $currentBestOffer?->title,
                    'current_reward_type' => $currentBestOffer ? $this->getOfferRewardType($currentBestOffer, $product->id) : null,
                    'change_reason' => $changeReason
                ];
            }
        }

        return [
            'valid' => empty($changes),
            'changes' => $changes
        ];
    }

    /**
     * Determine the reason why an offer changed
     * 
     * @param int|null $previewOfferId The offer ID from preview
     * @param int|null $currentOfferId The current best offer ID
     * @param int $customerId The customer's user ID
     * @return string The change reason enum value
     */
    private function determineOfferChangeReason(?int $previewOfferId, ?int $currentOfferId, int $customerId): string
    {
        // No offer before, offer now
        if ($previewOfferId === null && $currentOfferId !== null) {
            return 'new_better_offer';
        }

        // Offer before, no offer now
        if ($previewOfferId !== null && $currentOfferId === null) {
            $previewOffer = $this->orderRepository->findOffer($previewOfferId);

            if ($previewOffer->status !== 'active') {
                return 'became_inactive';
            }

            $now = now();
            if ($previewOffer->end_at !== null && $now > $previewOffer->end_at) {
                return 'expired';
            }

            if ($previewOffer->start_at !== null && $now < $previewOffer->start_at) {
                return 'not_started';
            }

            if ($previewOffer->scope === 'private') {
                $isTargeted = $this->orderRepository->isCustomerTargeted($previewOfferId, $customerId);
                if (!$isTargeted) {
                    return 'targeting_changed';
                }
            }

            return 'removed';
        }

        // Different offer now
        return 'new_better_offer';
    }

    /**
     * Get the reward type for an offer from its offer_item
     * 
     * @param \App\Models\Offer $offer The offer
     * @param int $productId The product ID
     * @return string|null The reward type (discount_percent, discount_fixed, bonus_qty)
     */
    private function getOfferRewardType(\App\Models\Offer $offer, int $productId): ?string
    {
        $offerItem = $this->orderRepository->findOfferItem($offer->id, $productId);
        return $offerItem?->reward_type;
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
