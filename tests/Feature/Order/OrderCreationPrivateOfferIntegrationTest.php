<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\OfferTarget;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Creation with Private Offer
 * 
 * **Validates: Requirements 17.9**
 * 
 * This test verifies the complete end-to-end flow of creating an order
 * with private offers and customer targeting validation.
 */
class OrderCreationPrivateOfferIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Order creation succeeds with private offer for targeted customer
     * 
     * **Validates: Requirements 17.9**
     * 
     * Verifies that:
     * 1. Order is created successfully when customer is targeted
     * 2. Private offer is applied correctly
     * 3. Targeting validation works
     */
    #[Test]
    public function order_creation_succeeds_with_private_offer_for_targeted_customer(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Create private offer
        $offer = Offer::factory()->private()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'title' => 'Private 20% Discount'
        ]);

        OfferItem::factory()->percentageDiscount(20.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Target this customer
        OfferTarget::factory()->forCustomer($customer->id)->create([
            'offer_id' => $offer->id
        ]);

        // Calculate: qty=100, discount=(100*10*20)/100=200, total=1000-200=800

        $orderData = [
            'company_id' => $company->id,
            'notes' => 'Test order with private offer',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 200.00,
                    'final_line_total_snapshot' => 800.00,
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
            'qty' => 100,
            'unit_price_snapshot' => 10.00,
            'discount_amount_snapshot' => 200.00,
            'final_line_total_snapshot' => 800.00,
            'selected_offer_id' => $offer->id
        ]);

        // Assert: Response totals
        $responseData = $response->json('data.order');
        $this->assertEquals(1000.00, $responseData['subtotal']);
        $this->assertEquals(200.00, $responseData['total_discount']);
        $this->assertEquals(800.00, $responseData['final_total']);
    }

    /**
     * Test: Order creation fails with private offer for non-targeted customer
     * 
     * **Validates: Requirements 17.9**
     * 
     * Verifies that customers not in the target list cannot use private offers
     */
    #[Test]
    public function order_creation_fails_with_private_offer_for_non_targeted_customer(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $targetedCustomer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $nonTargetedCustomer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Create private offer
        $offer = Offer::factory()->private()->create([
            'company_user_id' => $company->id,
            'status' => 'active'
        ]);

        OfferItem::factory()->percentageDiscount(15.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        // Target only the first customer
        OfferTarget::factory()->forCustomer($targetedCustomer->id)->create([
            'offer_id' => $offer->id
        ]);

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 50,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 75.00,
                    'final_line_total_snapshot' => 425.00,
                    'selected_offer_id' => $offer->id
                ]
            ]
        ];

        // Act: Submit order as non-targeted customer
        $response = $this->actingAs($nonTargetedCustomer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert: HTTP 422 (tampering)
        $response->assertStatus(422)
            ->assertJson([
                'success' => false
            ]);

        // Assert: Error message mentions eligibility
        $this->assertStringContainsString('not eligible', $response->json('message'));

        // Assert: No order created
        $this->assertEquals(0, Order::count());
    }

    /**
     * Test: Order with private offer using fixed discount
     * 
     * **Validates: Requirements 17.9, 17.10**
     */
    #[Test]
    public function order_creation_with_private_offer_fixed_discount(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 25.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->private()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'title' => 'VIP 100 Off'
        ]);

        OfferItem::factory()->fixedDiscount(100.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        OfferTarget::factory()->forCustomer($customer->id)->create([
            'offer_id' => $offer->id
        ]);

        // Calculate: qty=150, multiplier=3, discount=100*3=300, total=3750-300=3450

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 150,
                    'unit_price_snapshot' => 25.00,
                    'discount_amount_snapshot' => 300.00,
                    'final_line_total_snapshot' => 3450.00,
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
        $this->assertEquals(3750.00, $responseData['subtotal']);
        $this->assertEquals(300.00, $responseData['total_discount']);
        $this->assertEquals(3450.00, $responseData['final_total']);
    }

    /**
     * Test: Order with private offer using bonus quantity
     * 
     * **Validates: Requirements 17.9, 17.11**
     */
    #[Test]
    public function order_creation_with_private_offer_bonus_qty(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 8.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->private()->create([
            'company_user_id' => $company->id,
            'status' => 'active',
            'title' => 'VIP Bonus Offer'
        ]);

        OfferItem::factory()->bonusQty(15, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        OfferTarget::factory()->forCustomer($customer->id)->create([
            'offer_id' => $offer->id
        ]);

        // Calculate: qty=200, multiplier=2, bonus=15*2=30

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 200,
                    'unit_price_snapshot' => 8.00,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => 1600.00,
                    'selected_offer_id' => $offer->id
                ]
            ],
            'order_item_bonuses' => [
                [
                    'order_item_index' => 0,
                    'bonus_product_id' => $product->id,
                    'bonus_qty' => 30
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(1600.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(1600.00, $responseData['final_total']);
        
        $bonus = $responseData['items'][0]['bonuses'][0];
        $this->assertEquals(30, $bonus['bonus_qty']);
    }

    /**
     * Test: Order with mix of public and private offers
     * 
     * **Validates: Requirements 17.9, 5.1**
     */
    #[Test]
    public function order_creation_with_mix_of_public_and_private_offers(): void
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

        // Public offer
        $publicOffer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active'
        ]);

        OfferItem::factory()->percentageDiscount(10.00)->create([
            'offer_id' => $publicOffer->id,
            'product_id' => $product1->id,
            'min_qty' => 100
        ]);

        // Private offer
        $privateOffer = Offer::factory()->private()->create([
            'company_user_id' => $company->id,
            'status' => 'active'
        ]);

        OfferItem::factory()->fixedDiscount(50.00)->create([
            'offer_id' => $privateOffer->id,
            'product_id' => $product2->id,
            'min_qty' => 50
        ]);

        OfferTarget::factory()->forCustomer($customer->id)->create([
            'offer_id' => $privateOffer->id
        ]);

        // Product 1: public offer, qty=100, discount=100, total=900
        // Product 2: private offer, qty=100, discount=100, total=1400
        // Totals: subtotal=2500, discount=200, final=2300

        $orderData = [
            'company_id' => $company->id,
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 10.00,
                    'discount_amount_snapshot' => 100.00,
                    'final_line_total_snapshot' => 900.00,
                    'selected_offer_id' => $publicOffer->id
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 100,
                    'unit_price_snapshot' => 15.00,
                    'discount_amount_snapshot' => 100.00,
                    'final_line_total_snapshot' => 1400.00,
                    'selected_offer_id' => $privateOffer->id
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders', $orderData);

        // Assert
        $response->assertStatus(201);
        
        $responseData = $response->json('data.order');
        $this->assertEquals(2500.00, $responseData['subtotal']);
        $this->assertEquals(200.00, $responseData['total_discount']);
        $this->assertEquals(2300.00, $responseData['final_total']);
        $this->assertCount(2, $responseData['items']);
    }
}
