<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Creation with No Offers
 * 
 * **Validates: Requirements 1.2, 1.3, 4.1, 5.1**
 * 
 * This test verifies the complete end-to-end flow of creating an order
 * without any offers applied, from HTTP request to database persistence.
 */
class OrderCreationNoOffersIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete order creation flow with no offers
     * 
     * **Validates: Requirements 1.2, 1.3, 4.1, 5.1**
     * 
     * Verifies that:
     * 1. Order is created successfully with HTTP 201
     * 2. Order header is persisted with correct data
     * 3. Order items are persisted with correct snapshots
     * 4. No bonuses are created
     * 5. Status log is created
     * 6. Response contains complete order details
     */
    #[Test]
    public function order_creation_succeeds_with_no_offers(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 5.50,
            'is_active' => true
        ]);

        // Prepare order request
        $orderData = [
            'company_id' => $company->id,
            'notes' => 'Test order with no offers',
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1000.00,
                    'selected_offer_id' => null
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 50,
                    'unit_price_snapshot' => 5.50,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 275.00,
                    'selected_offer_id' => null
                ]
            ]
        ];

        // Act: Submit order
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert: HTTP response
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully'
            ]);

        // Assert: Order header persisted
        $this->assertDatabaseHas('orders', [
            'company_user_id' => $company->id,
            'customer_user_id' => $customer->id,
            'status' => 'pending',
            'notes_customer' => 'Test order with no offers'
        ]);

        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->order_no);
        $this->assertNotNull($order->submitted_at);
        $this->assertNull($order->approved_at);
        $this->assertNull($order->delivered_at);

        // Assert: Order items persisted
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'qty' => 100,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 1000.00,
            'selected_offer_id' => null
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'qty' => 50,
            'unit_price_snapshot' => 5.50,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 275.00,
            'selected_offer_id' => null
        ]);

        // Assert: No bonuses created
        $this->assertEquals(0, OrderItemBonus::count());

        // Assert: Status log created
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'pending',
            'changed_by_user_id' => $customer->id
        ]);

        // Assert: Response structure
        $responseData = $response->json('data.order');
        $this->assertNotNull($responseData);
        $this->assertEquals($order->order_no, $responseData['order_no']);
        $this->assertEquals('pending', $responseData['status']);
        $this->assertEquals('Test order with no offers', $responseData['notes']);
        $this->assertCount(2, $responseData['items']);
        
        // Assert: Response totals
        $this->assertEquals(1275.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(1275.00, $responseData['final_total']);

        // Assert: Item details in response
        $item1 = collect($responseData['items'])->firstWhere('product_id', $product1->id);
        $this->assertNotNull($item1);
        $this->assertEquals(100, $item1['qty']);
        $this->assertEquals(10.00, $item1['unit_price']);
        $this->assertEquals(0.00, $item1['discount_amount']);
        $this->assertEquals(1000.00, $item1['final_total']);
        $this->assertNull($item1['selected_offer_id']);
        $this->assertEmpty($item1['bonuses']);
    }

    /**
     * Test: Order creation with single item and no offer
     * 
     * **Validates: Requirements 1.2, 1.3, 5.1**
     */
    #[Test]
    public function order_creation_succeeds_with_single_item_no_offer(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 25.75,
            'is_active' => true
        ]);

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'unit_price_snapshot' => 25.75,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 257.50,
                    'selected_offer_id' => null
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals(0, OrderItemBonus::count());
        
        $responseData = $response->json('data.order');
        $this->assertEquals(257.50, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(257.50, $responseData['final_total']);
    }
}
