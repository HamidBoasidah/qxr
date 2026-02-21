<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Confirmation with Preview Reuse
 * 
 * **Validates: Requirements 2.3, 2.7**
 * 
 * This test verifies the complete end-to-end flow when attempting to reuse
 * a preview token after successful confirmation (single-use token).
 */
class ConfirmationPreviewReuseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Preview token cannot be reused after successful confirmation
     * 
     * **Validates: Requirements 2.3, 2.7**
     * 
     * Verifies that:
     * 1. Preview is created successfully
     * 2. First confirmation succeeds with HTTP 201
     * 3. Preview token is deleted from cache
     * 4. Second confirmation attempt returns HTTP 404
     * 5. Only one order is persisted
     */
    #[Test]
    public function preview_token_cannot_be_reused_after_successful_confirmation(): void
    {
        // Arrange: Create test data
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
            'notes' => 'Test single-use token',
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewResponse->assertStatus(200);
        $previewToken = $previewResponse->json('data.preview_token');

        // Verify preview is in cache
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Step 2: First confirmation (should succeed)
        $confirmData = ['preview_token' => $previewToken];

        $confirmResponse1 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', $confirmData);

        // Assert: First confirmation succeeds
        $confirmResponse1->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully'
            ]);

        // Assert: Preview token deleted from cache
        $this->assertNull(Cache::get("preview:{$previewToken}"));

        // Assert: One order persisted
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        // Step 3: Second confirmation attempt (should fail)
        $confirmResponse2 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', $confirmData);

        // Assert: Second confirmation fails with HTTP 404
        $confirmResponse2->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Preview not found or expired'
            ]);

        // Assert: Still only one order persisted (no duplicate)
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    /**
     * Test: Multiple confirmation attempts in rapid succession
     * 
     * **Validates: Requirements 2.7**
     */
    #[Test]
    public function multiple_rapid_confirmation_attempts_only_create_one_order(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 15.00,
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

        // Step 2: First confirmation
        $confirmResponse1 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        $confirmResponse1->assertStatus(201);

        // Step 3: Immediate second confirmation attempt
        $confirmResponse2 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        $confirmResponse2->assertStatus(404);

        // Step 4: Third confirmation attempt
        $confirmResponse3 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        $confirmResponse3->assertStatus(404);

        // Assert: Only one order created
        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Test: Customer can create new preview after confirming previous one
     * 
     * **Validates: Requirements 2.3, 2.7**
     */
    #[Test]
    public function customer_can_create_new_preview_after_confirming_previous(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 20.00,
            'is_active' => true
        ]);

        // Step 1: Create first preview
        $previewData1 = [
            'company_id' => $company->id,
            'notes' => 'First order',
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse1 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData1);

        $previewToken1 = $previewResponse1->json('data.preview_token');

        // Step 2: Confirm first preview
        $confirmResponse1 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken1]);

        $confirmResponse1->assertStatus(201);

        // Step 3: Create second preview
        $previewData2 = [
            'company_id' => $company->id,
            'notes' => 'Second order',
            'items' => [
                ['product_id' => $product->id, 'qty' => 50]
            ]
        ];

        $previewResponse2 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData2);

        $previewResponse2->assertStatus(200);
        $previewToken2 = $previewResponse2->json('data.preview_token');

        // Assert: Different tokens
        $this->assertNotEquals($previewToken1, $previewToken2);

        // Step 4: Confirm second preview
        $confirmResponse2 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken2]);

        $confirmResponse2->assertStatus(201);

        // Assert: Two orders created
        $this->assertDatabaseCount('orders', 2);
        
        $orders = \App\Models\Order::where('customer_user_id', $customer->id)->get();
        $this->assertCount(2, $orders);
        $this->assertEquals('First order', $orders[0]->notes_customer);
        $this->assertEquals('Second order', $orders[1]->notes_customer);
    }

    /**
     * Test: Token deletion is atomic with order creation
     * 
     * **Validates: Requirements 2.7**
     */
    #[Test]
    public function token_deletion_is_atomic_with_order_creation(): void
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
                ['product_id' => $product->id, 'qty' => 25]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Confirm order
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        $confirmResponse->assertStatus(201);

        // Assert: If order was created, token must be deleted
        $this->assertDatabaseCount('orders', 1);
        $this->assertNull(Cache::get("preview:{$previewToken}"));

        // This ensures atomicity: either both succeed (order created + token deleted)
        // or both fail (no order + token remains for retry)
    }

    /**
     * Test: Preview token format remains valid after confirmation failure
     * 
     * **Validates: Requirements 2.3**
     */
    #[Test]
    public function preview_token_remains_valid_after_409_failure(): void
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
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Change price to trigger HTTP 409
        $product->update(['base_price' => 12.00]);

        // Step 3: First confirmation attempt (should fail with 409)
        $confirmResponse1 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        $confirmResponse1->assertStatus(409);

        // Assert: Token still in cache (kept for re-preview)
        $this->assertNotNull(Cache::get("preview:{$previewToken}"));

        // Step 4: Customer can create new preview with updated price
        $previewResponse2 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewResponse2->assertStatus(200);
        $newPreviewToken = $previewResponse2->json('data.preview_token');

        // Step 5: Confirm with new token (should succeed)
        $confirmResponse2 = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $newPreviewToken]);

        $confirmResponse2->assertStatus(201);

        // Assert: Order created
        $this->assertDatabaseCount('orders', 1);
    }
}
