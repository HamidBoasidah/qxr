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
 * Integration Test: Order Creation with Bonus Quantity
 * 
 * **Validates: Requirements 17.11, 7.2**
 * 
 * This test verifies the complete end-to-end flow of creating an order
 * with bonus quantity offers applied.
 */
class OrderCreationBonusQtyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete order creation flow with bonus quantity
     * 
     * **Validates: Requirements 17.11, 7.2**
     * 
     * Verifies that:
     * 1. Order is created successfully with HTTP 201
     * 2. Bonus quantity is calculated correctly
     * 3. Order items are persisted with zero discount
     * 4. Bonus is created and linked correctly
     * 5. Response contains correct bonus details
     */
    #[Test]
    public function order_creation_succeeds_with_bonus_qty(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Create offer: Buy 100, get 10 free
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => 'Buy 100 Get 10 Free'
        ]);

        $offerItem = OfferItem::factory()->bonusQty(10, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Calculate expected values
        // qty = 250, min_qty = 100, multiplier = floor(250/100) = 2
        // bonus_qty = 10 * 2 = 20
        // discount = 0 (bonus offers have no discount)
        // final total = 250 * 10.00 = 2500.00

        $orderData = [
            'company_id' => $company->id,
            'notes' => 'Test order with bonus quantity',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 250,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 2500.00,
                    'selected_offer_id' => $offer->id
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 20
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

        // Assert: Order item persisted with zero discount
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 250,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 0.00,
            'final_line_total_snapshot' => 2500.00,
            'selected_offer_id' => $offer->id
        ]);

        // Assert: Bonus created and linked correctly
        $orderItem = $order->items()->first();
        $this->assertDatabaseHas('order_item_bonuses', [
            'order_item_id' => $orderItem->id,
            'bonus_product_id' => $product->id,
            'bonus_qty' => 20,
            'offer_id' => $offer->id
        ]);

        // Assert: Exactly one bonus per item
        $this->assertEquals(1, OrderItemBonus::count());

        // Assert: Response totals
        $responseData = $response->json('data.order');
        $this->assertEquals(2500.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(2500.00, $responseData['final_total']);

        // Assert: Item details in response
        $item = $responseData['items'][0];
        $this->assertEquals(250, $item['qty']);
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(2500.00, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        
        // Assert: Bonus details in response
        $this->assertCount(1, $item['bonuses']);
        $bonus = $item['bonuses'][0];
        $this->assertEquals($product->id, $bonus['bonus_product_id']);
        $this->assertEquals($product->name, $bonus['bonus_product_name']);
        $this->assertEquals(20, $bonus['bonus_qty']);
        $this->assertEquals($offer->title, $bonus['offer_title']);
    }

    /**
     * Test: Order with bonus quantity and exact multiplier
     * 
     * **Validates: Requirements 17.11, 17.8**
     * 
     * Tests that bonus quantity is calculated correctly when qty exactly divides by min_qty
     */
    #[Test]
    public function order_creation_with_bonus_qty_exact_multiplier(): void
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

        OfferItem::factory()->bonusQty(5, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        // Calculate: qty = 150, min_qty = 50, multiplier = floor(150/50) = 3
        // bonus_qty = 5 * 3 = 15

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 5.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 750.00,
                    'selected_offer_id' => $offer->id
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 15
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
        $bonus = $orderItem->bonuses()->first();
        
        $this->assertEquals(15, $bonus->bonus_qty);
        $this->assertEquals($product->id, $bonus->bonus_product_id);
    }

    /**
     * Test: Order with bonus quantity and minimum quantity
     * 
     * **Validates: Requirements 17.11, 17.7**
     * 
     * Tests that bonus applies correctly when qty equals min_qty (multiplier = 1)
     */
    #[Test]
    public function order_creation_with_bonus_qty_minimum_quantity(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->bonusQty(3, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 30
        ]);

        // Calculate: qty = 30, min_qty = 30, multiplier = floor(30/30) = 1
        // bonus_qty = 3 * 1 = 3

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 30,
                    'unit_price_snapshot' => 20.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 600.00,
                    'selected_offer_id' => $offer->id
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 3
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(600.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(600.00, $responseData['final_total']);
        
        $bonus = $responseData['items'][0]['bonuses'][0];
        $this->assertEquals(3, $bonus['bonus_qty']);
    }

    /**
     * Test: Order with bonus quantity for different product
     * 
     * **Validates: Requirements 17.11, 7.5**
     * 
     * Tests that bonus can be a different product than the purchased product
     */
    #[Test]
    public function order_creation_with_bonus_qty_different_product(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $purchasedProduct = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true,
            'name' => 'Main Product'
        ]);

        $bonusProduct = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 5.00,
            'is_active' => true,
            'name' => 'Bonus Product'
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => 'Buy Main Product, Get Bonus Product Free'
        ]);

        OfferItem::factory()->bonusQty(10, $bonusProduct->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $purchasedProduct->id,
            'min_qty' => 100
        ]);

        // Calculate: qty = 200, min_qty = 100, multiplier = 2
        // bonus_qty = 10 * 2 = 20 of bonusProduct

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $purchasedProduct->id,
                    'qty' => 200,
                    'unit_price_snapshot' => 15.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 3000.00,
                    'selected_offer_id' => $offer->id
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $bonusProduct->id,
                    'bonus_qty' => 20
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
        
        // Assert: Order item is for purchased product
        $this->assertEquals($purchasedProduct->id, $orderItem->product_id);
        
        // Assert: Bonus is for different product
        $bonus = $orderItem->bonuses()->first();
        $this->assertEquals($bonusProduct->id, $bonus->bonus_product_id);
        $this->assertEquals(20, $bonus->bonus_qty);
        
        // Assert: Response shows correct bonus product
        $responseData = $response->json('data.order');
        $bonusData = $responseData['items'][0]['bonuses'][0];
        $this->assertEquals($bonusProduct->id, $bonusData['bonus_product_id']);
        $this->assertEquals('Bonus Product', $bonusData['bonus_product_name']);
    }
}
