<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Order;
use App\Models\OrderItemBonus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Creation with Fixed Discount
 * 
 * **Validates: Requirements 17.10, 6.6**
 * 
 * This test verifies the complete end-to-end flow of creating an order
 * with fixed discount offers applied.
 */
class OrderCreationFixedDiscountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete order creation flow with fixed discount
     * 
     * **Validates: Requirements 17.10, 6.6**
     * 
     * Verifies that:
     * 1. Order is created successfully with HTTP 201
     * 2. Fixed discount is calculated correctly
     * 3. Order items are persisted with correct discount snapshots
     * 4. No bonuses are created (discount offers exclude bonuses)
     * 5. Response contains correct totals
     */
    #[Test]
    public function order_creation_succeeds_with_fixed_discount(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Create offer: Buy 50, get 25.00 discount
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '25 off per 50 units'
        ]);

        $offerItem = OfferItem::factory()->fixedDiscount(25.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        // Calculate expected values
        // qty = 150, min_qty = 50, multiplier = floor(150/50) = 3
        // total discount = 25.00 * 3 = 75.00
        // final total = (150 * 10.00) - 75.00 = 1425.00

        $orderData = [
            'company_id' => $company->id,
            'notes' => 'Test order with fixed discount',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 75.00,
                    'final_line_total_snapshot' => 1425.00,
                    'selected_offer_id' => $offer->id
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

        // Assert: Order persisted
        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);

        // Assert: Order item persisted with correct discount
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 150,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 75.00,
            'final_line_total_snapshot' => 1425.00,
            'selected_offer_id' => $offer->id
        ]);

        // Assert: No bonuses created (discount offers exclude bonuses)
        $this->assertEquals(0, OrderItemBonus::count());

        // Assert: Response totals
        $responseData = $response->json('data.order');
        $this->assertEquals(1500.00, $responseData['subtotal']);
        $this->assertEquals(75.00, $responseData['total_discount']);
        $this->assertEquals(1425.00, $responseData['final_total']);

        // Assert: Item details in response
        $item = $responseData['items'][0];
        $this->assertEquals(150, $item['qty']);
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(75.00, $item['discount_amount']);
        $this->assertEquals(1425.00, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        $this->assertEmpty($item['bonuses']);
    }

    /**
     * Test: Order with fixed discount and exact multiplier
     * 
     * **Validates: Requirements 17.10, 17.8**
     * 
     * Tests that multiplier works correctly when qty exactly divides by min_qty
     */
    #[Test]
    public function order_creation_with_fixed_discount_exact_multiplier(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 8.50,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->fixedDiscount(40.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Calculate: qty = 300, min_qty = 100, multiplier = floor(300/100) = 3
        // total discount = 40.00 * 3 = 120.00
        // final total = (300 * 8.50) - 120.00 = 2430.00

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 300,
                    'unit_price_snapshot' => 8.50,
                    'discount_amount_snapshot' => 120.00,
                    'final_line_total_snapshot' => 2430.00,
                    'selected_offer_id' => $offer->id
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $order = Order::where('customer_user_id', $customer->id)->first();
        $orderItem = $order->items()->first();
        
        $this->assertEquals(120.00, $orderItem->discount_amount_snapshot);
        $this->assertEquals(2430.00, $orderItem->final_line_total_snapshot);
    }

    /**
     * Test: Order with fixed discount and minimum quantity
     * 
     * **Validates: Requirements 17.10, 17.7**
     * 
     * Tests that discount applies correctly when qty equals min_qty (multiplier = 1)
     */
    #[Test]
    public function order_creation_with_fixed_discount_minimum_quantity(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->fixedDiscount(50.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 20
        ]);

        // Calculate: qty = 20, min_qty = 20, multiplier = floor(20/20) = 1
        // total discount = 50.00 * 1 = 50.00
        // final total = (20 * 15.00) - 50.00 = 250.00

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 20,
                    'unit_price_snapshot' => 15.00,
                    'discount_amount_snapshot' => 50.00,
                    'final_line_total_snapshot' => 250.00,
                    'selected_offer_id' => $offer->id
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(300.00, $responseData['subtotal']);
        $this->assertEquals(50.00, $responseData['total_discount']);
        $this->assertEquals(250.00, $responseData['final_total']);
    }

    /**
     * Test: Order with multiple items having fixed discounts
     * 
     * **Validates: Requirements 17.10, 5.1**
     */
    #[Test]
    public function order_creation_with_multiple_fixed_discounts(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 12.00,
            'is_active' => true
        ]);

        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 25.00,
            'is_active' => true
        ]);

        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->fixedDiscount(30.00)->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 50
        ]);

        OfferItem::factory()->fixedDiscount(100.00)->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 100
        ]);

        // Product 1: qty=100, multiplier=2, discount=30*2=60, total=1200-60=1140
        // Product 2: qty=150, multiplier=1, discount=100*1=100, total=3750-100=3650

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 12.00,
                    'discount_amount_snapshot' => 60.00,
                    'final_line_total_snapshot' => 1140.00,
                    'selected_offer_id' => $offer1->id
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 25.00,
                    'discount_amount_snapshot' => 100.00,
                    'final_line_total_snapshot' => 3650.00,
                    'selected_offer_id' => $offer2->id
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(4950.00, $responseData['subtotal']);
        $this->assertEquals(160.00, $responseData['total_discount']);
        $this->assertEquals(4790.00, $responseData['final_total']);
        $this->assertCount(2, $responseData['items']);
    }
}
