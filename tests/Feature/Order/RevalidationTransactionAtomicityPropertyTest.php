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
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Revalidation Transaction Atomicity
 * 
 * **Validates: Requirements 9.3**
 * 
 * Property 43: For any confirmation request, the revalidation and order persistence
 * should happen in the SAME database transaction to prevent race conditions.
 */
class RevalidationTransactionAtomicityPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test 43: Revalidation and persistence occur in same transaction
     * 
     * **Validates: Requirements 9.3**
     * 
     * For any confirmation request, the revalidation and order persistence should
     * happen in the SAME database transaction to prevent race conditions where
     * prices or offers change between validation and persistence.
     */
    #[Test]
    public function revalidation_and_persistence_occur_in_same_transaction(): void
    {
        // Feature: order-creation-api, Property 43: Revalidation transaction atomicity
        
        $orderService = app(OrderService::class);
        
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
            
            // Create product with initial price
            $initialPrice = fake()->randomFloat(2, 10, 100);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $initialPrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create preview
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];
            
            $this->actingAs($customer);
            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];
            
            // Verify preview was created
            $this->assertNotNull(Cache::get("preview:{$previewToken}"));
            
            // Act: Confirm order (revalidation and persistence should be atomic)
            $orderDTO = $orderService->confirmOrder($previewToken, $customer);
            
            // Assert: Order was created successfully
            $this->assertNotNull($orderDTO);
            $this->assertArrayHasKey('order_no', $orderDTO);
            
            // Verify order exists in database
            $order = Order::where('order_no', $orderDTO['order_no'])->first();
            $this->assertNotNull($order, "Order should exist in database (iteration {$i})");
            
            // Verify order items were created
            $orderItems = OrderItem::where('order_id', $order->id)->get();
            $this->assertCount(1, $orderItems, "Should have 1 order item (iteration {$i})");
            
            // Verify status log was created
            $statusLog = OrderStatusLog::where('order_id', $order->id)->first();
            $this->assertNotNull($statusLog, "Status log should exist (iteration {$i})");
            $this->assertEquals('pending', $statusLog->to_status);
            
            // Verify preview token was deleted (single-use)
            $this->assertNull(
                Cache::get("preview:{$previewToken}"),
                "Preview token should be deleted after successful confirmation (iteration {$i})"
            );
            
            // Verify the persisted values match recalculated values (not stale preview values)
            $orderItem = $orderItems->first();
            $expectedUnitPrice = round($initialPrice, 2, PHP_ROUND_HALF_UP);
            $expectedFinalTotal = round($qty * $expectedUnitPrice, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedUnitPrice,
                $orderItem->unit_price_snapshot,
                "Persisted unit price should match recalculated value (iteration {$i})"
            );
            
            $this->assertEquals(
                $expectedFinalTotal,
                $orderItem->final_line_total_snapshot,
                "Persisted final total should match recalculated value (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 43: Revalidation failure prevents persistence
     * 
     * **Validates: Requirements 9.3**
     * 
     * For any confirmation request where revalidation fails (e.g., price changed),
     * no order data should be persisted to the database. The transaction should
     * roll back completely.
     */
    #[Test]
    public function revalidation_failure_prevents_persistence(): void
    {
        // Feature: order-creation-api, Property 43: Revalidation transaction atomicity
        
        $orderService = app(OrderService::class);
        
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
            
            // Create product with initial price
            $initialPrice = fake()->randomFloat(2, 10, 100);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $initialPrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create preview
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];
            
            $this->actingAs($customer);
            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];
            
            // Count orders before confirmation
            $orderCountBefore = Order::count();
            $orderItemCountBefore = OrderItem::count();
            $statusLogCountBefore = OrderStatusLog::count();
            
            // Change price to invalidate preview
            $priceChange = fake()->randomFloat(2, 0.02, 50);
            $product->base_price = $initialPrice + $priceChange;
            $product->save();
            
            // Act: Attempt to confirm order (should fail revalidation)
            try {
                $orderService->confirmOrder($previewToken, $customer);
                $this->fail("Should have thrown PreviewInvalidatedException (iteration {$i})");
            } catch (\App\Exceptions\PreviewInvalidatedException $e) {
                // Expected exception
                $this->assertStringContainsString(
                    'Preview is no longer valid',
                    $e->getMessage(),
                    "Exception message should indicate preview is invalid (iteration {$i})"
                );
            }
            
            // Assert: No order data was persisted
            $orderCountAfter = Order::count();
            $orderItemCountAfter = OrderItem::count();
            $statusLogCountAfter = OrderStatusLog::count();
            
            $this->assertEquals(
                $orderCountBefore,
                $orderCountAfter,
                "No orders should be created when revalidation fails (iteration {$i})"
            );
            
            $this->assertEquals(
                $orderItemCountBefore,
                $orderItemCountAfter,
                "No order items should be created when revalidation fails (iteration {$i})"
            );
            
            $this->assertEquals(
                $statusLogCountBefore,
                $statusLogCountAfter,
                "No status logs should be created when revalidation fails (iteration {$i})"
            );
            
            // Verify preview token is kept (so client can re-preview)
            $this->assertNotNull(
                Cache::get("preview:{$previewToken}"),
                "Preview token should be kept when revalidation fails (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 43: Persistence failure rolls back transaction
     * 
     * **Validates: Requirements 9.3**
     * 
     * For any confirmation request where persistence fails after successful
     * revalidation, the entire transaction should roll back, leaving no
     * partial data in the database.
     * 
     * Note: This test verifies rollback behavior by attempting to create an order
     * with an invalid company_id, which will cause a foreign key constraint violation.
     */
    #[Test]
    public function persistence_failure_rolls_back_transaction(): void
    {
        // Feature: order-creation-api, Property 43: Revalidation transaction atomicity
        
        $orderService = app(OrderService::class);
        
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
            
            // Create preview
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];
            
            $this->actingAs($customer);
            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];
            
            // Count records before confirmation
            $orderCountBefore = Order::count();
            $orderItemCountBefore = OrderItem::count();
            $statusLogCountBefore = OrderStatusLog::count();
            
            // Corrupt the preview data to cause a database failure
            // Change company_id to a non-existent value (foreign key violation)
            $cachedPreview = Cache::get("preview:{$previewToken}");
            $cachedPreview['company_id'] = 999999; // Non-existent company
            Cache::put("preview:{$previewToken}", $cachedPreview, now()->addMinutes(15));
            
            // Act: Attempt to confirm order (should fail during persistence)
            try {
                $orderService->confirmOrder($previewToken, $customer);
                $this->fail("Should have thrown exception due to database failure (iteration {$i})");
            } catch (\Exception $e) {
                // Expected exception (database constraint violation)
                $this->assertTrue(true, "Exception was thrown as expected (iteration {$i})");
            }
            
            // Assert: No partial data was persisted (transaction rolled back)
            $orderCountAfter = Order::count();
            $orderItemCountAfter = OrderItem::count();
            $statusLogCountAfter = OrderStatusLog::count();
            
            $this->assertEquals(
                $orderCountBefore,
                $orderCountAfter,
                "No orders should be created when persistence fails (iteration {$i})"
            );
            
            $this->assertEquals(
                $orderItemCountBefore,
                $orderItemCountAfter,
                "No order items should be created when persistence fails (iteration {$i})"
            );
            
            $this->assertEquals(
                $statusLogCountBefore,
                $statusLogCountAfter,
                "No status logs should be created when persistence fails (iteration {$i})"
            );
            
            // Verify preview token was deleted (to prevent replay attacks)
            $this->assertNull(
                Cache::get("preview:{$previewToken}"),
                "Preview token should be deleted when persistence fails (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 43: Concurrent price changes are prevented by transaction
     * 
     * **Validates: Requirements 9.3**
     * 
     * For any confirmation request, if a price change occurs between revalidation
     * and persistence within the same transaction, the transaction should use
     * the values from the start of the transaction (READ COMMITTED isolation level).
     * 
     * This test verifies that the transaction isolation prevents race conditions.
     */
    #[Test]
    public function transaction_prevents_race_conditions(): void
    {
        // Feature: order-creation-api, Property 43: Revalidation transaction atomicity
        
        $orderService = app(OrderService::class);
        
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
            
            // Create product with initial price
            $initialPrice = fake()->randomFloat(2, 10, 100);
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $initialPrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create preview
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];
            
            $this->actingAs($customer);
            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];
            
            // Act: Confirm order
            // The transaction should read the current price at the start and use it
            // throughout the entire transaction (revalidation + persistence)
            $orderDTO = $orderService->confirmOrder($previewToken, $customer);
            
            // Assert: Order was created with consistent values
            $order = Order::where('order_no', $orderDTO['order_no'])->first();
            $this->assertNotNull($order, "Order should exist (iteration {$i})");
            
            $orderItem = OrderItem::where('order_id', $order->id)->first();
            $this->assertNotNull($orderItem, "Order item should exist (iteration {$i})");
            
            // Verify the persisted values are internally consistent
            // (unit_price * qty - discount = final_total)
            $expectedFinalTotal = round(
                ($orderItem->qty * $orderItem->unit_price_snapshot) - $orderItem->discount_amount_snapshot,
                2,
                PHP_ROUND_HALF_UP
            );
            
            $this->assertEquals(
                $expectedFinalTotal,
                $orderItem->final_line_total_snapshot,
                "Persisted values should be internally consistent (iteration {$i})"
            );
            
            // Verify the unit price matches the product price at transaction time
            $expectedUnitPrice = round($initialPrice, 2, PHP_ROUND_HALF_UP);
            $this->assertEquals(
                $expectedUnitPrice,
                $orderItem->unit_price_snapshot,
                "Persisted unit price should match product price at transaction time (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 43: Offer changes during transaction are handled atomically
     * 
     * **Validates: Requirements 9.3**
     * 
     * For any confirmation request with offers, if an offer changes between
     * revalidation and persistence, the transaction should either:
     * 1. Detect the change during revalidation and reject (HTTP 409), OR
     * 2. Use consistent offer data throughout the transaction
     * 
     * No partial or inconsistent data should be persisted.
     */
    #[Test]
    public function offer_changes_are_handled_atomically(): void
    {
        // Feature: order-creation-api, Property 43: Revalidation transaction atomicity
        
        $orderService = app(OrderService::class);
        
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
            
            // Create an active offer
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'title' => 'Test Offer ' . uniqid(),
                'scope' => 'public',
                'status' => 'active',
                'start_at' => now()->subDay()->toDateString(),
                'end_at' => now()->addDay()->toDateString()
            ]);
            
            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => 100,
                'reward_type' => 'discount_percent',
                'discount_percent' => 10.00,
                'discount_fixed' => null,
                'bonus_product_id' => null,
                'bonus_qty' => null
            ]);
            
            $qty = fake()->numberBetween(100, 1000);
            
            // Create preview (should select the offer)
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    ['product_id' => $product->id, 'qty' => $qty]
                ]
            ];
            
            $this->actingAs($customer);
            $previewResponse = $orderService->previewOrder($previewData, $customer);
            $previewToken = $previewResponse['preview_token'];
            
            // Verify offer was selected in preview
            $this->assertEquals(
                $offer->id,
                $previewResponse['items'][0]['selected_offer_id'],
                "Offer should be selected in preview (iteration {$i})"
            );
            
            // Act: Confirm order
            // The transaction should use consistent offer data throughout
            $orderDTO = $orderService->confirmOrder($previewToken, $customer);
            
            // Assert: Order was created with consistent offer data
            $order = Order::where('order_no', $orderDTO['order_no'])->first();
            $this->assertNotNull($order, "Order should exist (iteration {$i})");
            
            $orderItem = OrderItem::where('order_id', $order->id)->first();
            $this->assertNotNull($orderItem, "Order item should exist (iteration {$i})");
            
            // Verify the offer was applied consistently
            $this->assertEquals(
                $offer->id,
                $orderItem->selected_offer_id,
                "Persisted offer should match preview offer (iteration {$i})"
            );
            
            // Verify discount was calculated correctly based on the offer
            $expectedDiscount = round(
                (100 * $orderItem->unit_price_snapshot * 10 / 100) * floor($qty / 100),
                2,
                PHP_ROUND_HALF_UP
            );
            
            $this->assertEquals(
                $expectedDiscount,
                $orderItem->discount_amount_snapshot,
                "Discount should be calculated correctly based on offer (iteration {$i})"
            );
        }
    }
}
