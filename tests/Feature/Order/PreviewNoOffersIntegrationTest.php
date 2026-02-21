<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Preview with No Offers
 * 
 * **Validates: Requirements 1.1-1.5, 5.14**
 * 
 * This test verifies the complete end-to-end preview flow from HTTP request
 * to response when no offers are eligible for the products.
 */
class PreviewNoOffersIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Complete preview flow with no offers
     * 
     * **Validates: Requirements 1.1-1.5, 5.14**
     * 
     * Verifies that:
     * 1. Preview request is accepted with HTTP 200
     * 2. Preview token is generated in correct format (PV-YYYYMMDD-XXXX)
     * 3. Preview data structure matches expected format
     * 4. Discount amounts are 0 for all items
     * 5. No bonuses are included
     * 6. Preview data is stored in cache (not database)
     * 7. Totals are calculated correctly
     */
    #[Test]
    public function preview_succeeds_with_no_offers(): void
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

        // Prepare preview request
        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test preview with no offers',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'qty' => 100
                ],
                [
                    'product_id' => $product2->id,
                    'qty' => 50
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
        $this->assertEquals('Test preview with no offers', $responseData['notes']);

        // Assert: Items count
        $this->assertCount(2, $responseData['items']);

        // Assert: Item 1 details (no offer applied)
        $item1 = collect($responseData['items'])->firstWhere('product_id', $product1->id);
        $this->assertNotNull($item1);
        $this->assertEquals($product1->id, $item1['product_id']);
        $this->assertEquals($product1->name, $item1['product_name']);
        $this->assertEquals(100, $item1['qty']);
        $this->assertEquals(10.00, $item1['unit_price']);
        $this->assertEquals(0.00, $item1['discount_amount']);
        $this->assertEquals(1000.00, $item1['final_total']);
        $this->assertNull($item1['selected_offer_id']);
        $this->assertNull($item1['offer_title']);
        $this->assertEmpty($item1['bonuses']);

        // Assert: Item 2 details (no offer applied)
        $item2 = collect($responseData['items'])->firstWhere('product_id', $product2->id);
        $this->assertNotNull($item2);
        $this->assertEquals($product2->id, $item2['product_id']);
        $this->assertEquals($product2->name, $item2['product_name']);
        $this->assertEquals(50, $item2['qty']);
        $this->assertEquals(5.50, $item2['unit_price']);
        $this->assertEquals(0.00, $item2['discount_amount']);
        $this->assertEquals(275.00, $item2['final_total']);
        $this->assertNull($item2['selected_offer_id']);
        $this->assertNull($item2['offer_title']);
        $this->assertEmpty($item2['bonuses']);

        // Assert: Totals calculation
        // Subtotal = sum of (qty × unit_price) for all items
        // Item 1: 100 × 10.00 = 1000.00
        // Item 2: 50 × 5.50 = 275.00
        // Subtotal = 1275.00
        $this->assertEquals(1275.00, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(1275.00, $responseData['final_total']);

        // Assert: Preview stored in cache (not database)
        $previewToken = $responseData['preview_token'];
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertEquals($customer->id, $cachedPreview['customer_user_id']);
        $this->assertEquals($company->id, $cachedPreview['company_id']);
        $this->assertEquals('Test preview with no offers', $cachedPreview['notes'] ?? null);

        // Assert: No database persistence (preview only)
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_item_bonuses', 0);
        $this->assertDatabaseCount('order_status_logs', 0);
    }

    /**
     * Test: Preview with single item and no offer
     * 
     * **Validates: Requirements 1.1-1.5, 5.14**
     */
    #[Test]
    public function preview_succeeds_with_single_item_no_offer(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 25.75,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10
                ]
            ]
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        
        // Assert: Preview token format
        $this->assertMatchesRegularExpression(
            '/^PV-\d{8}-[A-Z0-9]{4}$/',
            $responseData['preview_token']
        );

        // Assert: Single item with no offer
        $this->assertCount(1, $responseData['items']);
        $item = $responseData['items'][0];
        $this->assertEquals($product->id, $item['product_id']);
        $this->assertEquals(10, $item['qty']);
        $this->assertEquals(25.75, $item['unit_price']);
        $this->assertEquals(0.00, $item['discount_amount']);
        $this->assertEquals(257.50, $item['final_total']);
        $this->assertNull($item['selected_offer_id']);
        $this->assertEmpty($item['bonuses']);

        // Assert: Totals
        $this->assertEquals(257.50, $responseData['subtotal']);
        $this->assertEquals(0.00, $responseData['total_discount']);
        $this->assertEquals(257.50, $responseData['final_total']);

        // Assert: No database persistence
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Preview with notes omitted
     * 
     * **Validates: Requirements 1.2, 7.6**
     */
    #[Test]
    public function preview_succeeds_without_notes(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
            'is_active' => true
        ]);

        $previewData = [
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5
                ]
            ]
            // notes intentionally omitted
        ];

        // Act
        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        // Assert
        $response->assertStatus(200);
        
        $responseData = $response->json('data');
        
        // Assert: Notes should be null when omitted
        $this->assertNull($responseData['notes']);

        // Assert: Preview stored in cache with null notes
        $previewToken = $responseData['preview_token'];
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertNull($cachedPreview['notes']);
    }
}
