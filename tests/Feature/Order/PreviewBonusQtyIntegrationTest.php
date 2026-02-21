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
 * Integration Test: Order Preview with Bonus Quantity
 * 
 * **Validates: Requirements 5.9, 6.5**
 * 
 * This test verifies the complete end-to-end preview flow from HTTP request
 * to response when bonus quantity offers are applied.
 */
class PreviewBonusQtyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete preview flow with bonus qty
     * 
     * **Validates: Requirements 5.9, 6.5**
     * 
     * Verifies that:
     * 1. Preview request is accepted with HTTP 200
     * 2. Bonus quantity is calculated correctly: reward_value × multiplier (integer)
     * 3. Preview response includes bonus details in bonuses array
     * 4. No discount is applied (discount_amount = 0 for bonus offers)
     * 5. Bonuses array includes bonus_product_id, bonus_product_name, bonus_qty, offer_title
     * 6. Preview data is stored in cache (not database)
     * 7. Totals are calculated correctly (no discount for bonus offers)
     */
    #[Test]
    public function preview_succeeds_with_bonus_qty(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true,
            'name' => 'Test Product'
        ]);

        // Create offer: Buy 100, get 10 free
        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => 'Buy 100 Get 10 Free',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->bonusQty(10, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Calculate expected values
        // qty = 250, min_qty = 100, multiplier = floor(250/100) = 2
        // bonus_qty = reward_value × multiplier = 10 * 2 = 20
        // discount_amount = 0 (bonus offers have no discount)
        // subtotal = 250 * 10.00 = 2500.00
        // final total = 2500.00 (no discount)

        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test preview with bonus qty',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 250
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
        $this->assertEquals('Test preview with bonus qty', $responseData['notes']);

        // Assert: Items count
        $this->assertCount(1, $responseData['items']);

        // Assert: Item details with bonus qty offer
        $item = $responseData['items'][0];
        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals('Test Product', $item['product_name']);
        $this->assertEquals(250, $item['qty']);
        $this->assertEquals(10.00, $item['unit_price']);
        $this->assertEquals(0.00, $item['discount_amount']); // No discount for bonus offers
        $this->assertEquals(2500.00, $item['final_total']);
        $this->assertEquals($offer->id, $item['selected_offer_id']);
        $this->assertEquals('Buy 100 Get 10 Free', $item['offer_title']);

        // Assert: Bonuses array structure and content
        $this->assertArrayHasKey('bonuses', $item);
        $this->assertCount(1, $item['bonuses']);
        
        $bonus = $item['bonuses'][0];
        $this->assertEquals($product->id, $bonus['bonus_product_id']);
        $this->assertEquals('Test Product', $bonus['bonus_product_name']);
        $this->assertEquals(20, $bonus['bonus_qty']); // 10 * 2 = 20
        $this->assertEquals('Buy 100 Get 10 Free', $bonus['offer_title']);

        // Assert: Totals calculation (no discount for bonus offers)
        // Subtotal = 250 * 10.00 = 2500.00
        // Total discount = 0.00 (bonus offers don't provide discounts)
        // Final total = 2500.00
        $this->assertEquals(2500.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(2500.00, $responseData['final_total']);

        // Assert: Preview stored in cache (not database)
        $previewToken = $responseData['preview_token'];
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertEquals($customer->id, $cachedPreview['customer_user_id']);
        $this->assertEquals($company->id, $cachedPreview['company_id']);
        $this->assertEquals('Test preview with bonus qty', $cachedPreview['notes']);

        // Assert: No database persistence (preview only)
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_item_bonuses', 0);
        $this->assertDatabaseCount('order_status_logs', 0);
    }

    /**
     * Test: Preview with bonus qty and fractional multiplier
     * 
     * **Validates: Requirements 5.9, 6.5, 5.4**
     * 
     * Tests that multiplier is correctly floored when qty doesn't evenly divide by min_qty
     */
    #[Test]
    public function preview_with_bonus_qty_floors_multiplier(): void
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
            'title' => 'Buy 50 Get 5 Free',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->bonusQty(5, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 50
        ]);

        // Calculate: qty = 125, min_qty = 50, multiplier = floor(125/50) = 2
        // bonus_qty = 5 * 2 = 10
        // subtotal = 125 * 5.00 = 625.00
        // final total = 625.00 (no discount)

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
        
        $item = $response->json('data.items.0');
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(625.00, $item['final_total']);
        
        $bonus = $item['bonuses'][0];
        $this->assertEquals(10, $bonus['bonus_qty']); // floor(125/50) * 5 = 2 * 5 = 10
    }

    /**
     * Test: Preview with bonus qty for different product
     * 
     * **Validates: Requirements 5.9, 6.5**
     * 
     * Tests that bonus can be a different product than the purchased product
     */
    #[Test]
    public function preview_with_bonus_qty_different_product(): void
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
            'title' => 'Buy Main Product, Get Bonus Product Free',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->bonusQty(10, $bonusProduct->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $purchasedProduct->id,
            'min_qty' => 100
        ]);

        // Calculate: qty = 200, min_qty = 100, multiplier = 2
        // bonus_qty = 10 * 2 = 20 of bonusProduct
        // subtotal = 200 * 15.00 = 3000.00
        // final total = 3000.00 (no discount)

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $purchasedProduct->id,
                    'qty' => 200
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $item = $response->json('data.items.0');
        $this->assertEquals($purchasedProduct->id, $item['product_id']);
        $this->assertEquals('Main Product', $item['product_name']);
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(3000.00, $item['final_total']);
        
        // Assert: Bonus is for different product
        $bonus = $item['bonuses'][0];
        $this->assertEquals($bonusProduct->id, $bonus['bonus_product_id']);
        $this->assertEquals('Bonus Product', $bonus['bonus_product_name']);
        $this->assertEquals(20, $bonus['bonus_qty']);
        $this->assertEquals('Buy Main Product, Get Bonus Product Free', $bonus['offer_title']);
    }

    /**
     * Test: Preview with bonus qty at minimum quantity
     * 
     * **Validates: Requirements 5.9, 6.5**
     * 
     * Tests that bonus applies correctly when qty equals min_qty (multiplier = 1)
     */
    #[Test]
    public function preview_with_bonus_qty_minimum_quantity(): void
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
            'status' => 'active',
            'title' => 'Buy 30 Get 3 Free',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->bonusQty(3, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 30
        ]);

        // Calculate: qty = 30, min_qty = 30, multiplier = floor(30/30) = 1
        // bonus_qty = 3 * 1 = 3
        // subtotal = 30 * 20.00 = 600.00
        // final total = 600.00 (no discount)

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 30
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        $this->assertEquals(600.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(600.00, $responseData['final_total']);
        
        $bonus = $responseData['items'][0]['bonuses'][0];
        $this->assertEquals(3, $bonus['bonus_qty']);
    }

    /**
     * Test: Preview with bonus qty below minimum quantity
     * 
     * **Validates: Requirements 5.5, 5.14**
     * 
     * Tests that no bonus is applied when qty < min_qty (multiplier = 0)
     */
    #[Test]
    public function preview_with_bonus_qty_below_minimum(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 8.00,
            'is_active' => true
        ]);

        $offer = Offer::factory()->create([
            'company_user_id' => $company->id,
            'scope' => 'public',
            'status' => 'active',
            'title' => 'Buy 100 Get 10 Free',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay()
        ]);

        OfferItem::factory()->bonusQty(10, $product->id)->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 100
        ]);

        // Calculate: qty = 50, min_qty = 100, multiplier = floor(50/100) = 0
        // No offer applied (multiplier = 0)
        // subtotal = 50 * 8.00 = 400.00
        // final total = 400.00

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
        
        $item = $response->json('data.items.0');
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(400.00, $item['final_total']);
        $this->assertNull($item['selected_offer_id']); // No offer applied
        $this->assertEmpty($item['bonuses']); // No bonuses
    }
}
