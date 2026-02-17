<?php

namespace App\Services;

use App\Exceptions\StaleDataException;
use App\Exceptions\TamperingException;
use App\Repositories\OrderRepository;

class OfferVerifier
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    /**
     * Verify offer eligibility and staleness for all order items
     * 
     * @param array $orderItems Array of order items from request
     * @param int $customerId ID of the customer placing the order
     * @throws StaleDataException if offer is expired, inactive, or not started
     * @throws TamperingException if offer eligibility checks fail
     */
    public function verifyOffers(array $orderItems, int $customerId): void
    {
        foreach ($orderItems as $item) {
            // Explicitly check for null to handle both missing and null values
            if (($item['selected_offer_id'] ?? null) === null) {
                continue;
            }
            
            $offerId = $item['selected_offer_id'];
            $offer = $this->orderRepository->findOffer($offerId);
            
            if (!$offer) {
                throw new TamperingException("Offer {$offerId} not found");
            }
            
            // Stale check: offer must be active
            if ($offer->status !== 'active') {
                throw new StaleDataException(
                    "Offer {$offerId} is no longer active. Please refresh and try again."
                );
            }
            
            // Stale check: offer must be within date range
            $now = now();
            
            // start_at = null means offer is already started
            if ($offer->start_at !== null && $now < $offer->start_at) {
                throw new StaleDataException(
                    "Offer {$offerId} has not started yet. Please refresh and try again."
                );
            }
            
            // end_at = null means offer never expires
            if ($offer->end_at !== null && $now > $offer->end_at) {
                throw new StaleDataException(
                    "Offer {$offerId} has expired. Please refresh and try again."
                );
            }
            
            // Eligibility check: product must be in offer_items
            $offerItem = $this->orderRepository->findOfferItem($offerId, $item['product_id']);
            if (!$offerItem) {
                throw new TamperingException(
                    "Product {$item['product_id']} is not included in offer {$offerId}"
                );
            }
            
            // Eligibility check: qty must meet min_qty
            if ($item['qty'] < $offerItem->min_qty) {
                throw new TamperingException(
                    "Quantity {$item['qty']} does not meet minimum {$offerItem->min_qty} " .
                    "for offer {$offerId}"
                );
            }
            
            // Eligibility check: customer must be targeted for private offers
            if ($offer->scope === 'private') {
                $isTargeted = $this->orderRepository->isCustomerTargeted($offerId, $customerId);
                if (!$isTargeted) {
                    throw new TamperingException(
                        "Customer is not eligible for private offer {$offerId}"
                    );
                }
            }
        }
    }
}
