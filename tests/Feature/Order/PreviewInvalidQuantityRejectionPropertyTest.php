<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Invalid Quantity Rejection for Preview Endpoint
 * 
 * **Validates: Requirements 4.7**
 * 
 * Property 6: For any preview request where any qty value is ≤ 0, 
 * the system should reject the request with HTTP 422.
 */
class PreviewInvalidQuantityRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Preview requests with zero or negative quantities should be rejected
     * 
     * **Validates: Requirements 4.7**
     * 
     * This test generates multiple random preview requests with invalid quantities
     * and verifies that all are rejected with HTTP 422.
     */
    #[Test]
    public function preview_with_invalid_quantity_is_rejected(): void
    {
        // Feature: order-creation-api, Property 6: Invalid quantity rejection
        
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with unique emails
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

            // Generate invalid quantity (zero or negative)
            $invalidQty = fake()->randomElement([
                0,
                -1,
                fake()->numberBetween(-1000, -1),
                fake()->numberBetween(-10, 0)
            ]);
            
            // Prepare preview request with invalid quantity
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $invalidQty
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
                "Preview with qty={$invalidQty} should be rejected with HTTP 422"
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
     * Property Test: Preview with multiple items where at least one has invalid quantity
     * 
     * **Validates: Requirements 4.7**
     * 
     * Verifies that if any item in the preview has an invalid quantity,
     * the entire request is rejected.
     */
    #[Test]
    public function preview_with_one_invalid_quantity_among_multiple_items_is_rejected(): void
    {
        // Feature: order-creation-api, Property 6: Invalid quantity rejection
        
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
            $numProducts = fake()->numberBetween(2, 5);
            $products = Product::factory()->count($numProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            // Pick a random product to have invalid quantity
            $invalidIndex = fake()->numberBetween(0, $numProducts - 1);
            
            $items = [];
            foreach ($products as $index => $product) {
                $qty = ($index === $invalidIndex) 
                    ? fake()->randomElement([0, -1, fake()->numberBetween(-100, -1)])
                    : fake()->numberBetween(1, 100);
                
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => $qty
                ];
            }

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
                "Preview with at least one invalid qty should be rejected with HTTP 422"
            );
        }
    }

    /**
     * Property Test: Valid positive quantities should be accepted
     * 
     * **Validates: Requirements 4.7**
     * 
     * Verifies that preview requests with valid positive quantities pass validation
     * (may still fail for other reasons, but not quantity validation).
     */
    #[Test]
    public function preview_with_valid_positive_quantities_passes_quantity_validation(): void
    {
        // Feature: order-creation-api, Property 6: Invalid quantity rejection
        
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
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            // Generate valid positive quantity
            $validQty = fake()->numberBetween(1, 10000);
            
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $validQty
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Should NOT fail with quantity validation error
            if ($response->status() === 422) {
                $errors = $response->json('errors');
                $this->assertFalse(
                    isset($errors['items.0.qty']),
                    "Valid qty={$validQty} should not trigger quantity validation error"
                );
            } else {
                // If not 422, it should be 200 (success for preview)
                $this->assertEquals(
                    200,
                    $response->status(),
                    "Preview with valid qty={$validQty} should succeed"
                );
            }
        }
    }
}
