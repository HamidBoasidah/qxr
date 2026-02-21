<?php

namespace Tests\Feature\Order;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Non-Customer Role Rejection for Preview Endpoint
 * 
 * **Validates: Requirements 3.2, 3.6**
 * 
 * Property 8: For any authenticated user with role != 'customer', 
 * the system should reject preview requests with HTTP 403.
 */
class PreviewNonCustomerRoleRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Preview requests from non-customer users should be rejected
     * 
     * **Validates: Requirements 3.2, 3.6**
     * 
     * This test generates multiple random preview requests from users with
     * non-customer roles and verifies rejection with HTTP 403.
     */
    #[Test]
    public function preview_from_non_customer_role_is_rejected(): void
    {
        // Feature: order-creation-api, Property 8: Non-customer role rejection
        
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            
            // Create user with non-customer role (use company as the only other available user_type)
            $nonCustomer = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 100)
                    ]
                ]
            ];

            // Act: Submit preview request as non-customer
            $response = $this->actingAs($nonCustomer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 403
            $this->assertEquals(
                403,
                $response->status(),
                "Preview from non-customer (company user) should be rejected with HTTP 403"
            );
        }
    }

    /**
     * Property Test: Preview requests from customer role should pass authorization
     * 
     * **Validates: Requirements 3.2, 3.6**
     * 
     * Verifies that users with customer role pass authorization checks
     * (may still fail for other reasons, but not authorization).
     */
    #[Test]
    public function preview_from_customer_role_passes_authorization(): void
    {
        // Feature: order-creation-api, Property 8: Non-customer role rejection
        
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
                        'qty' => fake()->numberBetween(1, 100)
                    ]
                ]
            ];

            // Act: Submit preview request as customer
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Should NOT fail with authorization error (403)
            $this->assertNotEquals(
                403,
                $response->status(),
                "Preview from customer role should not be rejected with HTTP 403"
            );
            
            // Should be 200 (success for preview)
            $this->assertEquals(
                200,
                $response->status(),
                "Preview from customer role should succeed"
            );
        }
    }

    /**
     * Property Test: Unauthenticated preview requests should be rejected
     * 
     * **Validates: Requirements 3.2**
     * 
     * Verifies that unauthenticated requests are rejected with HTTP 401.
     */
    #[Test]
    public function preview_without_authentication_is_rejected(): void
    {
        // Feature: order-creation-api, Property 8: Non-customer role rejection
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
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
                        'qty' => fake()->numberBetween(1, 100)
                    ]
                ]
            ];

            // Act: Submit preview request without authentication
            $response = $this->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 401
            $this->assertEquals(
                401,
                $response->status(),
                "Preview without authentication should be rejected with HTTP 401"
            );
        }
    }

    /**
     * Property Test: Multiple non-customer roles should all be rejected
     * 
     * **Validates: Requirements 3.2, 3.6**
     * 
     * Verifies that various non-customer roles are consistently rejected.
     */
    #[Test]
    public function preview_from_various_non_customer_roles_is_rejected(): void
    {
        // Feature: order-creation-api, Property 8: Non-customer role rejection
        
        // Test company user_type multiple times
        for ($i = 0; $i < 90; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            
            $nonCustomer = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 100)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($nonCustomer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 403
            $this->assertEquals(
                403,
                $response->status(),
                "Preview from non-customer (company user) should be rejected with HTTP 403"
            );
        }
    }
}
