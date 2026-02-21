<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Order Data Integrity
 * 
 * **Validates: Requirements 10.1-10.7, 11.1-11.5, 12.1-12.6, 13.1-13.5**
 * 
 * Property 48: Order header creation with correct status, timestamps, and associations
 * Property 49: Order number uniqueness
 * Property 50: Company association
 * Property 51: Customer association
 * Property 52: Timestamp accuracy
 * Property 53: Notes persistence
 * Property 54: Order line count matches items
 * Property 55: All snapshot fields are persisted correctly
 * Property 56: Selected offer persistence
 * Property 57: Quantity persistence
 * Property 58: Bonus creation for bonus offers
 * Property 59: No bonuses for discount offers
 * Property 60: Status log creation
 */
class OrderDataIntegrityPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test 48-60: Complete order data integrity validation
     * 
     * **Validates: Requirements 10.1-10.7, 11.1-11.5, 12.1-12.6, 13.1-13.5**
     * 
     * For any successful order creation, the system should:
     * - Create order header with status='pending', submitted_at, and null approved_at/delivered_at
     * - Generate unique order_no
     * - Associate with correct company_id and customer_user_id
     * - Record submitted_at within 5 seconds of current time
     * - Persist notes_customer if provided
     * - Create correct number of order items
     * - Persist all snapshot fields (unit_price, discount_amount, final_line_total)
     * - Persist selected_offer_id (nullable)
     * - Persist qty for each item
     * - Create bonuses for bonus_qty offers
     * - NOT create bonuses for discount offers
     * - Create status log with from_status=null, to_status='pending', changed_by=customer
     */
    #[Test]
    public function order_data_integrity_is_maintained_across_all_fields(): void
    {
        // Feature: order-creation-api, Property 48-60: Order persistence properties
        
        $orderRepository = app(OrderRepository::class);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            // Create multiple products
            $numProducts = fake()->numberBetween(1, 5);
            $products = [];
            for ($j = 0; $j < $numProducts; $j++) {
                $products[] = Product::factory()->create([
                    'company_user_id' => $company->id,
                    'base_price' => fake()->randomFloat(2, 10, 100),
                    'is_active' => true
                ]);
            }
            
            // Prepare order data with multiple items and mixed offer types
            $orderItems = [];
            $bonuses = [];
            $expectedBonusCount = 0;
            $hasDiscountOffer = false;
            $hasBonusOffer = false;
            
            foreach ($products as $index => $product) {
                $qty = fake()->numberBetween(100, 1000);
                $unitPrice = round($product->base_price, 2, PHP_ROUND_HALF_UP);
                
                // Randomly assign offer types
                $offerType = fake()->randomElement(['none', 'discount_percent', 'discount_fixed', 'bonus_qty']);
                $selectedOfferId = null;
                $discountAmount = 0.00;
                
                if ($offerType !== 'none') {
                    // Create offer
                    $offer = Offer::factory()->create([
                        'company_user_id' => $company->id,
                        'title' => ucfirst($offerType) . ' Offer ' . uniqid(),
                        'scope' => 'public',
                        'status' => 'active',
                        'start_at' => now()->subDay()->toDateString(),
                        'end_at' => now()->addDay()->toDateString()
                    ]);
                    
                    $selectedOfferId = $offer->id;
                    
                    if ($offerType === 'discount_percent') {
                        $hasDiscountOffer = true;
                        $rewardValue = fake()->numberBetween(5, 20);
                        $minQty = 100;
                        
                        OfferItem::factory()->create([
                            'offer_id' => $offer->id,
                            'product_id' => $product->id,
                            'min_qty' => $minQty,
                            'reward_type' => 'discount_percent',
                            'discount_percent' => $rewardValue
                        ]);
                        
                        $multiplier = floor($qty / $minQty);
                        $discountPerBlock = round(($minQty * $unitPrice * $rewardValue / 100), 2, PHP_ROUND_HALF_UP);
                        $discountAmount = round($discountPerBlock * $multiplier, 2, PHP_ROUND_HALF_UP);
                        
                    } elseif ($offerType === 'discount_fixed') {
                        $hasDiscountOffer = true;
                        $rewardValue = fake()->randomFloat(2, 5, 50);
                        $minQty = 100;
                        
                        OfferItem::factory()->create([
                            'offer_id' => $offer->id,
                            'product_id' => $product->id,
                            'min_qty' => $minQty,
                            'reward_type' => 'discount_fixed',
                            'discount_fixed' => $rewardValue
                        ]);
                        
                        $multiplier = floor($qty / $minQty);
                        $discountAmount = round($rewardValue * $multiplier, 2, PHP_ROUND_HALF_UP);
                        
                    } elseif ($offerType === 'bonus_qty') {
                        $hasBonusOffer = true;
                        $bonusQty = fake()->numberBetween(10, 50);
                        $minQty = 100;
                        
                        OfferItem::factory()->create([
                            'offer_id' => $offer->id,
                            'product_id' => $product->id,
                            'min_qty' => $minQty,
                            'reward_type' => 'bonus_qty',
                            'bonus_product_id' => $product->id,
                            'bonus_qty' => $bonusQty
                        ]);
                        
                        $multiplier = floor($qty / $minQty);
                        $actualBonusQty = $bonusQty * $multiplier;
                        
                        $bonuses[] = [
                            'order_item_index' => $index,
                            'bonus_product_id' => $product->id,
                            'bonus_qty' => $actualBonusQty
                        ];
                        
                        $expectedBonusCount++;
                    }
                }
                
                $finalTotal = round(($qty * $unitPrice) - $discountAmount, 2, PHP_ROUND_HALF_UP);
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unitPrice,
                    'discount_amount_snapshot' => $discountAmount,
                    'final_line_total_snapshot' => $finalTotal,
                    'selected_offer_id' => $selectedOfferId
                ];
            }
            
            // Add notes randomly
            $notes = fake()->boolean(70) ? 'Test order notes ' . uniqid() : null;
            
            $orderData = [
                'company_id' => $company->id,
                'notes_customer' => $notes,
                'order_items' => $orderItems,
                'order_item_bonuses' => $bonuses
            ];
            
            // Record time before order creation for timestamp validation
            $beforeCreation = now();
            
            // Act: Create order
            $order = $orderRepository->createOrderWithTransaction($customer->id, $orderData);
            
            // Record time after order creation
            $afterCreation = now();
            
            // Assert Property 48: Order header creation with correct status and timestamps
            $this->assertNotNull($order, "Order should be created (iteration {$i})");
            $this->assertNotNull($order->id, "Order should have an ID (iteration {$i})");
            $this->assertEquals(
                'pending',
                $order->status,
                "Property 48: Order status should be 'pending' (iteration {$i})"
            );
            $this->assertNotNull(
                $order->submitted_at,
                "Property 48: Order should have submitted_at timestamp (iteration {$i})"
            );
            $this->assertNull(
                $order->approved_at,
                "Property 48: Order approved_at should be null (iteration {$i})"
            );
            $this->assertNull(
                $order->delivered_at,
                "Property 48: Order delivered_at should be null (iteration {$i})"
            );
            
            // Assert Property 49: Order number uniqueness
            $this->assertNotNull($order->order_no, "Property 49: Order should have order_no (iteration {$i})");
            $this->assertMatchesRegularExpression(
                '/^ORD-\d{14}-[A-Z0-9]{4}/',
                $order->order_no,
                "Property 49: Order number should match format ORD-YYYYMMDDHHMMSS-XXXX (iteration {$i})"
            );
            
            // Verify uniqueness by checking no other order has the same order_no
            $duplicateCount = Order::where('order_no', $order->order_no)->count();
            $this->assertEquals(
                1,
                $duplicateCount,
                "Property 49: Order number should be unique (iteration {$i})"
            );
            
            // Assert Property 50: Company association
            $this->assertEquals(
                $company->id,
                $order->company_user_id,
                "Property 50: Order should be associated with correct company (iteration {$i})"
            );
            
            // Assert Property 51: Customer association
            $this->assertEquals(
                $customer->id,
                $order->customer_user_id,
                "Property 51: Order should be associated with correct customer (iteration {$i})"
            );
            
            // Assert Property 52: Timestamp accuracy (within 5 seconds)
            $submittedAt = $order->submitted_at;
            $this->assertGreaterThanOrEqual(
                $beforeCreation->timestamp,
                $submittedAt->timestamp,
                "Property 52: submitted_at should be after or equal to before creation time (iteration {$i})"
            );
            $this->assertLessThanOrEqual(
                $afterCreation->timestamp,
                $submittedAt->timestamp,
                "Property 52: submitted_at should be before or equal to after creation time (iteration {$i})"
            );
            $this->assertLessThanOrEqual(
                5,
                $afterCreation->timestamp - $beforeCreation->timestamp,
                "Property 52: Order creation should complete within 5 seconds (iteration {$i})"
            );
            
            // Assert Property 53: Notes persistence
            if ($notes !== null) {
                $this->assertEquals(
                    $notes,
                    $order->notes_customer,
                    "Property 53: Notes should be persisted correctly (iteration {$i})"
                );
            } else {
                $this->assertNull(
                    $order->notes_customer,
                    "Property 53: Notes should be null when not provided (iteration {$i})"
                );
            }
            
            // Assert Property 54: Order line count matches items
            $persistedItems = OrderItem::where('order_id', $order->id)->get();
            $this->assertCount(
                count($orderItems),
                $persistedItems,
                "Property 54: Order should have correct number of items (iteration {$i})"
            );
            
            // Assert Properties 55-57: Snapshot fields, selected offer, and quantity persistence
            foreach ($persistedItems as $index => $persistedItem) {
                $expectedItem = $orderItems[$index];
                
                // Property 55: Order line snapshots
                $this->assertEquals(
                    $expectedItem['unit_price_snapshot'],
                    $persistedItem->unit_price_snapshot,
                    "Property 55: unit_price_snapshot should match (iteration {$i}, item {$index})"
                );
                $this->assertEquals(
                    $expectedItem['discount_amount_snapshot'],
                    $persistedItem->discount_amount_snapshot,
                    "Property 55: discount_amount_snapshot should match (iteration {$i}, item {$index})"
                );
                $this->assertEquals(
                    $expectedItem['final_line_total_snapshot'],
                    $persistedItem->final_line_total_snapshot,
                    "Property 55: final_line_total_snapshot should match (iteration {$i}, item {$index})"
                );
                
                // Property 56: Selected offer persistence
                $this->assertEquals(
                    $expectedItem['selected_offer_id'],
                    $persistedItem->selected_offer_id,
                    "Property 56: selected_offer_id should match (iteration {$i}, item {$index})"
                );
                
                // Property 57: Quantity persistence
                $this->assertEquals(
                    $expectedItem['qty'],
                    $persistedItem->qty,
                    "Property 57: qty should match (iteration {$i}, item {$index})"
                );
            }
            
            // Assert Property 58: Bonus creation for bonus offers
            $persistedBonuses = OrderItemBonus::whereIn(
                'order_item_id',
                $persistedItems->pluck('id')->toArray()
            )->get();
            
            $this->assertCount(
                $expectedBonusCount,
                $persistedBonuses,
                "Property 58: Correct number of bonuses should be created (iteration {$i})"
            );
            
            if ($expectedBonusCount > 0) {
                foreach ($persistedBonuses as $bonusIndex => $persistedBonus) {
                    $expectedBonus = $bonuses[$bonusIndex];
                    
                    $this->assertEquals(
                        $expectedBonus['bonus_product_id'],
                        $persistedBonus->bonus_product_id,
                        "Property 58: bonus_product_id should match (iteration {$i}, bonus {$bonusIndex})"
                    );
                    $this->assertEquals(
                        $expectedBonus['bonus_qty'],
                        $persistedBonus->bonus_qty,
                        "Property 58: bonus_qty should match (iteration {$i}, bonus {$bonusIndex})"
                    );
                    $this->assertNotNull(
                        $persistedBonus->offer_id,
                        "Property 58: offer_id should not be null (iteration {$i}, bonus {$bonusIndex})"
                    );
                }
            }
            
            // Assert Property 59: No bonuses for discount offers
            if ($hasDiscountOffer && !$hasBonusOffer) {
                $this->assertCount(
                    0,
                    $persistedBonuses,
                    "Property 59: No bonuses should be created for discount offers (iteration {$i})"
                );
            }
            
            // Assert Property 60: Status log creation
            $statusLog = OrderStatusLog::where('order_id', $order->id)->first();
            $this->assertNotNull(
                $statusLog,
                "Property 60: Status log should exist (iteration {$i})"
            );
            $this->assertNull(
                $statusLog->from_status,
                "Property 60: from_status should be null for initial creation (iteration {$i})"
            );
            $this->assertEquals(
                'pending',
                $statusLog->to_status,
                "Property 60: to_status should be 'pending' (iteration {$i})"
            );
            $this->assertEquals(
                $customer->id,
                $statusLog->changed_by_user_id,
                "Property 60: changed_by_user_id should be customer (iteration {$i})"
            );
            $this->assertNotNull(
                $statusLog->changed_at,
                "Property 60: changed_at should not be null (iteration {$i})"
            );
            
            // Verify status log timestamp is within reasonable range
            $statusLogTime = \Carbon\Carbon::parse($statusLog->changed_at);
            $this->assertGreaterThanOrEqual(
                $beforeCreation->timestamp,
                $statusLogTime->timestamp,
                "Property 60: Status log timestamp should be after or equal to before creation time (iteration {$i})"
            );
            $this->assertLessThanOrEqual(
                $afterCreation->timestamp,
                $statusLogTime->timestamp,
                "Property 60: Status log timestamp should be before or equal to after creation time (iteration {$i})"
            );
        }
    }
}
