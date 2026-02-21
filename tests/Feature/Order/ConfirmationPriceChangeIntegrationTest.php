<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Confirmation with Price Change
 * 
 * **Validates: Requirements 9.8, 9.18**
 * 
 * This test verifies the complete end-to-end flow when product prices change
 * between preview and confirmation, resulting in HTTP 409 response.
 */
class ConfirmationPriceChangeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Confirmation fails when price increases
     * 
     * **Validates: Requirements 9.8, 9.18**
     * 
     * Verifies that:
     * 1. Preview is created with original price
     * 2. Price changes before confirmation
     * 3. Confirmation returns HTTP 409
     * 4. Response includes price change details
     * 5. Preview token is kept in cache (not deleted)
     * 6. No order is persisted
     */
    #[Test]
    public function confirmation_fails_when_price_increases(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Create preview with original price
        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Test price change',
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewResponse->assertStatus(200);
        $previewToken = $previewResponse->json('data.preview_token');

        // Verify preview has original price
        $previewItem = $previewResponse->json('data.items.0');
        $this->assertEquals(10.00, $previewItem['unit_price']);
        $this->assertEquals(1000.00, $previewItem['final_total']);

        // Step 2: Change product price
        $product->update(['base_price' => 12.00]);

        // Step 3: Attempt to confirm order
        $confirmData = ['preview_token' => $previewToken];

        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', $confirmData);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Preview is no longer valid. Please re-preview your order.'
            ]);

        // Assert: Response includes price change details
        $details = $confirmResponse->json('details');
        $this->assertNotEmpty($details);
        
        $priceChange = collect($details)->firstWhere('type', 'price_changed');
        $this->assertNotNull($priceChange);
        $this->assertEquals($product->id, $priceChange['product_id']);
        $this->assertEquals($product->name, $priceChange['product_name']);
        $this->assertEquals(10.00, $priceChange['preview_price']);
        $this->assertEquals(12.00, $priceChange['current_price']);

        // Assert: Preview token is kept in cache
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview, 'Preview token should be kept in cache for re-preview');

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    /**
     * Test: Confirmation fails when price decreases
     * 
     * **Validates: Requirements 9.8, 9.18**
     */
    #[Test]
    public function confirmation_fails_when_price_decreases(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 50]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Decrease product price
        $product->update(['base_price' => 8.50]);

        // Step 3: Attempt to confirm
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409);

        $details = $confirmResponse->json('details');
        $priceChange = collect($details)->firstWhere('type', 'price_changed');
        $this->assertNotNull($priceChange);
        $this->assertEquals(10.00, $priceChange['preview_price']);
        $this->assertEquals(8.50, $priceChange['current_price']);

        // Assert: Preview token kept
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Confirmation fails when multiple prices change
     * 
     * **Validates: Requirements 9.8, 9.18**
     */
    #[Test]
    public function confirmation_fails_when_multiple_prices_change(): void
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
            'base_price' => 5.00,
            'is_active' => true
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product1->id, 'qty' => 100],
                ['product_id' => $product2->id, 'qty' => 50]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Change both prices
        $product1->update(['base_price' => 11.00]);
        $product2->update(['base_price' => 4.50]);

        // Step 3: Attempt to confirm
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: HTTP 409 response
        $confirmResponse->assertStatus(409);

        // Assert: Both price changes reported
        $details = $confirmResponse->json('details');
        $priceChanges = collect($details)->where('type', 'price_changed');
        $this->assertCount(2, $priceChanges);

        $change1 = $priceChanges->firstWhere('product_id', $product1->id);
        $this->assertNotNull($change1);
        $this->assertEquals(10.00, $change1['preview_price']);
        $this->assertEquals(11.00, $change1['current_price']);

        $change2 = $priceChanges->firstWhere('product_id', $product2->id);
        $this->assertNotNull($change2);
        $this->assertEquals(5.00, $change2['preview_price']);
        $this->assertEquals(4.50, $change2['current_price']);

        // Assert: Preview token kept
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Confirmation succeeds when price change is within tolerance
     * 
     * **Validates: Requirements 9.8**
     * 
     * Note: Price comparison uses 0.01 tolerance after rounding
     */
    #[Test]
    public function confirmation_succeeds_when_price_change_within_tolerance(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.004,  // Rounds to 10.00
            'is_active' => true
        ]);

        // Step 1: Create preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 10]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Change price slightly (still rounds to 10.00)
        $product->update(['base_price' => 10.006]);  // Rounds to 10.01

        // Step 3: Confirm - should succeed because difference is exactly 0.01
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: Confirmation succeeds
        $confirmResponse->assertStatus(201);

        // Assert: Order persisted
        $this->assertDatabaseCount('orders', 1);
    }
}
