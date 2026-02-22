<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use App\Exceptions\PreviewNotFoundException;
use App\Exceptions\PreviewOwnershipException;
use App\Exceptions\PreviewInvalidatedException;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\DTOs\OrderDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private PriceVerifier $priceVerifier,
        private OfferVerifier $offerVerifier,
        private CalculationVerifier $calculationVerifier,
        private OfferSelector $offerSelector,
        private PricingCalculator $pricingCalculator,
        private PreviewValidator $previewValidator,
        private InvoiceService $invoiceService
    ) {
    }

    /**
     * Preview an order with calculated pricing and offers
     * 
     * @param array $data Validated preview request data
     * @param User $customer Authenticated customer user
     * @return array Preview data with preview_token
     * @throws AuthorizationException if user is not a customer or products don't belong to company
     * @throws ValidationException if company is inactive or products are invalid
     */
    public function previewOrder(array $data, User $customer): array
    {
        // Verify authorization
        $this->verifyAuthorization($customer);
        
        // Verify company is active
        $company = $this->orderRepository->findCompany($data['company_id']);
        if (!$company || !$company->is_active) {
            throw new ValidationException('Company is not active');
        }
        
        // Verify all products belong to company and are active
        $this->verifyProducts($data['items'], $data['company_id']);
        
        // Calculate preview
        $calculatedItems = [];
        foreach ($data['items'] as $item) {
            $product = $this->orderRepository->findProduct($item['product_id']);
            
            // Select best offer
            $bestOffer = $this->offerSelector->selectBestOffer(
                $product,
                $item['qty'],
                $customer->id
            );
            
            // Calculate pricing
            $pricing = $this->pricingCalculator->calculate(
                $product,
                $item['qty'],
                $bestOffer
            );
            
            $calculatedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'qty' => $item['qty'],
                'unit_price' => $pricing['unit_price'],
                'discount_amount' => $pricing['discount_amount'],
                'final_total' => $pricing['final_total'],
                'selected_offer_id' => $bestOffer?->id,
                'offer_title' => $bestOffer?->title,
                'bonuses' => $pricing['bonuses']
            ];
        }
        
        // Calculate totals
        // Calculate line_subtotal for each item first, then sum those rounded values
        $lineSubtotals = array_map(function($item) {
            return round($item['qty'] * $item['unit_price'], 2, PHP_ROUND_HALF_UP);
        }, $calculatedItems);
        $subtotal = round(array_sum($lineSubtotals), 2, PHP_ROUND_HALF_UP);
        $totalDiscount = round(array_sum(array_column($calculatedItems, 'discount_amount')), 2, PHP_ROUND_HALF_UP);
        $finalTotal = round(array_sum(array_column($calculatedItems, 'final_total')), 2, PHP_ROUND_HALF_UP);
        
        // Generate preview token
        $previewToken = $this->generatePreviewToken();
        
        // Store preview data in cache (15 minutes)
        $previewData = [
            'preview_token' => $previewToken,
            'customer_user_id' => $customer->id,
            'company_id' => $data['company_id'],
            'notes' => $data['notes'] ?? null,
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'total_discount' => $totalDiscount,
            'final_total' => $finalTotal,
            'created_at' => now()->toIso8601String()
        ];
        
        Cache::put("preview:{$previewToken}", $previewData, now()->addMinutes(15));
        
        return $previewData;
    }

    /**
     * Confirm an order from a preview token
     * 
     * @param string $previewToken The preview token from cache
     * @param User $customer Authenticated customer user
     * @return array OrderDTO array representation
     * @throws PreviewNotFoundException if preview not found or expired
     * @throws PreviewOwnershipException if preview belongs to another customer
     * @throws PreviewInvalidatedException if preview data is no longer valid
     */
    public function confirmOrder(string $previewToken, User $customer, int $deliveryAddressId = 0): array
    {
        // Retrieve preview data
        $previewData = Cache::get("preview:{$previewToken}");
        
        if (!$previewData) {
            throw new PreviewNotFoundException('Preview not found or expired');
        }
        
        // Verify ownership
        if ($previewData['customer_user_id'] !== $customer->id) {
            // Delete token to prevent further attempts
            Cache::forget("preview:{$previewToken}");
            throw new PreviewOwnershipException('This preview belongs to another customer');
        }
        
        // Revalidate and persist in SAME transaction to avoid race conditions
        try {
            $order = DB::transaction(function () use ($previewData, $customer, $deliveryAddressId) {
                // Revalidate preview using CURRENT database data
                $revalidationResult = $this->previewValidator->revalidate($previewData, $customer);
                
                if (!$revalidationResult['valid']) {
                    throw new PreviewInvalidatedException(
                        'Preview is no longer valid. Please re-preview your order.',
                        $revalidationResult['changes']
                    );
                }
                
                // Recalculate using CURRENT data (authoritative values)
                $recalculatedItems = [];
                foreach ($previewData['items'] as $item) {
                    $product = $this->orderRepository->findProduct($item['product_id']);
                    
                    // Re-select best offer
                    $bestOffer = $this->offerSelector->selectBestOffer(
                        $product,
                        $item['qty'],
                        $customer->id
                    );
                    
                    // Recalculate pricing (authoritative)
                    $pricing = $this->pricingCalculator->calculate(
                        $product,
                        $item['qty'],
                        $bestOffer
                    );
                    
                    $recalculatedItems[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'qty' => $item['qty'],
                        'unit_price' => $pricing['unit_price'],
                        'discount_amount' => $pricing['discount_amount'],
                        'final_total' => $pricing['final_total'],
                        'selected_offer_id' => $bestOffer?->id,
                        'bonuses' => $pricing['bonuses']
                    ];
                }
                
                // Create order with recalculated values (NOT preview cache values)
                $orderData = [
                    'company_id'          => $previewData['company_id'],
                    'notes_customer'      => $previewData['notes'],
                    'delivery_address_id' => $deliveryAddressId ?: null,
                    'order_items' => array_map(function($item) {
                        return [
                            'product_id' => $item['product_id'],
                            'qty' => $item['qty'],
                            'unit_price_snapshot' => $item['unit_price'],
                            'discount_amount_snapshot' => $item['discount_amount'],
                            'final_line_total_snapshot' => $item['final_total'],
                            'selected_offer_id' => $item['selected_offer_id']
                        ];
                    }, $recalculatedItems),
                    'order_item_bonuses' => $this->extractBonuses($recalculatedItems)
                ];
                
                return $this->orderRepository->createOrderWithTransaction(
                    $customer->id,
                    $orderData
                );
            });
            
            // Success: delete token (single-use)
            Cache::forget("preview:{$previewToken}");
            
            return OrderDTO::fromModel($order)->toArray();
            
        } catch (PreviewInvalidatedException $e) {
            // HTTP 409: Keep token so client can re-preview
            // Do NOT delete token here
            throw $e;
        } catch (\Exception $e) {
            // Any other error: delete token to prevent replay attacks
            Cache::forget("preview:{$previewToken}");
            throw $e;
        }
    }

    /**
     * Create a new order with verification
     * 
     * @param array $data Validated order data from request
     * @param User $customer Authenticated customer user
     * @return array OrderDTO array representation
     * @throws AuthorizationException if user is not a customer or products don't belong to company
     * @throws ValidationException if company is inactive or products are invalid
     */
    public function createOrder(array $data, User $customer): array
    {
        // Verify user has customer role
        $this->verifyAuthorization($customer);
        
        // Verify company exists and is active
        $company = $this->orderRepository->findCompany($data['company_id']);
        if (!$company) {
            throw new ValidationException('Company not found');
        }
        if (!$company->is_active) {
            throw new ValidationException('Company is not active');
        }
        
        // Verify all products belong to company and are active
        $this->verifyProducts($data['order_items'], $data['company_id']);
        
        // Call PriceVerifier to verify prices
        $this->priceVerifier->verifyPrices($data['order_items']);
        
        // Call OfferVerifier to verify offers
        $this->offerVerifier->verifyOffers($data['order_items'], $customer->id);
        
        // Call CalculationVerifier to verify calculations
        $this->calculationVerifier->verifyCalculations(
            $data['order_items'],
            $data['order_item_bonuses'] ?? []
        );
        
        // Call OrderRepository to create order in transaction
        $order = $this->orderRepository->createOrderWithTransaction(
            $customer->id,
            $data
        );
        
        // Transform to OrderDTO and return
        return OrderDTO::fromModel($order)->toArray();
    }

    /**
     * Verify user is a customer
     * 
     * @param User $customer User to verify
     * @throws AuthorizationException if user is not a customer
     */
    private function verifyAuthorization(User $customer): void
    {
        if ($customer->user_type !== 'customer') {
            throw new AuthorizationException('Only customers can create orders');
        }
    }

    /**
     * Verify all products belong to company and are active
     * 
     * @param array $orderItems Array of order items from request
     * @param int $companyId Company ID from request
     * @throws AuthorizationException if product doesn't belong to company
     * @throws ValidationException if product is not active
     */
    private function verifyProducts(array $orderItems, int $companyId): void
    {
        foreach ($orderItems as $item) {
            $product = $this->orderRepository->findProduct($item['product_id']);
            
            if (!$product) {
                throw new ValidationException("Product {$item['product_id']} not found");
            }
            
            if ($product->company_user_id !== $companyId) {
                throw new AuthorizationException(
                    "Product {$item['product_id']} does not belong to company {$companyId}"
                );
            }
            
            if (!$product->is_active) {
                throw new ValidationException("Product {$item['product_id']} is not active");
            }
        }
    }

    /**
     * Generate a unique preview token
     * Format: PV-YYYYMMDD-XXXX
     * 
     * @return string The generated preview token
     */
    private function generatePreviewToken(): string
    {
        do {
            $date = now()->format('Ymd');
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
            $token = "PV-{$date}-{$random}";
        } while (Cache::has("preview:{$token}"));
        
        return $token;
    }

    /**
     * Extract bonuses from recalculated items for order creation
     * 
     * @param array $recalculatedItems Array of recalculated items
     * @return array Array of bonuses with order_item_index
     */
    private function extractBonuses(array $recalculatedItems): array
    {
        $bonuses = [];
        
        foreach ($recalculatedItems as $index => $item) {
            if (!empty($item['bonuses'])) {
                foreach ($item['bonuses'] as $bonus) {
                    $bonuses[] = [
                        'order_item_index' => $index,
                        'bonus_product_id' => $bonus['bonus_product_id'],
                        'bonus_qty' => $bonus['bonus_qty']
                    ];
                }
            }
        }
        
        return $bonuses;
    }

    /**
     * Update an order
     * 
     * @param int $orderId Order ID
     * @param array $data Validated update data (status, notes, etc.)
     * @param User $user The user making the update
     * @return \App\Models\Order Updated order model
     * @throws \App\Exceptions\ValidationException if status transition is invalid
     */
    public function updateOrder(int $orderId, array $data, User $user): \App\Models\Order
    {
        return DB::transaction(function () use ($orderId, $data, $user) {
            $order = $this->orderRepository->findOrFail($orderId);
            
            // Track status change for logging
            $oldStatus = $order->status;
            $newStatus = $data['status'] ?? $oldStatus;
            
            // Validate status transition if status is being changed
            if ($newStatus !== $oldStatus) {
                $this->validateStatusTransition($oldStatus, $newStatus);
            }
            
            // Update order
            $order = $this->orderRepository->updateModel($order, $data);
            
            // If status changed, create status log
            if ($newStatus !== $oldStatus) {
                \App\Models\OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'changed_by_user_id' => $user->id,
                    'note' => $data['notes_company'] ?? null,
                    'changed_at' => now()
                ]);
            }
            
            return $order;
        });
    }

    /**
     * Validate status transition is logical
     * 
     * @param string $fromStatus Current status
     * @param string $toStatus New status
     * @throws \App\Exceptions\ValidationException if transition is invalid
     */
    private function validateStatusTransition(string $fromStatus, string $toStatus): void
    {
        // Define allowed transitions
        $allowedTransitions = [
            'pending' => ['approved', 'rejected', 'cancelled'],
            'approved' => ['preparing', 'shipped', 'delivered', 'cancelled'],
            'preparing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [], // Final state
            'rejected' => [], // Final state
            'cancelled' => [], // Final state
        ];
        
        // Check if transition is allowed
        if (!isset($allowedTransitions[$fromStatus])) {
            throw new ValidationException("الحالة الحالية غير صحيحة");
        }
        
        if (!in_array($toStatus, $allowedTransitions[$fromStatus], true)) {
            throw new ValidationException(
                "لا يمكن تغيير حالة الطلب من '{$fromStatus}' إلى '{$toStatus}'. "
                . "الحالات المسموحة: " . implode(', ', $allowedTransitions[$fromStatus] ?: ['لا يوجد'])
            );
        }
    }

    /**
     * الانتقالات المسموح بها للشركة من كل حالة
     */
    private function companyAllowedTransitions(): array
    {
        return [
            'pending'   => ['approved', 'rejected'],
            'approved'  => ['preparing', 'cancelled'],
            'preparing' => ['shipped',   'cancelled'],
            'shipped'   => ['delivered'],
        ];
    }

    /**
     * تغيير حالة الطلب من قِبَل الشركة
     *
     * @param int    $orderId   معرّف الطلب
     * @param string $newStatus الحالة الجديدة
     * @param int    $companyId معرّف المستخدم (الشركة)
     * @param string|null $note ملاحظة اختيارية
     * @return \App\Models\Order
     * @throws \App\Exceptions\AuthorizationException إذا لم يكن الطلب خاصًا بالشركة
     * @throws \App\Exceptions\ValidationException    إذا كانت الحالة غير مسموح بها
     */
    public function updateStatusByCompany(int $orderId, string $newStatus, int $companyId, ?string $note = null): \App\Models\Order
    {
        return DB::transaction(function () use ($orderId, $newStatus, $companyId, $note) {
            $order = $this->orderRepository->findOrFail($orderId);

            if ($order->company_user_id !== $companyId) {
                throw new AuthorizationException('ليس لديك صلاحية تعديل هذا الطلب');
            }

            $allowed = $this->companyAllowedTransitions()[$order->status] ?? [];

            if (!in_array($newStatus, $allowed, true)) {
                throw new ValidationException(
                    "لا يمكن تغيير حالة الطلب من '{$order->status}' إلى '{$newStatus}'. "
                    . 'الحالات المسموحة: ' . implode(', ', $allowed ?: ['لا يوجد'])
                );
            }

            $oldStatus = $order->status;

            $updates = ['status' => $newStatus];

            if ($newStatus === 'approved') {
                $updates['approved_at']         = now();
                $updates['approved_by_user_id'] = $companyId;
            }

            if ($newStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }

            $order = $this->orderRepository->updateModel($order, $updates);

            \App\Models\OrderStatusLog::create([
                'order_id'           => $order->id,
                'from_status'        => $oldStatus,
                'to_status'          => $newStatus,
                'changed_by_user_id' => $companyId,
                'note'               => $note ?: null,
                'changed_at'         => now(),
            ]);

            if ($newStatus === 'approved') {
                $this->invoiceService->createInvoiceForOrder($order);
            }

            return $order;
        });
    }

    /**
     * Delete an order
     * 
     * @param int $orderId Order ID
     * @return bool True if deleted successfully
     */
    public function deleteOrder(int $orderId): bool
    {
        return $this->orderRepository->delete($orderId);
    }

    /**
     * Cancel an order (only if status is pending)
     * 
     * @param int $orderId Order ID
     * @param User $user The user cancelling the order
     * @param string|null $note Optional cancellation note from customer
     * @return \App\Models\Order Updated order model
     * @throws \App\Exceptions\ValidationException if order status is not pending
     */
    public function cancelOrder(int $orderId, User $user, ?string $note = null): \App\Models\Order
    {
        return DB::transaction(function () use ($orderId, $user, $note) {
            $order = $this->orderRepository->findOrFail($orderId);
            
            // Check if order is in pending status
            if ($order->status !== 'pending') {
                throw new ValidationException('يمكن إلغاء الطلبات ذات الحالة المعلقة فقط');
            }
            
            $oldStatus = $order->status;
            
            // Update order status to cancelled
            $order = $this->orderRepository->updateModel($order, ['status' => 'cancelled']);
            
            // Create status log
            \App\Models\OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => $oldStatus,
                'to_status' => 'cancelled',
                'changed_by_user_id' => $user->id,
                'note' => $note ?? 'تم إلغاء الطلب من قبل العميل',
                'changed_at' => now()
            ]);
            
            return $order;
        });
    }
}
