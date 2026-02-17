<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Invalid Quantity Rejection
 * 
 * **Validates: Requirements 3.6**
 * 
 * Property 36: For any order request where any qty value is ≤ 0, 
 * the system should reject the request with HTTP 422.
 */
class InvalidQuantityRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Orders with zero or negative quantities should be rejected
     * 
     * **Validates: Requirements 3.6**
     * 
     * This test generates multiple random order requests with invalid quantities
     * and verifies that all are rejected with HTTP 422.
     */
    #[Test]
    public function order_with_invalid_quantity_is_rejected(): void
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

            // Generate invalid quantity (zero or negative)
            $invalidQty = fake()->randomElement([
                0,
                -1,
                fake()->numberBetween(-1000, -1),
                fake()->numberBetween(-10, 0)
            ]);

            $unitPrice = $product->base_price;
            
            // Prepare order request with invalid quantity
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $invalidQty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => $unitPrice * max(0, $invalidQty),
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
                "Order with qty={$invalidQty} should be rejected with HTTP 422"
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
     * Property Test: Orders with multiple items where at least one has invalid quantity
     * 
     * **Validates: Requirements 3.6**
     * 
     * Verifies that if any item in the order has an invalid quantity,
     * the entire order is rejected.
     */
    #[Test]
    public function order_with_one_invalid_quantity_among_multiple_items_is_rejected(): void
    {
        // Run 100 iterations
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
            
            // Create multiple products
            $numProducts = fake()->numberBetween(2, 5);
            $products = Product::factory()->count($numProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            // Pick a random product to have invalid quantity
            $invalidIndex = fake()->numberBetween(0, $numProducts - 1);
            
            $orderItems = [];
            foreach ($products as $index => $product) {
                $qty = ($index === $invalidIndex) 
                    ? fake()->randomElement([0, -1, fake()->numberBetween(-100, -1)])
                    : fake()->numberBetween(1, 100);
                
                $unitPrice = $product->base_price;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unitPrice,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => $unitPrice * max(0, $qty),
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
                "Order with at least one invalid qty should be rejected with HTTP 422"
            );
        }
    }

    /**
     * Property Test: Valid positive quantities should be accepted
     * 
     * **Validates: Requirements 3.6**
     * 
     * Verifies that orders with valid positive quantities pass validation
     * (may still fail for other reasons, but not quantity validation).
     */
    #[Test]
    public function order_with_valid_positive_quantities_passes_quantity_validation(): void
    {
        // Run 100 iterations
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

            // Generate valid positive quantity
            $validQty = fake()->numberBetween(1, 10000);
            $unitPrice = $product->base_price;
            
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $validQty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($unitPrice * $validQty, 2),
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Should NOT fail with quantity validation error
            // (may fail for other reasons, but status should be 201 or not 422 with qty error)
            if ($response->status() === 422) {
                $errors = $response->json('errors');
                $this->assertFalse(
                    isset($errors['order_items.0.qty']),
                    "Valid qty={$validQty} should not trigger quantity validation error"
                );
            } else {
                // If not 422, it should be 201 (success)
                $this->assertEquals(
                    201,
                    $response->status(),
                    "Order with valid qty={$validQty} should succeed"
                );
            }
        }
    }
}
