<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration Test: Order Confirmation with Wrong Customer
 * 
 * **Validates: Requirements 2.4, 3.5, 9.19**
 * 
 * This test verifies the complete end-to-end flow when a different customer
 * attempts to confirm another customer's preview, resulting in HTTP 403 response.
 */
class ConfirmationWrongCustomerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Confirmation fails when different customer attempts to confirm
     * 
     * **Validates: Requirements 2.4, 3.5, 9.19**
     * 
     * Verifies that:
     * 1. Preview is created by customer A
     * 2. Customer B attempts to confirm the preview
     * 3. Confirmation returns HTTP 403
     * 4. Response indicates unauthorized access
     * 5. Preview token is deleted from cache (security measure)
     * 6. No order is persisted
     */
    #[Test]
    public function confirmation_fails_when_wrong_customer_attempts_to_confirm(): void
    {
        // Arrange: Create test data
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customerA = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $customerB = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Customer A creates preview
        $previewData = [
            'company_id' => $company->id,
            'notes' => 'Customer A preview',
            'items' => [
                ['product_id' => $product->id, 'qty' => 100]
            ]
        ];

        $previewResponse = $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewResponse->assertStatus(200);
        $previewToken = $previewResponse->json('data.preview_token');

        // Verify preview is in cache and belongs to customer A
        $cachedPreview = Cache::get("preview:{$previewToken}");
        $this->assertNotNull($cachedPreview);
        $this->assertEquals($customerA->id, $cachedPreview['customer_user_id']);

        // Step 2: Customer B attempts to confirm customer A's preview
        $confirmData = ['preview_token' => $previewToken];

        $confirmResponse = $this->actingAs($customerB, 'sanctum')
            ->postJson('/api/orders/confirm', $confirmData);

        // Assert: HTTP 403 response
        $confirmResponse->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This preview belongs to another customer'
            ]);

        // Assert: Preview token is deleted from cache (security measure)
        $this->assertNull(Cache::get("preview:{$previewToken}"));

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_item_bonuses', 0);
        $this->assertDatabaseCount('order_status_logs', 0);
    }

    /**
     * Test: Customer A can successfully confirm their own preview
     * 
     * **Validates: Requirements 2.4, 3.5**
     */
    #[Test]
    public function confirmation_succeeds_when_correct_customer_confirms(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Customer creates preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 50]
            ]
        ];

        $previewResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Same customer confirms their own preview
        $confirmResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: Confirmation succeeds
        $confirmResponse->assertStatus(201);

        // Assert: Order persisted
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'customer_user_id' => $customer->id
        ]);
    }

    /**
     * Test: Token deletion prevents retry after ownership failure
     * 
     * **Validates: Requirements 9.19**
     */
    #[Test]
    public function token_deleted_prevents_retry_after_ownership_failure(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customerA = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $customerB = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Customer A creates preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 75]
            ]
        ];

        $previewResponse = $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Customer B attempts to confirm (first attempt)
        $confirmResponse1 = $this->actingAs($customerB, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        $confirmResponse1->assertStatus(403);

        // Assert: Token deleted after first failed attempt
        $this->assertNull(Cache::get("preview:{$previewToken}"));

        // Step 3: Customer B attempts to confirm again (second attempt)
        $confirmResponse2 = $this->actingAs($customerB, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: Second attempt returns 404 (token not found)
        $confirmResponse2->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Preview not found or expired'
            ]);

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Original customer cannot confirm after ownership failure by another
     * 
     * **Validates: Requirements 9.19**
     */
    #[Test]
    public function original_customer_cannot_confirm_after_ownership_failure(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customerA = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $customerB = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Customer A creates preview
        $previewData = [
            'company_id' => $company->id,
            'items' => [
                ['product_id' => $product->id, 'qty' => 30]
            ]
        ];

        $previewResponse = $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/orders/preview', $previewData);

        $previewToken = $previewResponse->json('data.preview_token');

        // Step 2: Customer B attempts to confirm (triggers token deletion)
        $this->actingAs($customerB, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Step 3: Customer A (original owner) attempts to confirm
        $confirmResponse = $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewToken]);

        // Assert: Even original customer cannot confirm (token deleted)
        $confirmResponse->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Preview not found or expired'
            ]);

        // Assert: No order persisted
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * Test: Multiple customers with their own previews
     * 
     * **Validates: Requirements 2.4, 3.5**
     */
    #[Test]
    public function multiple_customers_can_have_separate_previews(): void
    {
        // Arrange
        $company = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customerA = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $customerB = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        
        $product = Product::factory()->create([
            'company_user_id' => $company->id,
            'base_price' => 10.00,
            'is_active' => true
        ]);

        // Step 1: Customer A creates preview
        $previewDataA = [
            'company_id' => $company->id,
            'items' => [['product_id' => $product->id, 'qty' => 100]]
        ];

        $previewResponseA = $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/orders/preview', $previewDataA);

        $previewTokenA = $previewResponseA->json('data.preview_token');

        // Step 2: Customer B creates their own preview
        $previewDataB = [
            'company_id' => $company->id,
            'items' => [['product_id' => $product->id, 'qty' => 50]]
        ];

        $previewResponseB = $this->actingAs($customerB, 'sanctum')
            ->postJson('/api/orders/preview', $previewDataB);

        $previewTokenB = $previewResponseB->json('data.preview_token');

        // Assert: Different tokens
        $this->assertNotEquals($previewTokenA, $previewTokenB);

        // Step 3: Each customer confirms their own preview
        $confirmResponseA = $this->actingAs($customerA, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewTokenA]);

        $confirmResponseB = $this->actingAs($customerB, 'sanctum')
            ->postJson('/api/orders/confirm', ['preview_token' => $previewTokenB]);

        // Assert: Both confirmations succeed
        $confirmResponseA->assertStatus(201);
        $confirmResponseB->assertStatus(201);

        // Assert: Two orders persisted
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseHas('orders', ['customer_user_id' => $customerA->id]);
        $this->assertDatabaseHas('orders', ['customer_user_id' => $customerB->id]);
    }
}
