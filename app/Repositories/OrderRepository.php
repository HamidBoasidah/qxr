<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\OfferTarget;
use App\Models\User;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a company by ID
     */
    public function findCompany(int $companyId): ?User
    {
        return User::find($companyId);
    }

    /**
     * Find a product by ID
     */
    public function findProduct(int $productId): ?Product
    {
        return Product::find($productId);
    }

    /**
     * Find an offer by ID
     */
    public function findOffer(int $offerId): ?Offer
    {
        return Offer::find($offerId);
    }

    /**
     * Find an offer item by offer ID and product ID
     */
    public function findOfferItem(int $offerId, int $productId): ?OfferItem
    {
        return OfferItem::where('offer_id', $offerId)
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * Check if a customer is targeted for a private offer
     */
    public function isCustomerTargeted(int $offerId, int $customerId): bool
    {
        return OfferTarget::where('offer_id', $offerId)
            ->where('target_type', 'customer')
            ->where('target_id', $customerId)
            ->exists();
    }

    /**
     * Generate a unique order number
     * Format: ORD-YYYYMMDDHHMMSS-XXXX (e.g., ORD-20260217103045-A3F2)
     * Uses timestamp + random for concurrency safety
     */
    private function generateOrderNumber(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        return "ORD-{$timestamp}-{$random}";
    }

    /**
     * Create order with all related records in a database transaction
     * 
     * @param int $customerId The authenticated customer's user ID
     * @param array $data The validated order data
     * @return Order The created order with loaded relationships
     */
    public function createOrderWithTransaction(int $customerId, array $data): Order
    {
        return DB::transaction(function () use ($customerId, $data) {
            // Create order header
            $order = Order::create([
                'company_user_id' => $data['company_id'],
                'customer_user_id' => $customerId,
                'order_no' => $this->generateOrderNumber(),
                'status' => 'pending',
                'notes_customer' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'approved_at' => null,
                'delivered_at' => null
            ]);

            // Create order items
            foreach ($data['order_items'] as $index => $itemData) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'unit_price_snapshot' => $itemData['unit_price_snapshot'],
                    'discount_amount_snapshot' => $itemData['discount_amount_snapshot'],
                    'final_line_total_snapshot' => $itemData['final_line_total_snapshot'],
                    'selected_offer_id' => $itemData['selected_offer_id'] ?? null
                ]);

                // Create bonuses if present
                if (isset($data['order_item_bonuses'])) {
                    foreach ($data['order_item_bonuses'] as $bonusData) {
                        if ($bonusData['order_item_index'] === $index) {
                            // Use selected_offer_id from the order item (server-verified)
                            // NOT from the bonus request payload
                            OrderItemBonus::create([
                                'order_item_id' => $orderItem->id,
                                'bonus_product_id' => $bonusData['bonus_product_id'],
                                'bonus_qty' => $bonusData['bonus_qty'],
                                'offer_id' => $itemData['selected_offer_id']
                            ]);
                        }
                    }
                }
            }

            // Create status log
            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'changed_by_user_id' => $customerId,
                'note' => null,
                'changed_at' => now()
            ]);

            return $order->load(['items.product', 'items.bonuses.bonusProduct', 'items.bonuses.offer']);
        });
    }
}
