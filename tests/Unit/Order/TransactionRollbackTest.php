<?php

namespace Tests\Unit\Order;

use App\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Transaction Rollback
 * 
 * **Validates: Requirements 14.2, 14.3**
 * 
 * Tests that database transactions are properly rolled back on error,
 * ensuring no partial data is persisted when order creation fails.
 */
class TransactionRollbackTest extends TestCase
{
    use RefreshDatabase;

    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(OrderRepository::class);
    }

    /**
     * Test rollback on database constraint violation
     * 
     * Simulates a database error during order creation and verifies
     * that no partial data (order header, items, logs) is persisted.
     */
    #[Test]
    public function it_rolls_back_transaction_on_database_error(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        // Prepare order data with intentional invalid product_id in second item
        $orderData = [
            'company_id' => $company->id,
            'notes_customer' => 'Test rollback order',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'unit_price_snapshot' => 100.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 500.00,
                    'selected_offer_id' => null
                ],
                [
                    'product_id' => 999999, // Non-existent product (will cause FK constraint error)
                    'qty' => 3,
                    'unit_price_snapshot' => 50.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 150.00,
                    'selected_offer_id' => null
                ]
            ],
            'order_item_bonuses' => []
        ];

        // Record counts before attempt
        $orderCountBefore = Order::count();
        $orderItemCountBefore = OrderItem::count();
        $statusLogCountBefore = OrderStatusLog::count();

        // Act & Assert: Expect exception during creation
        try {
            $this->repository->createOrderWithTransaction($customer->id, $orderData);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected to fail due to FK constraint violation
            $this->assertTrue(true, 'Exception was thrown as expected');
        }

        // Assert: Verify complete rollback - no partial data persisted
        $this->assertEquals($orderCountBefore, Order::count(), 
            'Order count should not change after rollback');
        $this->assertEquals($orderItemCountBefore, OrderItem::count(), 
            'OrderItem count should not change after rollback');
        $this->assertEquals($statusLogCountBefore, OrderStatusLog::count(), 
            'OrderStatusLog count should not change after rollback');
    }

    /**
     * Test that successful transaction commits all data atomically
     * 
     * Validates that when no error occurs, all related records are persisted
     */
    #[Test]
    public function it_commits_all_data_on_successful_transaction(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);
        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $orderData = [
            'company_id' => $company->id,
            'notes_customer' => 'Valid order',
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 5,
                    'unit_price_snapshot' => 100.00,
                    'discount_amount_snapshot' => 10.00,
                    'final_line_total_snapshot' => 490.00,
                    'selected_offer_id' => null
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 3,
                    'unit_price_snapshot' => 50.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 150.00,
                    'selected_offer_id' => null
                ]
            ],
            'order_item_bonuses' => []
        ];

        // Record counts before
        $orderCountBefore = Order::count();
        $orderItemCountBefore = OrderItem::count();
        $statusLogCountBefore = OrderStatusLog::count();

        // Act
        $order = $this->repository->createOrderWithTransaction($customer->id, $orderData);

        // Assert: All records created
        $this->assertEquals($orderCountBefore + 1, Order::count(), 
            'One order should be created');
        $this->assertEquals($orderItemCountBefore + 2, OrderItem::count(), 
            'Two order items should be created');
        $this->assertEquals($statusLogCountBefore + 1, OrderStatusLog::count(), 
            'One status log should be created');

        // Assert: Order is properly populated
        $this->assertNotNull($order->id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($customer->id, $order->customer_user_id);
        $this->assertEquals($company->id, $order->company_user_id);
        $this->assertNotNull($order->order_no);
        $this->assertNotNull($order->submitted_at);
    }

    /**
     * Test that nested transaction failures don't leave partial data
     * 
     * Simulates a complex scenario where creation fails after some operations
     */
    #[Test]
    public function it_handles_nested_transaction_rollback(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        // Record initial state
        $initialOrderCount = Order::count();

        // Act: Manually trigger a transaction with intentional failure
        try {
            DB::transaction(function () use ($company, $customer, $product) {
                // Create order (should succeed)
                Order::create([
                    'company_user_id' => $company->id,
                    'customer_user_id' => $customer->id,
                    'order_no' => 'ORD-TEST-12345',
                    'status' => 'pending',
                    'submitted_at' => now()
                ]);

                // Force an exception to trigger rollback
                throw new \Exception('Simulated failure');
            });

            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Simulated failure', $e->getMessage());
        }

        // Assert: Rollback occurred, no order was persisted
        $this->assertEquals($initialOrderCount, Order::count(), 
            'Transaction should have been rolled back completely');
    }

    /**
     * Test that order number uniqueness constraint is handled within transaction
     * 
     * Validates that even unique constraint violations trigger proper rollback
     */
    #[Test]
    public function it_rolls_back_on_order_number_conflict(): void
    {
        // Arrange: Create an existing order with a specific order number
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $existingOrderNo = 'ORD-20260220000000-TEST';
        Order::create([
            'company_user_id' => $company->id,
            'customer_user_id' => $customer->id,
            'order_no' => $existingOrderNo,
            'status' => 'pending',
            'submitted_at' => now()
        ]);

        $initialCount = Order::count();

        // Act: Try to create another order with the same order_no
        try {
            DB::transaction(function () use ($company, $customer, $existingOrderNo) {
                Order::create([
                    'company_user_id' => $company->id,
                    'customer_user_id' => $customer->id,
                    'order_no' => $existingOrderNo, // Duplicate!
                    'status' => 'pending',
                    'submitted_at' => now()
                ]);
            });

            $this->fail('Expected unique constraint exception was not thrown');
        } catch (\Exception $e) {
            // Expected to fail due to unique constraint
            $this->assertTrue(true);
        }

        // Assert: No new order was created
        $this->assertEquals($initialCount, Order::count(), 
            'Order count should remain unchanged after unique constraint violation');
    }

    /**
     * Test that multiple order items are atomic (all or nothing)
     */
    #[Test]
    public function it_creates_all_order_items_atomically(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $products = Product::factory()->count(3)->create([
            'company_user_id' => $company->id,
            'is_active' => true
        ]);

        $orderData = [
            'company_id' => $company->id,
            'notes_customer' => 'Multi-item order',
            'order_items' => [
                [
                    'product_id' => $products[0]->id,
                    'qty' => 2,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 20.00,
                    'selected_offer_id' => null
                ],
                [
                    'product_id' => $products[1]->id,
                    'qty' => 5,
                    'unit_price_snapshot' => 15.00,
                    'discount_amount_snapshot' => 5.00,
                    'final_line_total_snapshot' => 70.00,
                    'selected_offer_id' => null
                ],
                [
                    'product_id' => $products[2]->id,
                    'qty' => 1,
                    'unit_price_snapshot' => 100.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 100.00,
                    'selected_offer_id' => null
                ]
            ],
            'order_item_bonuses' => []
        ];

        // Act
        $order = $this->repository->createOrderWithTransaction($customer->id, $orderData);

        // Assert: All items persisted
        $this->assertCount(3, $order->items, 'Order should have exactly 3 items');
        
        // Verify each item is correctly stored
        foreach ($order->items as $index => $item) {
            $this->assertEquals($orderData['order_items'][$index]['product_id'], $item->product_id);
            $this->assertEquals($orderData['order_items'][$index]['qty'], $item->qty);
        }
    }
}
