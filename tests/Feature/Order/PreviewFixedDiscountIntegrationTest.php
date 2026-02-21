<?php

namespace Tests\Feature\Order;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Preview with Fixed Discount
 * 
 * **Validates: Requirements 5.8, 6.4**
 * 
 * This test verifies the complete end-to-end preview flow from HTTP request
 * to response when fixed discount offers are applied.
 */
class PreviewFixedDiscountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete preview flow with fixed discount
     * 
     * **Validates: Requirements 5.8, 6.4**
     * 
     * Verifies that:
     * 1. Preview request is accepted with HTTP 200
     * 2. Fixed discount is calculated correctly: reward_value × multiplier
     * 3. Preview response includes offer details
     * 4. Discount amounts are correct for all items
     * 5. No bonuses are included (discount offers exclude bonuses)
     * 6. Preview data is stored in cache (not database)
     * 7. Totals are calculated correctly
     */
    #[Test]
    public function preview_succeeds_with_fixed_discount(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Create offer: Buy 100, get $50 fixed discount per block
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '$50 Discount on 100+ units',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->fixedDiscount(50.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Calculate expected values
        // qty = 200, min_qty = 100, multiplier = floor(200/100) = 2
        // discount = reward_value × multiplier = 50.00 * 2 = 100.00
        // subtotal = 200 * 10.00 = 2000.00
        // final total = 2000.00 - 100.00 = 1900.00

        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test preview with fixed discount',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 200
                ]
            ]
        ];

        // Act: Submit preview request
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert: HTTP response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $responseData = $response->json('data');

        // Assert: Preview token format (PV-YYYYMMDD-XXXX)
        $this->assertNotNull($responseData['preview_token']);
        $this->assertMatchesRegularExpression(
            '/^PV-\d{8}-[A-Z0-9]{4}$/',
            $responseData['preview_token']
        );

        // Assert: Preview data structure
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('subtotal', $responseData);
        $this->assertArrayHasKey('total_discount', $responseData);
        $this->assertArrayHasKey('final_total', $responseData);
        $this->assertArrayHasKey('notes', $responseData);
        $this->assertArrayHasKey('created_at', $responseData);

        // Assert: Notes persistence
        $this->assertEquals('Test preview with fixed discount', $responseData['notes']);

        // Assert: Items count
        $this->assertCount(1, $responseData['items']);

        // Assert: Item details with fixed discount
        $item = $responseData['items'][0];
        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals($product->name, $item['product_name']);
        $this->assertEquals(200, $item['qty']);
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(100.00, $item['discount_amount']);
        $this->assertEquals(1900.00, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        $this->assertEquals('$50 Discount on 100+ units', $item['offer_title']);
        $this->assertEmpty($item['bonuses']);

        // Assert: Totals calculation
        // Subtotal = 200 * 10.00 = 2000.00
        // Total discount = 100.00
        // Final total = 2000.00 - 100.00 = 1900.00
        $this->assertEquals(2000.00, $responseData['subtotal']);
        $this->assertEquals(100.00, $responseData['total_discount']);
        $this->assertEquals(1900.00, $responseData['final_total']);

        // Assert: Preview stored in cache (not database)
        $previewToken = $responseData['preview_token'];
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertEquals($customer->id, $cachedPreview['customer_user_id']);
        $this->assertEquals($company->id, $cachedPreview['company_id']);
        $this->assertEquals('Test preview with fixed discount', $cachedPreview['notes']);

        // Assert: No database persistence (preview only)
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_item_bonuses', 0);
        $this->assertDatabaseCount('order_status_logs', 0);
    }

    /**
     * Test: Preview with fixed discount and fractional multiplier
     * 
     * **Validates: Requirements 5.8, 6.4, 5.4**
     * 
     * Tests that multiplier is correctly floored when qty doesn't evenly divide by min_qty
     */
    #[Test]
    public function preview_with_fixed_discount_floors_multiplier(): void
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
            'status' => 'active',
            'title' => '$25 Off 50+ units',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->fixedDiscount(25.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        // Calculate: qty = 125, min_qty = 50, multiplier = floor(125/50) = 2
        // discount = reward_value × multiplier = 25.00 * 2 = 50.00
        // subtotal = 125 * 5.00 = 625.00
        // final total = 625.00 - 50.00 = 575.00

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 125
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        $item = $responseData['items'][0];
        
        $this->assertEquals(125, $item['qty']);
        $this->assertEquals(5.00, $item['unit_price']);
        $this->assertEquals(50.00, $item['discount_amount']);
        $this->assertEquals(575.00, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);

        // Assert totals
        $this->assertEquals(625.00, $responseData['subtotal']);
        $this->assertEquals(50.00, $responseData['total_discount']);
        $this->assertEquals(575.00, $responseData['final_total']);
    }

    /**
     * Test: Preview with multiple items having fixed discounts
     * 
     * **Validates: Requirements 5.8, 6.4, 5.1**
     */
    #[Test]
    public function preview_with_multiple_fixed_discounts(): void
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
            'status' => 'active',
            'title' => '$100 Off 100+ units',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '$200 Off 50+ units',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->fixedDiscount(100.00)->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 100
        ]);

        OfferItem::factory()->fixedDiscount(200.00)->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 50
        ]);

        // Product 1: qty=100, multiplier=1, discount=100*1=100, subtotal=1000, final=900
        // Product 2: qty=100, multiplier=2, discount=200*2=400, subtotal=2000, final=1600

        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Multiple fixed discounts',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 100
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        
        // Assert: Items count
        $this->assertCount(2, $responseData['items']);

        // Assert: Product 1 details
        $item1 = collect($responseData['items'])->firstWhere('product_id', $product1->id);
        $this->assertEquals(100, $item1['qty']);
        $this->assertEquals(10.00, $item1['unit_price']);
        $this->assertEquals(100.00, $item1['discount_amount']);
        $this->assertEquals(900.00, $item1['final_total']);
        $this->assertEquals($offer1->id, $item1['selected_offer_id']);

        // Assert: Product 2 details
        $item2 = collect($responseData['items'])->firstWhere('product_id', $product2->id);
        $this->assertEquals(100, $item2['qty']);
        $this->assertEquals(20.00, $item2['unit_price']);
        $this->assertEquals(400.00, $item2['discount_amount']);
        $this->assertEquals(1600.00, $item2['final_total']);
        $this->assertEquals($offer2->id, $item2['selected_offer_id']);

        // Assert: Totals
        $this->assertEquals(3000.00, $responseData['subtotal']);
        $this->assertEquals(500.00, $responseData['total_discount']);
        $this->assertEquals(2500.00, $responseData['final_total']);
    }

    /**
     * Test: Preview with fixed discount below minimum quantity
     * 
     * **Validates: Requirements 5.5, 5.14**
     * 
     * Tests that when qty < min_qty (multiplier = 0), no offer is applied
     */
    #[Test]
    public function preview_with_fixed_discount_below_min_qty_applies_no_offer(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '$100 Off 100+ units',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->fixedDiscount(100.00)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // qty = 50, min_qty = 100, multiplier = floor(50/100) = 0
        // No offer should be applied

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 50
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        $item = $responseData['items'][0];
        
        // Assert: No offer applied (multiplier = 0)
        $this->assertEquals(50, $item['qty']);
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(500.00, $item['final_total']);
        $this->assertNull($item['selected_offer_id']);
        $this->assertNull($item['offer_title']);
        $this->assertEmpty($item['bonuses']);

        // Assert: Totals
        $this->assertEquals(500.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(500.00, $responseData['final_total']);
    }

    /**
     * Test: Preview with fixed discount and rounding
     * 
     * **Validates: Requirements 5.8, 6.4, 6.2**
     * 
     * Tests that fixed discount calculations are properly rounded
     */
    #[Test]
    public function preview_with_fixed_discount_applies_rounding(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 7.33,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => '$15.50 Off 10+ units',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->fixedDiscount(15.50)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 10
        ]);

        // Calculate: qty = 25, min_qty = 10, multiplier = floor(25/10) = 2
        // discount = 15.50 * 2 = 31.00
        // subtotal = 25 * 7.33 = 183.25
        // final total = 183.25 - 31.00 = 152.25

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 25
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        $item = $responseData['items'][0];
        
        $this->assertEquals(25, $item['qty']);
        $this->assertEquals(7.33, $item['unit_price']);
        $this->assertEquals(31.00, $item['discount_amount']);
        $this->assertEquals(152.25, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);

        // Assert totals
        $this->assertEquals(183.25, $responseData['subtotal']);
        $this->assertEquals(31.00, $responseData['total_discount']);
        $this->assertEquals(152.25, $responseData['final_total']);
    }
}
