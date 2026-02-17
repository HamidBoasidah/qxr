<?php

namespace App\Services;

use App\Exceptions\StaleDataException;
use App\Repositories\OrderRepository;

class PriceVerifier
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    /**
     * Verify that unit_price_snapshot matches current product prices
     * 
     * @param array $orderItems Array of order items from request
     * @throws StaleDataException if price differs by more than 0.01 after rounding
     */
    public function verifyPrices(array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $product = $this->orderRepository->findProduct($item['product_id']);
            
            $currentPrice = $this->round($product->base_price);
            $snapshotPrice = $this->round($item['unit_price_snapshot']);
            
            if (abs($currentPrice - $snapshotPrice) > 0.01) {
                throw new StaleDataException(
                    "Price for product {$item['product_id']} has changed. " .
                    "Please refresh and try again."
                );
            }
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
