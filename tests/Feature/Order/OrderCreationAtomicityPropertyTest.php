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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Order Creation Atomicity
 * 
 * **Validates: Requirements 14.1, 14.2, 14.4, 14.5**
 * 
 * Property 61: For any confirmation request that fails during order creation,
 * the system should roll back all changes (no order, order_items, bonuses, or
 * status_logs persisted).
 * 
 * Property 62: For any successful confirmation, the persisted data should include
 * the order header, all order items, all bonuses, and the initial status log entry.
 */
class OrderCreationAtomicityPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test 61: Transaction rollback on database errors
     * 
     * **Validates: Requirements 14.1, 14.2**
     * 
     * For any confirmation request that fails during order creation due to
     * database errors, the system should roll back all changes. No partial
     * data should be persisted (no orders, order_items, bonuses, or status_logs).
     */
    #[Test]
    public function transaction_rolls_back_on_database_errors(): void
    {
        // Feature: order-creation-api, Property 61: Transaction rollback on failure
        
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
            
            // Create product
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Count records before attempting order creation
            $orderCountBefore = Order::count();
            $orderItemCountBefore = OrderItem::count();
            $bonusCountBefore = OrderItemBonus::count();
            $statusLogCountBefore = OrderStatusLog::count();
            
            // Prepare order data with invalid company_id to trigger foreign key constraint violation
            $invalidOrderData = [
                'company_id' => 999999, // Non-existent company
                'notes_customer' => 'Test order ' . uniqid(),
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($qty * $product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ]
                ]
            ];
            
            // Act: Attempt to create order (should fail due to foreign key constraint)
            try {
                $orderRepository->createOrderWithTransaction($customer->id, $invalidOrderData);
                $this->fail("Should have thrown exception due to database constraint violation (iteration {$i})");
            } catch (\Exception $e) {
                // Expected exception (database constraint violation)
                $this->assertTrue(true, "Exception was thrown as expected (iteration {$i})");
            }
            
            // Assert: No partial data was persisted (transaction rolled back)
            $orderCountAfter = Order::count();
            $orderItemCountAfter = OrderItem::count();
            $bonusCountAfter = OrderItemBonus::count();
            $statusLogCountAfter = OrderStatusLog::count();
            
            $this->assertEquals(
                $orderCountBefore,
                $orderCountAfter,
                "No orders should be created when database error occurs (iteration {$i})"
            );
            
            $this->assertEquals(
                $orderItemCountBefore,
                $orderItemCountAfter,
                "No order items should be created when database error occurs (iteration {$i})"
            );
            
            $this->assertEquals(
                $bonusCountBefore,
                $bonusCountAfter,
                "No bonuses should be created when database error occurs (iteration {$i})"
            );
            
            $this->assertEquals(
                $statusLogCountBefore,
                $statusLogCountAfter,
                "No status logs should be created when database error occurs (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 62: Complete order persistence on success
     * 
     * **Validates: Requirements 14.4, 14.5**
     * 
     * For any successful confirmation, the persisted data should include the
     * order header, all order items, all bonuses (if applicable), and the
     * initial status log entry. All data must be persisted atomically.
     */
    #[Test]
    public function complete_order_data_is_persisted_atomically(): void
    {
        // Feature: order-creation-api, Property 62: Complete order persistence
        
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
            
            // Prepare order data with multiple items
            $orderItems = [];
            $expectedBonusCount = 0;
            
            foreach ($products as $index => $product) {
                $qty = fake()->numberBetween(100, 1000);
                $unitPrice = round($product->base_price, 2, PHP_ROUND_HALF_UP);
                $discountAmount = 0.00;
                $finalTotal = round($qty * $unitPrice, 2, PHP_ROUND_HALF_UP);
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unitPrice,
                    'discount_amount_snapshot' => $discountAmount,
                    'final_line_total_snapshot' => $finalTotal,
                    'selected_offer_id' => null
                ];
            }
            
            // Randomly add bonuses to some items
            $bonuses = [];
            $offer = null;
            if (fake()->boolean(50)) { // 50% chance to have bonuses
                $itemIndexWithBonus = fake()->numberBetween(0, count($orderItems) - 1);
                $bonusProduct = $products[$itemIndexWithBonus];
                $bonusQty = fake()->numberBetween(10, 100);
                
                // Create an actual offer for the bonus
                $offer = Offer::factory()->create([
                    'company_user_id' => $company->id,
                    'title' => 'Test Bonus Offer ' . uniqid(),
                    'scope' => 'public',
                    'status' => 'active',
                    'start_at' => now()->subDay()->toDateString(),
                    'end_at' => now()->addDay()->toDateString()
                ]);
                
                OfferItem::factory()->create([
                    'offer_id' => $offer->id,
                    'product_id' => $bonusProduct->id,
                    'min_qty' => 100,
                    'reward_type' => 'bonus_qty',
                    'discount_percent' => null,
                    'discount_fixed' => null,
                    'bonus_product_id' => $bonusProduct->id,
                    'bonus_qty' => $bonusQty
                ]);
                
                $bonuses[] = [
                    'order_item_index' => $itemIndexWithBonus,
                    'bonus_product_id' => $bonusProduct->id,
                    'bonus_qty' => $bonusQty
                ];
                
                // Update the order item to have the selected offer
                $orderItems[$itemIndexWithBonus]['selected_offer_id'] = $offer->id;
                $expectedBonusCount = 1;
            }
            
            $orderData = [
                'company_id' => $company->id,
                'notes_customer' => 'Test order ' . uniqid(),
                'order_items' => $orderItems,
                'order_item_bonuses' => $bonuses
            ];
            
            // Count records before order creation
            $orderCountBefore = Order::count();
            $orderItemCountBefore = OrderItem::count();
            $bonusCountBefore = OrderItemBonus::count();
            $statusLogCountBefore = OrderStatusLog::count();
            
            // Act: Create order
            $order = $orderRepository->createOrderWithTransaction($customer->id, $orderData);
            
            // Assert: Order header was created
            $this->assertNotNull($order, "Order should be created (iteration {$i})");
            $this->assertNotNull($order->id, "Order should have an ID (iteration {$i})");
            $this->assertNotNull($order->order_no, "Order should have an order number (iteration {$i})");
            $this->assertEquals('pending', $order->status, "Order status should be pending (iteration {$i})");
            $this->assertEquals($company->id, $order->company_user_id, "Order should be associated with company (iteration {$i})");
            $this->assertEquals($customer->id, $order->customer_user_id, "Order should be associated with customer (iteration {$i})");
            $this->assertNotNull($order->submitted_at, "Order should have submitted_at timestamp (iteration {$i})");
            
            // Assert: All order items were created
            $orderCountAfter = Order::count();
            $orderItemCountAfter = OrderItem::count();
            
            $this->assertEquals(
                $orderCountBefore + 1,
                $orderCountAfter,
                "Exactly one order should be created (iteration {$i})"
            );
            
            $this->assertEquals(
                $orderItemCountBefore + count($orderItems),
                $orderItemCountAfter,
                "All order items should be created (iteration {$i})"
            );
            
            $persistedItems = OrderItem::where('order_id', $order->id)->get();
            $this->assertCount(
                count($orderItems),
                $persistedItems,
                "Order should have correct number of items (iteration {$i})"
            );
            
            // Verify each order item has correct data
            foreach ($persistedItems as $index => $persistedItem) {
                $expectedItem = $orderItems[$index];
                
                $this->assertEquals(
                    $expectedItem['product_id'],
                    $persistedItem->product_id,
                    "Order item product_id should match (iteration {$i}, item {$index})"
                );
                
                $this->assertEquals(
                    $expectedItem['qty'],
                    $persistedItem->qty,
                    "Order item qty should match (iteration {$i}, item {$index})"
                );
                
                $this->assertEquals(
                    $expectedItem['unit_price_snapshot'],
                    $persistedItem->unit_price_snapshot,
                    "Order item unit_price_snapshot should match (iteration {$i}, item {$index})"
                );
                
                $this->assertEquals(
                    $expectedItem['discount_amount_snapshot'],
                    $persistedItem->discount_amount_snapshot,
                    "Order item discount_amount_snapshot should match (iteration {$i}, item {$index})"
                );
                
                $this->assertEquals(
                    $expectedItem['final_line_total_snapshot'],
                    $persistedItem->final_line_total_snapshot,
                    "Order item final_line_total_snapshot should match (iteration {$i}, item {$index})"
                );
            }
            
            // Assert: All bonuses were created (if applicable)
            $bonusCountAfter = OrderItemBonus::count();
            
            $this->assertEquals(
                $bonusCountBefore + $expectedBonusCount,
                $bonusCountAfter,
                "All bonuses should be created (iteration {$i})"
            );
            
            if ($expectedBonusCount > 0) {
                $persistedBonuses = OrderItemBonus::whereIn(
                    'order_item_id',
                    $persistedItems->pluck('id')->toArray()
                )->get();
                
                $this->assertCount(
                    $expectedBonusCount,
                    $persistedBonuses,
                    "Order should have correct number of bonuses (iteration {$i})"
                );
                
                foreach ($persistedBonuses as $persistedBonus) {
                    $this->assertNotNull(
                        $persistedBonus->bonus_product_id,
                        "Bonus should have bonus_product_id (iteration {$i})"
                    );
                    
                    $this->assertGreaterThan(
                        0,
                        $persistedBonus->bonus_qty,
                        "Bonus should have positive bonus_qty (iteration {$i})"
                    );
                    
                    $this->assertNotNull(
                        $persistedBonus->offer_id,
                        "Bonus should have offer_id (iteration {$i})"
                    );
                }
            }
            
            // Assert: Status log was created
            $statusLogCountAfter = OrderStatusLog::count();
            
            $this->assertEquals(
                $statusLogCountBefore + 1,
                $statusLogCountAfter,
                "Exactly one status log should be created (iteration {$i})"
            );
            
            $statusLog = OrderStatusLog::where('order_id', $order->id)->first();
            $this->assertNotNull($statusLog, "Status log should exist (iteration {$i})");
            $this->assertNull($statusLog->from_status, "Status log from_status should be null (iteration {$i})");
            $this->assertEquals('pending', $statusLog->to_status, "Status log to_status should be pending (iteration {$i})");
            $this->assertEquals($customer->id, $statusLog->changed_by_user_id, "Status log should be changed by customer (iteration {$i})");
            $this->assertNotNull($statusLog->changed_at, "Status log should have changed_at timestamp (iteration {$i})");
            
            // Assert: All data was persisted atomically (verify relationships)
            $this->assertNotNull($order->items, "Order should have items relationship loaded (iteration {$i})");
            $this->assertCount(
                count($orderItems),
                $order->items,
                "Order items relationship should have correct count (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 61: Transaction rollback on partial failure
     * 
     * **Validates: Requirements 14.1, 14.2**
     * 
     * For any order creation that fails after creating some records but before
     * completing all records, the entire transaction should roll back, leaving
     * no partial data in the database.
     */
    #[Test]
    public function transaction_rolls_back_on_partial_failure(): void
    {
        // Feature: order-creation-api, Property 61: Transaction rollback on failure
        
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
            
            // Create product
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Count records before attempting order creation
            $orderCountBefore = Order::count();
            $orderItemCountBefore = OrderItem::count();
            $bonusCountBefore = OrderItemBonus::count();
            $statusLogCountBefore = OrderStatusLog::count();
            
            // Prepare order data with invalid product_id in second item to trigger failure
            $invalidOrderData = [
                'company_id' => $company->id,
                'notes_customer' => 'Test order ' . uniqid(),
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => round($product->base_price, 2, PHP_ROUND_HALF_UP),
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($qty * $product->base_price, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ],
                    [
                        'product_id' => 999999, // Non-existent product
                        'qty' => $qty,
                        'unit_price_snapshot' => 10.00,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($qty * 10.00, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ]
                ]
            ];
            
            // Act: Attempt to create order (should fail on second item)
            try {
                $orderRepository->createOrderWithTransaction($customer->id, $invalidOrderData);
                $this->fail("Should have thrown exception due to invalid product_id (iteration {$i})");
            } catch (\Exception $e) {
                // Expected exception (foreign key constraint violation)
                $this->assertTrue(true, "Exception was thrown as expected (iteration {$i})");
            }
            
            // Assert: No partial data was persisted (transaction rolled back completely)
            $orderCountAfter = Order::count();
            $orderItemCountAfter = OrderItem::count();
            $bonusCountAfter = OrderItemBonus::count();
            $statusLogCountAfter = OrderStatusLog::count();
            
            $this->assertEquals(
                $orderCountBefore,
                $orderCountAfter,
                "No orders should be created when partial failure occurs (iteration {$i})"
            );
            
            $this->assertEquals(
                $orderItemCountBefore,
                $orderItemCountAfter,
                "No order items should be created when partial failure occurs (iteration {$i})"
            );
            
            $this->assertEquals(
                $bonusCountBefore,
                $bonusCountAfter,
                "No bonuses should be created when partial failure occurs (iteration {$i})"
            );
            
            $this->assertEquals(
                $statusLogCountBefore,
                $statusLogCountAfter,
                "No status logs should be created when partial failure occurs (iteration {$i})"
            );
        }
    }
}
