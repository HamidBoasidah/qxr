<?php

namespace Tests\Feature\Order;

use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Preview Storage and Retrieval
 * 
 * **Validates: Requirements 8.1, 8.2, 8.5, 1.4, 3.3, 2.4, 3.5, 9.2**
 * 
 * Property 30-36: Preview storage, cache, uniqueness, database isolation, 
 * customer association, ownership
 */
class PreviewStorageAndRetrievalPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Preview data is stored in cache with preview_token as key
     * 
     * **Validates: Requirements 8.1**
     * 
     * Property 30: For any successful preview, the system should store preview 
     * data in cache with preview_token as key.
     */
    #[Test]
    public function preview_data_is_stored_in_cache(): void
    {
        // Feature: order-creation-api, Property 30: Preview cache storage
        
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 1000)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $previewToken = $response->json('data.preview_token');
            $this->assertNotNull($previewToken);
            
            // Verify data is stored in cache with correct key format
            $cacheKey = "preview:{$previewToken}";
            $cachedData = Cache::get($cacheKey);
            
            $this->assertNotNull(
                $cachedData,
                "Preview data should be stored in cache with key {$cacheKey}"
            );
        }
    }

    /**
     * Property Test: Preview tokens are unique
     * 
     * **Validates: Requirements 8.5**
     * 
     * Property 32: For any two distinct previews, their preview_token values 
     * should be different.
     */
    #[Test]
    public function preview_tokens_are_unique(): void
    {
        // Feature: order-creation-api, Property 32: Preview token uniqueness
        
        $tokens = [];
        
        // Generate 100 previews and collect tokens
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 1000)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            $this->assertEquals(200, $response->status());
            
            $previewToken = $response->json('data.preview_token');
            $tokens[] = $previewToken;
        }
        
        // Assert: All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(
            100,
            $uniqueTokens,
            "All 100 preview tokens should be unique"
        );
    }

    /**
     * Property Test: Preview does not persist data to database
     * 
     * **Validates: Requirements 1.4**
     * 
     * Property 33: For any preview request, the system should NOT persist any 
     * data to the database (no orders, order_items, bonuses, or status_logs created).
     */
    #[Test]
    public function preview_does_not_persist_to_database(): void
    {
        // Feature: order-creation-api, Property 33: Preview database isolation
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            // Count orders before preview
            $orderCountBefore = Order::count();

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 1000)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: No orders should be created
            $this->assertEquals(200, $response->status());
            
            $orderCountAfter = Order::count();
            $this->assertEquals(
                $orderCountBefore,
                $orderCountAfter,
                "Preview should not create any orders in database"
            );
        }
    }

    /**
     * Property Test: Preview is associated with authenticated customer
     * 
     * **Validates: Requirements 3.3**
     * 
     * Property 34: For any preview, the stored preview data should include 
     * the authenticated customer's user_id as customer_user_id.
     */
    #[Test]
    public function preview_is_associated_with_customer(): void
    {
        // Feature: order-creation-api, Property 34: Preview customer association
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 1000)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $previewToken = $response->json('data.preview_token');
            $cacheKey = "preview:{$previewToken}";
            $cachedData = Cache::get($cacheKey);
            
            $this->assertNotNull($cachedData);
            $this->assertEquals(
                $customer->id,
                $cachedData['customer_user_id'],
                "Preview should be associated with authenticated customer"
            );
        }
    }

    /**
     * Property Test: Preview ownership is verified on confirmation
     * 
     * **Validates: Requirements 2.4, 3.5, 9.2**
     * 
     * Property 36: For any confirmation request, if the preview_token's 
     * customer_user_id does not match the authenticated user's id, the system 
     * should reject with HTTP 403 and delete the token.
     */
    #[Test]
    public function preview_ownership_is_verified_on_confirmation(): void
    {
        // Feature: order-creation-api, Property 36: Preview ownership verification
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create two customers
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customerA = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            $customerB = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 1000)
                    ]
                ]
            ];

            // Act: Customer A creates preview
            $previewResponse = $this->actingAs($customerA, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            $this->assertEquals(200, $previewResponse->status());
            $previewToken = $previewResponse->json('data.preview_token');

            // Customer B tries to confirm Customer A's preview
            $confirmResponse = $this->actingAs($customerB, 'sanctum')
                ->postJson('/api/orders/confirm', [
                    'preview_token' => $previewToken
                ]);

            // Assert: Should be rejected with HTTP 403
            $this->assertEquals(
                403,
                $confirmResponse->status(),
                "Confirmation by different customer should be rejected with HTTP 403"
            );
            
            // Verify token is deleted
            $cacheKey = "preview:{$previewToken}";
            $cachedData = Cache::get($cacheKey);
            $this->assertNull(
                $cachedData,
                "Preview token should be deleted after ownership mismatch"
            );
        }
    }

    /**
     * Property Test: Preview data contains all required fields
     * 
     * **Validates: Requirements 8.2**
     * 
     * Property 31: For any stored preview, the cache should include 
     * customer_user_id, company_id, notes, calculated items with all pricing, 
     * calculated bonuses, totals, and creation timestamp.
     */
    #[Test]
    public function preview_data_contains_all_required_fields(): void
    {
        // Feature: order-creation-api, Property 31: Preview data completeness
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $notes = fake()->sentence();

            $previewData = [
                'company_id' => $company->id,
                'notes' => $notes,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 1000)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $previewToken = $response->json('data.preview_token');
            $cacheKey = "preview:{$previewToken}";
            $cachedData = Cache::get($cacheKey);
            
            $this->assertNotNull($cachedData);
            
            // Verify all required fields are present
            $this->assertArrayHasKey('preview_token', $cachedData);
            $this->assertArrayHasKey('customer_user_id', $cachedData);
            $this->assertArrayHasKey('company_id', $cachedData);
            $this->assertArrayHasKey('notes', $cachedData);
            $this->assertArrayHasKey('items', $cachedData);
            $this->assertArrayHasKey('subtotal', $cachedData);
            $this->assertArrayHasKey('total_discount', $cachedData);
            $this->assertArrayHasKey('final_total', $cachedData);
            $this->assertArrayHasKey('created_at', $cachedData);
            
            // Verify values match
            $this->assertEquals($customer->id, $cachedData['customer_user_id']);
            $this->assertEquals($company->id, $cachedData['company_id']);
            $this->assertEquals($notes, $cachedData['notes']);
        }
    }
}
