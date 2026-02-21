<?php

namespace Tests\Feature\Order;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Product Ownership Validation for Preview Endpoint
 * 
 * **Validates: Requirements 4.5, 4.6**
 * 
 * Property 4: For any preview request, if any product_id does not belong to 
 * the specified company, the system should reject the request with HTTP 403.
 * For any product_id that references an inactive product, the system should 
 * reject the request with HTTP 422.
 */
class PreviewProductOwnershipValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Preview with product from wrong company should be rejected
     * 
     * **Validates: Requirements 4.5**
     * 
     * This test generates multiple random preview requests with products that
     * don't belong to the specified company and verifies rejection with HTTP 403.
     */
    #[Test]
    public function preview_with_product_from_wrong_company_is_rejected(): void
    {
        // Feature: order-creation-api, Property 4: Product ownership validation
        
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create two different companies
            $companyA = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $companyB = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            // Create product belonging to companyB
            $product = Product::factory()->create([
                'company_user_id' => $companyB->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            // Try to order product from companyB using companyA's ID
            $previewData = [
                'company_id' => $companyA->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => fake()->numberBetween(1, 100)
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 403
            $this->assertEquals(
                403,
                $response->status(),
                "Preview with product from wrong company should be rejected with HTTP 403"
            );
        }
    }

    /**
     * Property Test: Preview with inactive product should be rejected
     * 
     * **Validates: Requirements 4.6**
     * 
     * This test generates multiple random preview requests with inactive products
     * and verifies rejection with HTTP 422.
     */
    #[Test]
    public function preview_with_inactive_product_is_rejected(): void
    {
        // Feature: order-creation-api, Property 4: Product ownership validation
        
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
            
            // Create inactive product
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => false
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
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 422
            $this->assertEquals(
                422,
                $response->status(),
                "Preview with inactive product should be rejected with HTTP 422"
            );
        }
    }

    /**
     * Property Test: Preview with multiple products where one is from wrong company
     * 
     * **Validates: Requirements 4.5**
     * 
     * Verifies that if any product doesn't belong to the specified company,
     * the entire request is rejected.
     */
    #[Test]
    public function preview_with_one_wrong_company_product_among_multiple_items_is_rejected(): void
    {
        // Feature: order-creation-api, Property 4: Product ownership validation
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create two companies
            $companyA = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $companyB = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            // Create products for companyA
            $numValidProducts = fake()->numberBetween(1, 3);
            $validProducts = Product::factory()->count($numValidProducts)->create([
                'company_id' => $companyA->id,
                'is_active' => true
            ]);
            
            // Create one product for companyB
            $wrongProduct = Product::factory()->create([
                'company_id' => $companyB->id,
                'is_active' => true
            ]);

            $items = [];
            // Add valid products
            foreach ($validProducts as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100)
                ];
            }
            
            // Add wrong company product
            $items[] = [
                'product_id' => $wrongProduct->id,
                'qty' => fake()->numberBetween(1, 100)
            ];
            
            // Shuffle to randomize position
            shuffle($items);

            $previewData = [
                'company_id' => $companyA->id,
                'items' => $items
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 403
            $this->assertEquals(
                403,
                $response->status(),
                "Preview with one product from wrong company should be rejected with HTTP 403"
            );
        }
    }

    /**
     * Property Test: Preview with all products from correct company and active
     * 
     * **Validates: Requirements 4.5, 4.6**
     * 
     * Verifies that preview requests with all products from the correct company
     * and all active pass ownership validation.
     */
    #[Test]
    public function preview_with_valid_products_passes_ownership_validation(): void
    {
        // Feature: order-creation-api, Property 4: Product ownership validation
        
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
            
            // Create multiple active products for the company
            $numProducts = fake()->numberBetween(1, 5);
            $products = Product::factory()->count($numProducts)->create([
                'company_id' => $company->id,
                'is_active' => true
            ]);

            $items = [];
            foreach ($products as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100)
                ];
            }
            
            $previewData = [
                'company_id' => $company->id,
                'items' => $items
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Should NOT fail with ownership validation error
            if ($response->status() === 403) {
                $this->fail("Valid products from correct company should not trigger HTTP 403");
            }
            
            if ($response->status() === 422) {
                $message = $response->json('message') ?? '';
                $this->assertFalse(
                    str_contains($message, 'inactive') || str_contains($message, 'not active'),
                    "Active products should not trigger inactive validation error"
                );
            }
            
            // Should be 200 (success for preview)
            $this->assertEquals(
                200,
                $response->status(),
                "Preview with valid products should succeed"
            );
        }
    }
}
