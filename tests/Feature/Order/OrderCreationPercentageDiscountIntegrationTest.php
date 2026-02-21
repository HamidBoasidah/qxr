<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Creation with Percentage Discount
 * 
 * **Validates: Requirements 17.10, 6.6**
 * 
 * This test verifies the complete end-to-end flow of creating an order
 * with percentage discount offers applied.
 */
class OrderCreationPercentageDiscountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete order creation flow with percentage discount
     * 
     * **Validates: Requirements 17.10, 6.6**
     * 
     * Verifies that:
     * 1. Order is created successfully with HTTP 201
     * 2. Percentage discount is calculated correctly
     * 3. Order items are persisted with correct discount snapshots
     * 4. No bonuses are created (discount offers exclude bonuses)
     * 5. Response contains correct totals
     */
    #[Test]
    public function order_creation_succeeds_with_percentage_discount(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Create offer: Buy 100, get 10% discount
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '10% Discount on 100+ units'
        ]);

        $offerItem = OfferItem::factory()->percentageDiscount(10.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Calculate expected values
        // qty = 200, min_qty = 100, multiplier = floor(200/100) = 2
        // discount per block = (100 * 10.00 * 10) / 100 = 100.00
        // total discount = 100.00 * 2 = 200.00
        // final total = (200 * 10.00) - 200.00 = 1800.00

        $orderData = [
            'company_id' => $company->id,
            'notes' => 'Test order with percentage discount',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 200,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 200.00,
                    'final_line_total_snapshot' => 1800.00,
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
                'success' => true
            ])
            ->assertJsonPath('message', fn($message) => 
                in_array($message, ['Order created successfully', 'تم إنشاء الطلب بنجاح'])
            );

        // Assert: Order persisted
        $order = Order::where('customer_user_id', $customer->id)->first();
        $this->assertNotNull($order);

        // Assert: Order item persisted with correct discount
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 200,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 200.00,
            'final_line_total_snapshot' => 1800.00,
            'selected_offer_id' => $offer->id
        ]);

        // Assert: No bonuses created (discount offers exclude bonuses)
        $this->assertEquals(0, OrderItemBonus::count());

        // Assert: Response totals
        $responseData = $response->json('data.order');
        $this->assertEquals(2000.00, $responseData['subtotal']);
        $this->assertEquals(200.00, $responseData['total_discount']);
        $this->assertEquals(1800.00, $responseData['final_total']);

        // Assert: Item details in response
        $item = $responseData['items'][0];
        $this->assertEquals(200, $item['qty']);
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(200.00, $item['discount_amount']);
        $this->assertEquals(1800.00, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        $this->assertEmpty($item['bonuses']);
    }

    /**
     * Test: Order with percentage discount and fractional multiplier
     * 
     * **Validates: Requirements 17.10, 17.8**
     * 
     * Tests that multiplier is correctly floored when qty doesn't evenly divide by min_qty
     */
    #[Test]
    public function order_creation_with_percentage_discount_floors_multiplier(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 5.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->percentageDiscount(15.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        // Calculate: qty = 125, min_qty = 50, multiplier = floor(125/50) = 2
        // discount per block = (50 * 5.00 * 15) / 100 = 37.50
        // total discount = 37.50 * 2 = 75.00
        // final total = (125 * 5.00) - 75.00 = 550.00

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 125,
                    'unit_price_snapshot' => 5.00,
                    'discount_amount_snapshot' => 75.00,
                    'final_line_total_snapshot' => 550.00,
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
        
        $this->assertEquals(75.00, $orderItem->discount_amount_snapshot);
        $this->assertEquals(550.00, $orderItem->final_line_total_snapshot);
    }

    /**
     * Test: Order with multiple items having percentage discounts
     * 
     * **Validates: Requirements 17.10, 5.1**
     */
    #[Test]
    public function order_creation_with_multiple_percentage_discounts(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
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

        OfferItem::factory()->percentageDiscount(10.00)->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 100
        ]);

        OfferItem::factory()->percentageDiscount(20.00)->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 50
        ]);

        // Product 1: qty=100, discount=(100*10*10)/100=100, total=1000-100=900
        // Product 2: qty=100, multiplier=2, discount_per_block=(50*20*20)/100=200, total_discount=400, total=2000-400=1600

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 100.00,
                    'final_line_total_snapshot' => 900.00,
                    'selected_offer_id' => $offer1->id
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 20.00,
                    'discount_amount_snapshot' => 400.00,
                    'final_line_total_snapshot' => 1600.00,
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
        $this->assertEquals(3000.00, $responseData['subtotal']);
        $this->assertEquals(500.00, $responseData['total_discount']);
        $this->assertEquals(2500.00, $responseData['final_total']);
        $this->assertCount(2, $responseData['items']);
    }
}
