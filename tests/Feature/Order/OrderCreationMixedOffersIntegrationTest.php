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
 * Integration Test: Order Creation with Multiple Items and Mixed Offers
 * 
 * **Validates: Requirements 5.1, 17.10, 17.11**
 * 
 * This test verifies the complete end-to-end flow of creating an order
 * with multiple items using different types of offers.
 */
class OrderCreationMixedOffersIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Order with multiple items using all three offer types
     * 
     * **Validates: Requirements 5.1, 17.10, 17.11**
     * 
     * Verifies that:
     * 1. Order is created successfully with HTTP 201
     * 2. All three offer types work correctly in same order
     * 3. Each item has correct calculations
     * 4. Bonuses are only created for bonus_qty offers
     * 5. Response contains correct totals
     */
    #[Test]
    public function order_creation_with_all_three_offer_types(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        // Product 1: Percentage discount
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true,
            'name' => 'Product with Percentage Discount'
        ]);

        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '10% Discount'
        ]);

        OfferItem::factory()->percentageDiscount(10.00)->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 100
        ]);

        // Product 2: Fixed discount
        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
            'is_active' => true,
            'name' => 'Product with Fixed Discount'
        ]);

        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '50 Off'
        ]);

        OfferItem::factory()->fixedDiscount(50.00)->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 50
        ]);

        // Product 3: Bonus quantity
        $product3 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 5.00,
            'is_active' => true,
            'name' => 'Product with Bonus'
        ]);

        $offer3 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => 'Buy 100 Get 10 Free'
        ]);

        OfferItem::factory()->bonusQty(10, $product3->id)->create([
            'offer_id' => $offer3->id,
            'product_id' => $product3->id,
            'min_qty' => 100
        ]);

        // Product 4: No offer
        $product4 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true,
            'name' => 'Product without Offer'
        ]);

        // Calculate expected values:
        // Product 1: qty=200, discount=(100*10*10)/100*2=200, total=2000-200=1800
        // Product 2: qty=100, discount=50*2=100, total=2000-100=1900
        // Product 3: qty=150, bonus=10*1=10, discount=0, total=750
        // Product 4: qty=50, discount=0, total=750
        // Totals: subtotal=5500, discount=300, final=5200

        $orderData = [
            'company_id' => $company->id,
            'notes' => 'Test order with mixed offers',
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 200,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 200.00,
                    'final_line_total_snapshot' => 1800.00,
                    'selected_offer_id' => $offer1->id
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 20.00,
                    'discount_amount_snapshot' => 100.00,
                    'final_line_total_snapshot' => 1900.00,
                    'selected_offer_id' => $offer2->id
                ],
                [
                    'product_id' => $product3->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 5.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 750.00,
                    'selected_offer_id' => $offer3->id
                ],
                [
                    'product_id' => $product4->id,
                    'qty' => 50,
                    'unit_price_snapshot' => 15.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 750.00,
                    'selected_offer_id' => null
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 2,
                    'bonus_product_id' => $product3->id,
                    'bonus_qty' => 10
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
        $this->assertEquals(4, $order->items()->count());

        // Assert: Only one bonus created (for product 3)
        $this->assertEquals(1, OrderItemBonus::count());

        // Assert: Response totals
        $responseData = $response->json('data.order');
        $this->assertEquals(5500.00, $responseData['subtotal']);
        $this->assertEquals(300.00, $responseData['total_discount']);
        $this->assertEquals(5200.00, $responseData['final_total']);
        $this->assertCount(4, $responseData['items']);

        // Assert: Each item has correct values
        $items = collect($responseData['items']);
        
        $item1 = $items->firstWhere('product_id', $product1->id);
        $this->assertEquals(200.00, $item1['discount_amount']);
        $this->assertEquals(1800.00, $item1['final_total']);
        $this->assertEmpty($item1['bonuses']);

        $item2 = $items->firstWhere('product_id', $product2->id);
        $this->assertEquals(100.00, $item2['discount_amount']);
        $this->assertEquals(1900.00, $item2['final_total']);
        $this->assertEmpty($item2['bonuses']);

        $item3 = $items->firstWhere('product_id', $product3->id);
        $this->assertEquals(0.00, $item3['discount_amount']);
        $this->assertEquals(750.00, $item3['final_total']);
        $this->assertCount(1, $item3['bonuses']);
        $this->assertEquals(10, $item3['bonuses'][0]['bonus_qty']);

        $item4 = $items->firstWhere('product_id', $product4->id);
        $this->assertEquals(0.00, $item4['discount_amount']);
        $this->assertEquals(750.00, $item4['final_total']);
        $this->assertEmpty($item4['bonuses']);
        $this->assertNull($item4['selected_offer_id']);
    }

    /**
     * Test: Order with some items having offers and some without
     * 
     * **Validates: Requirements 5.1, 8.5**
     */
    #[Test]
    public function order_creation_with_partial_offers(): void
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
            'base_price' => 8.00,
            'is_active' => true
        ]);

        $product3 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 6.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->percentageDiscount(15.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product2->id,
            'min_qty' => 50
        ]);

        // Product 1: no offer, total=100*12=1200
        // Product 2: 15% discount, discount=(50*8*15)/100=60, total=400-60=340
        // Product 3: no offer, total=30*6=180
        // Totals: subtotal=1780, discount=60, final=1720

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 12.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1200.00,
                    'selected_offer_id' => null
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 50,
                    'unit_price_snapshot' => 8.00,
                    'discount_amount_snapshot' => 60.00,
                    'final_line_total_snapshot' => 340.00,
                    'selected_offer_id' => $offer->id
                ],
                [
                    'product_id' => $product3->id,
                    'qty' => 30,
                    'unit_price_snapshot' => 6.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 180.00,
                    'selected_offer_id' => null
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(1780.00, $responseData['subtotal']);
        $this->assertEquals(60.00, $responseData['total_discount']);
        $this->assertEquals(1720.00, $responseData['final_total']);
        $this->assertCount(3, $responseData['items']);
    }

    /**
     * Test: Order with multiple items having same offer type
     * 
     * **Validates: Requirements 5.1, 17.10**
     */
    #[Test]
    public function order_creation_with_multiple_items_same_offer_type(): void
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
            'base_price' => 15.00,
            'is_active' => true
        ]);

        $product3 = Product::factory()->create([
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

        $offer3 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->fixedDiscount(30.00)->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 50
        ]);

        OfferItem::factory()->fixedDiscount(50.00)->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 100
        ]);

        OfferItem::factory()->fixedDiscount(75.00)->create([
            'offer_id' => $offer3->id,
            'product_id' => $product3->id,
            'min_qty' => 75
        ]);

        // Product 1: qty=100, discount=30*2=60, total=1000-60=940
        // Product 2: qty=150, discount=50*1=50, total=2250-50=2200
        // Product 3: qty=150, discount=75*2=150, total=3000-150=2850
        // Totals: subtotal=6250, discount=260, final=5990

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 60.00,
                    'final_line_total_snapshot' => 940.00,
                    'selected_offer_id' => $offer1->id
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 15.00,
                    'discount_amount_snapshot' => 50.00,
                    'final_line_total_snapshot' => 2200.00,
                    'selected_offer_id' => $offer2->id
                ],
                [
                    'product_id' => $product3->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 20.00,
                    'discount_amount_snapshot' => 150.00,
                    'final_line_total_snapshot' => 2850.00,
                    'selected_offer_id' => $offer3->id
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(6250.00, $responseData['subtotal']);
        $this->assertEquals(260.00, $responseData['total_discount']);
        $this->assertEquals(5990.00, $responseData['final_total']);
        $this->assertCount(3, $responseData['items']);
        
        // Assert: No bonuses created (all are discount offers)
        $this->assertEquals(0, OrderItemBonus::count());
    }
}
