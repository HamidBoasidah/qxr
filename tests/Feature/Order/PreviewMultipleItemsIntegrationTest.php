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
 * Integration Test: Order Preview with Multiple Items
 * 
 * **Validates: Requirements 1.2, 5.1-5.14, 6.1-6.9**
 * 
 * This test verifies the complete end-to-end preview flow with multiple items
 * and mixed offer types (percentage discount, fixed discount, bonus qty, no offer).
 */
class PreviewMultipleItemsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Preview with multiple items and mixed offers
     * 
     * **Validates: Requirements 1.2, 5.1-5.14, 6.1-6.9**
     * 
     * Verifies that:
     * 1. Preview request with multiple items is accepted with HTTP 200
     * 2. Each item has correct offer selected based on best offer logic
     * 3. Pricing calculations are correct for all offer types
     * 4. Totals are calculated correctly across all items
     * 5. Preview data is stored in cache
     */
    #[Test]
    public function preview_succeeds_with_multiple_items_and_mixed_offers(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        // Product 1: With percentage discount offer
        $product1 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);
        
        $offer1 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '10% Off 100+',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer1->id,
            'product_id' => $product1->id,
            'min_qty' => 100,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Product 2: With fixed discount offer
        $product2 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 5.50,
            'is_active' => true
        ]);
        
        $offer2 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => '$25 Off 50+',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer2->id,
            'product_id' => $product2->id,
            'min_qty' => 50,
            'reward_type' => 'discount_fixed',
            'discount_percent' => null,
            'discount_fixed' => 25.00,
            'bonus_product_id' => null,
            'bonus_qty' => null
        ]);
        
        // Product 3: With bonus qty offer
        $product3 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
            'is_active' => true
        ]);
        
        $offer3 = Offer::factory()->create([
            'company_user_id' => $company->id,
            'title' => 'Buy 10 Get 2 Free',
            'scope' => 'public',
            'status' => 'active',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);
        
        OfferItem::factory()->create([
            'offer_id' => $offer3->id,
            'product_id' => $product3->id,
            'min_qty' => 10,
            'reward_type' => 'bonus_qty',
            'discount_percent' => null,
            'discount_fixed' => null,
            'bonus_product_id' => $product3->id,
            'bonus_qty' => 2
        ]);
        
        // Product 4: No offer
        $product4 = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true
        ]);

        // Prepare preview request
        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test preview with multiple items',
            'items' => [
                ['product_id' => $product1->id, 'qty' => 200],  // 2x multiplier
                ['product_id' => $product2->id, 'qty' => 100],  // 2x multiplier
                ['product_id' => $product3->id, 'qty' => 25],   // 2x multiplier
                ['product_id' => $product4->id, 'qty' => 10]    // No offer
            ]
        ];

        // Act: Submit preview request
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert: HTTP response
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $responseData = $response->json('data');

        // Assert: Preview token format
        $this->assertMatchesRegularExpression(
            '/^PV-\d{8}-[A-Z0-9]{4}$/',
            $responseData['preview_token']
        );

        // Assert: Items count
        $this->assertCount(4, $responseData['items']);

        // Assert: Item 1 - Percentage discount (10% off, 2x multiplier)
        // Calculation: 
        // - unit_price = 10.00
        // - line_subtotal = 200 × 10.00 = 2000.00
        // - discount_per_block = (100 × 10.00 × 10 / 100) = 100.00
        // - discount_amount = 100.00 × 2 = 200.00
        // - final_total = 2000.00 - 200.00 = 1800.00
        $item1 = collect($responseData['items'])->firstWhere('product_id', $product1->id);
        $this->assertNotNull($item1);
        $this->assertEquals(200, $item1['qty']);
        $this->assertEquals(10.00, $item1['unit_price']);
        $this->assertEquals(200.00, $item1['discount_amount']);
        $this->assertEquals(1800.00, $item1['final_total']);
        $this->assertEquals($offer1->id, $item1['selected_offer_id']);
        $this->assertEquals('10% Off 100+', $item1['offer_title']);
        $this->assertEmpty($item1['bonuses']);

        // Assert: Item 2 - Fixed discount ($25 off, 2x multiplier)
        // Calculation:
        // - unit_price = 5.50
        // - line_subtotal = 100 × 5.50 = 550.00
        // - discount_amount = 25.00 × 2 = 50.00
        // - final_total = 550.00 - 50.00 = 500.00
        $item2 = collect($responseData['items'])->firstWhere('product_id', $product2->id);
        $this->assertNotNull($item2);
        $this->assertEquals(100, $item2['qty']);
        $this->assertEquals(5.50, $item2['unit_price']);
        $this->assertEquals(50.00, $item2['discount_amount']);
        $this->assertEquals(500.00, $item2['final_total']);
        $this->assertEquals($offer2->id, $item2['selected_offer_id']);
        $this->assertEquals('$25 Off 50+', $item2['offer_title']);
        $this->assertEmpty($item2['bonuses']);

        // Assert: Item 3 - Bonus qty (2 free per 10, 2x multiplier)
        // Calculation:
        // - unit_price = 20.00
        // - line_subtotal = 25 × 20.00 = 500.00
        // - discount_amount = 0.00 (bonus offers don't discount)
        // - final_total = 500.00
        // - bonus_qty = 2 × 2 = 4
        $item3 = collect($responseData['items'])->firstWhere('product_id', $product3->id);
        $this->assertNotNull($item3);
        $this->assertEquals(25, $item3['qty']);
        $this->assertEquals(20.00, $item3['unit_price']);
        $this->assertEquals(0.00, $item3['discount_amount']);
        $this->assertEquals(500.00, $item3['final_total']);
        $this->assertEquals($offer3->id, $item3['selected_offer_id']);
        $this->assertEquals('Buy 10 Get 2 Free', $item3['offer_title']);
        $this->assertCount(1, $item3['bonuses']);
        $this->assertEquals($product3->id, $item3['bonuses'][0]['bonus_product_id']);
        $this->assertEquals(4, $item3['bonuses'][0]['bonus_qty']);

        // Assert: Item 4 - No offer
        // Calculation:
        // - unit_price = 15.00
        // - line_subtotal = 10 × 15.00 = 150.00
        // - discount_amount = 0.00
        // - final_total = 150.00
        $item4 = collect($responseData['items'])->firstWhere('product_id', $product4->id);
        $this->assertNotNull($item4);
        $this->assertEquals(10, $item4['qty']);
        $this->assertEquals(15.00, $item4['unit_price']);
        $this->assertEquals(0.00, $item4['discount_amount']);
        $this->assertEquals(150.00, $item4['final_total']);
        $this->assertNull($item4['selected_offer_id']);
        $this->assertNull($item4['offer_title']);
        $this->assertEmpty($item4['bonuses']);

        // Assert: Totals calculation
        // Subtotal = sum of line_subtotal for all items
        // Item 1: 200 × 10.00 = 2000.00
        // Item 2: 100 × 5.50 = 550.00
        // Item 3: 25 × 20.00 = 500.00
        // Item 4: 10 × 15.00 = 150.00
        // Subtotal = 3200.00
        // Total discount = 200.00 + 50.00 + 0.00 + 0.00 = 250.00
        // Final total = 1800.00 + 500.00 + 500.00 + 150.00 = 2950.00
        $this->assertEquals(3200.00, $responseData['subtotal']);
        $this->assertEquals(250.00, $responseData['total_discount']);
        $this->assertEquals(2950.00, $responseData['final_total']);

        // Assert: Preview stored in cache
        $previewToken = $responseData['preview_token'];
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertEquals($customer->id, $cachedPreview['customer_user_id']);
        $this->assertEquals($company->id, $cachedPreview['company_id']);

        // Assert: No database persistence
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    /**
     * Test: Preview with 5+ items
     * 
     * **Validates: Requirements 1.2, 6.7-6.9**
     */
    #[Test]
    public function preview_succeeds_with_many_items(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $products = [];
        $items = [];
        $expectedSubtotal = 0.0;
        
        for ($i = 1; $i <= 6; $i++) {
            $price = $i * 5.00;
            $qty = $i * 10;
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $price,
                'is_active' => true
            ]);
            
            $products[] = $product;
            $items[] = ['product_id' => $product->id, 'qty' => $qty];
            $expectedSubtotal += round($qty * $price, 2);
        }

        $previewData = [
            'company_id' => $company->id,
            'items' => $items
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        $this->assertCount(6, $responseData['items']);
        $this->assertEquals(round($expectedSubtotal, 2), $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(round($expectedSubtotal, 2), $responseData['final_total']);
    }
}
