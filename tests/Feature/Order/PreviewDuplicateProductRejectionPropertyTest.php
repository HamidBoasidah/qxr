<?php

namespace Tests\Feature\Order;


use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Duplicate Product Rejection for Preview Endpoint
 * 
 * **Validates: Requirements 4.8**
 * 
 * Property 7: For any preview request where the items array contains 
 * duplicate product_id values, the system should reject the request with HTTP 422.
 */
class PreviewDuplicateProductRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Preview requests with duplicate product IDs should be rejected
     * 
     * **Validates: Requirements 4.8**
     * 
     * This test generates multiple random preview requests with duplicate products
     * and verifies that all are rejected with HTTP 422.
     */
    #[Test]
    public function preview_with_duplicate_products_is_rejected(): void
    {
        // Feature: order-creation-api, Property 7: Duplicate product rejection
        
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
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            // Create items array with duplicate product_id
            $numDuplicates = fake()->numberBetween(2, 5);
            $items = [];
            for ($j = 0; $j < $numDuplicates; $j++) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100)
                ];
            }
            
            // Prepare preview request with duplicate products
            $previewData = [
                'company_id' => $company->id,
                'items' => $items
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 422
            $this->assertEquals(
                422,
                $response->status(),
                "Preview with duplicate product_id={$product->id} should be rejected with HTTP 422"
            );
            
            // Verify validation error is present
            $errors = $response->json('errors');
            $this->assertNotNull(
                $errors,
                "Response should contain validation errors"
            );
        }
    }

    /**
     * Property Test: Preview with multiple products where some are duplicated
     * 
     * **Validates: Requirements 4.8**
     * 
     * Verifies that if any products are duplicated in the items array,
     * the entire request is rejected.
     */
    #[Test]
    public function preview_with_some_duplicate_products_among_multiple_items_is_rejected(): void
    {
        // Feature: order-creation-api, Property 7: Duplicate product rejection
        
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
            
            // Create multiple products
            $numUniqueProducts = fake()->numberBetween(2, 4);
            $products = Product::factory()->count($numUniqueProducts)->create([
                'company_id' => $company->id,
                'is_active' => true
            ]);

            // Pick a random product to duplicate
            $duplicateProduct = $products->random();
            
            $items = [];
            // Add all products once
            foreach ($products as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100)
                ];
            }
            
            // Add the duplicate product again
            $items[] = [
                'product_id' => $duplicateProduct->id,
                'qty' => fake()->numberBetween(1, 100)
            ];
            
            // Shuffle to randomize position
            shuffle($items);

            $previewData = [
                'company_id' => $company->id,
                'items' => $items
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be rejected with HTTP 422
            $this->assertEquals(
                422,
                $response->status(),
                "Preview with duplicate product_id={$duplicateProduct->id} should be rejected with HTTP 422"
            );
        }
    }

    /**
     * Property Test: Preview with all unique products should pass duplicate validation
     * 
     * **Validates: Requirements 4.8**
     * 
     * Verifies that preview requests with all unique products pass duplicate validation
     * (may still fail for other reasons, but not duplicate validation).
     */
    #[Test]
    public function preview_with_unique_products_passes_duplicate_validation(): void
    {
        // Feature: order-creation-api, Property 7: Duplicate product rejection
        
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
            
            // Create multiple unique products
            $numProducts = fake()->numberBetween(1, 10);
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

            // Assert: Should NOT fail with duplicate validation error
            if ($response->status() === 422) {
                $errors = $response->json('errors');
                $errorMessage = $response->json('errors.items.0') ?? '';
                $this->assertFalse(
                    str_contains($errorMessage, 'Duplicate') || str_contains($errorMessage, 'duplicate'),
                    "Unique products should not trigger duplicate validation error"
                );
            } else {
                // If not 422, it should be 200 (success for preview)
                $this->assertEquals(
                    200,
                    $response->status(),
                    "Preview with unique products should succeed"
                );
            }
        }
    }
}
