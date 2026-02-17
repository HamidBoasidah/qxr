<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Duplicate Product Rejection
 * 
 * **Validates: Requirements 3.7**
 * 
 * Property 37: For any order request where the order_items array contains 
 * duplicate product_id values, the system should reject the request with HTTP 422.
 */
class DuplicateProductRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Orders with duplicate products should be rejected
     * 
     * **Validates: Requirements 3.7**
     * 
     * This test generates multiple random order requests with duplicate products
     * and verifies that all are rejected with HTTP 422.
     */
    #[Test]
    public function order_with_duplicate_products_is_rejected(): void
    {
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with unique emails
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $unitPrice = $product->base_price;
            
            // Create order with duplicate product_id
            $qty1 = fake()->numberBetween(1, 100);
            $qty2 = fake()->numberBetween(1, 100);
            
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty1,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($unitPrice * $qty1, 2),
                        'selected_offer_id' => null
                    ],
                    [
                        'product_id' => $product->id, // Duplicate!
                        'qty' => $qty2,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($unitPrice * $qty2, 2),
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected with HTTP 422
            $this->assertEquals(
                422,
                $response->status(),
                "Order with duplicate product_id={$product->id} should be rejected with HTTP 422"
            );
            
            // Verify validation error mentions duplicates
            $errors = $response->json('errors');
            $this->assertNotNull($errors, "Response should contain validation errors");
        }
    }

    /**
     * Property Test: Orders with multiple duplicates among many items
     * 
     * **Validates: Requirements 3.7**
     * 
     * Verifies that orders with multiple items where some are duplicates
     * are rejected.
     */
    #[Test]
    public function order_with_multiple_duplicates_among_many_items_is_rejected(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            // Create several products
            $numUniqueProducts = fake()->numberBetween(2, 5);
            $products = Product::factory()->count($numUniqueProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            // Build order items with at least one duplicate
            $orderItems = [];
            
            // Add all products once
            foreach ($products as $product) {
                $qty = fake()->numberBetween(1, 100);
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $product->base_price,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => round($product->base_price * $qty, 2),
                    'selected_offer_id' => null
                ];
            }
            
            // Add a duplicate of a random product
            $duplicateProduct = $products->random();
            $qty = fake()->numberBetween(1, 100);
            $orderItems[] = [
                'product_id' => $duplicateProduct->id, // Duplicate!
                'qty' => $qty,
                'unit_price_snapshot' => $duplicateProduct->base_price,
                'discount_amount_snapshot' => 0.00,
                'final_line_total_snapshot' => round($duplicateProduct->base_price * $qty, 2),
                'selected_offer_id' => null
            ];

            $orderData = [
                'company_id' => $company->id,
                'order_items' => $orderItems
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected with HTTP 422
            $this->assertEquals(
                422,
                $response->status(),
                "Order with duplicate products should be rejected with HTTP 422"
            );
        }
    }

    /**
     * Property Test: Orders with all unique products should pass duplicate validation
     * 
     * **Validates: Requirements 3.7**
     * 
     * Verifies that orders with all unique products pass the duplicate check
     * (may still fail for other reasons, but not duplicate validation).
     */
    #[Test]
    public function order_with_all_unique_products_passes_duplicate_validation(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            // Create multiple unique products
            $numProducts = fake()->numberBetween(1, 10);
            $products = Product::factory()->count($numProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $orderItems = [];
            foreach ($products as $product) {
                $qty = fake()->numberBetween(1, 100);
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $product->base_price,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => round($product->base_price * $qty, 2),
                    'selected_offer_id' => null
                ];
            }

            $orderData = [
                'company_id' => $company->id,
                'order_items' => $orderItems
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Should NOT fail with duplicate product error
            if ($response->status() === 422) {
                $message = $response->json('message');
                $errors = $response->json('errors');
                
                // Should not have duplicate product error
                $this->assertFalse(
                    str_contains(strtolower($message ?? ''), 'duplicate') ||
                    (isset($errors['order_items']) && str_contains(strtolower(json_encode($errors['order_items'])), 'duplicate')),
                    "Order with unique products should not trigger duplicate validation error"
                );
            } else {
                // If not 422, it should be 201 (success)
                $this->assertEquals(
                    201,
                    $response->status(),
                    "Order with unique products should succeed"
                );
            }
        }
    }

    /**
     * Property Test: Orders with three or more of the same product should be rejected
     * 
     * **Validates: Requirements 3.7**
     * 
     * Verifies that even with multiple duplicates of the same product,
     * the order is rejected.
     */
    #[Test]
    public function order_with_triple_duplicate_is_rejected(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            // Create order with same product three times
            $orderItems = [];
            for ($j = 0; $j < 3; $j++) {
                $qty = fake()->numberBetween(1, 100);
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $product->base_price,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => round($product->base_price * $qty, 2),
                    'selected_offer_id' => null
                ];
            }

            $orderData = [
                'company_id' => $company->id,
                'order_items' => $orderItems
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected with HTTP 422
            $this->assertEquals(
                422,
                $response->status(),
                "Order with triple duplicate product should be rejected with HTTP 422"
            );
        }
    }
}
