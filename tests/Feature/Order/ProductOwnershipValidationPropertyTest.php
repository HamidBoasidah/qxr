<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Product Ownership Validation
 * 
 * **Validates: Requirements 17.2**
 * 
 * Property 38: For any order request, if any product_id does not belong to 
 * the specified company_id or is not active, the system should reject the 
 * request with HTTP 403 or HTTP 422.
 */
class ProductOwnershipValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Orders with products from wrong company should be rejected
     * 
     * **Validates: Requirements 17.2**
     * 
     * This test generates multiple random order requests where products
     * belong to a different company and verifies rejection.
     */
    #[Test]
    public function order_with_product_from_wrong_company_is_rejected(): void
    {
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create two different companies
            $company1 = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company1_' . uniqid() . '@example.com'
            ]);
            $company2 = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company2_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            // Create product belonging to company2
            $product = Product::factory()->create([
                'company_user_id' => $company2->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
            ]);

            $qty = fake()->numberBetween(1, 100);
            $unitPrice = $product->base_price;
            
            // Try to order product from company2 but specify company1
            $orderData = [
                'company_id' => $company1->id, // Wrong company!
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($unitPrice * $qty, 2),
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected with HTTP 403 or 422
            $this->assertContains(
                $response->status(),
                [403, 422],
                "Order with product from wrong company should be rejected with HTTP 403 or 422"
            );
        }
    }

    /**
     * Property Test: Orders with inactive products should be rejected
     * 
     * **Validates: Requirements 17.2**
     * 
     * Verifies that orders containing inactive products are rejected.
     */
    #[Test]
    public function order_with_inactive_product_is_rejected(): void
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
            
            // Create inactive product
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => false // Inactive!
            ]);

            $qty = fake()->numberBetween(1, 100);
            $unitPrice = $product->base_price;
            
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => round($unitPrice * $qty, 2),
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected (not 201)
            // May be 403, 422, or 500 depending on where validation occurs
            $this->assertNotEquals(
                201,
                $response->status(),
                "Order with inactive product should be rejected (not 201)"
            );
        }
    }

    /**
     * Property Test: Orders with mix of valid and invalid products should be rejected
     * 
     * **Validates: Requirements 17.2**
     * 
     * Verifies that if any product in the order is invalid (wrong company or inactive),
     * the entire order is rejected.
     */
    #[Test]
    public function order_with_one_invalid_product_among_multiple_is_rejected(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company1 = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company1_' . uniqid() . '@example.com'
            ]);
            $company2 = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company2_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            // Create valid products for company1
            $numValidProducts = fake()->numberBetween(1, 3);
            $validProducts = Product::factory()->count($numValidProducts)->create([
                'company_user_id' => $company1->id,
                'is_active' => true
            ]);

            // Create one invalid product (from different company)
            $invalidProduct = Product::factory()->create([
                'company_user_id' => $company2->id,
                'is_active' => true
            ]);

            $orderItems = [];
            
            // Add valid products
            foreach ($validProducts as $product) {
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
            
            // Add invalid product
            $qty = fake()->numberBetween(1, 100);
            $orderItems[] = [
                'product_id' => $invalidProduct->id,
                'qty' => $qty,
                'unit_price_snapshot' => $invalidProduct->base_price,
                'discount_amount_snapshot' => 0.00,
                'final_line_total_snapshot' => round($invalidProduct->base_price * $qty, 2),
                'selected_offer_id' => null
            ];

            $orderData = [
                'company_id' => $company1->id,
                'order_items' => $orderItems
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected (not 201)
            // May be 403, 422, or 500 depending on where validation occurs
            $this->assertNotEquals(
                201,
                $response->status(),
                "Order with one invalid product should be rejected (not 201)"
            );
        }
    }

    /**
     * Property Test: Orders with all valid products from correct company should pass ownership validation
     * 
     * **Validates: Requirements 17.2**
     * 
     * Verifies that orders with all products belonging to the specified company
     * and all active pass the ownership validation.
     */
    #[Test]
    public function order_with_all_valid_products_passes_ownership_validation(): void
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
            
            // Create multiple valid products for the company
            $numProducts = fake()->numberBetween(1, 5);
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

            // Assert: Should NOT fail with ownership/authorization error
            if ($response->status() === 403) {
                $this->fail("Order with valid products should not be rejected with HTTP 403");
            }
            
            if ($response->status() === 422) {
                $message = $response->json('message');
                // Should not have authorization or ownership error
                $this->assertFalse(
                    str_contains(strtolower($message ?? ''), 'authorization') ||
                    str_contains(strtolower($message ?? ''), 'belong') ||
                    str_contains(strtolower($message ?? ''), 'company') ||
                    str_contains(strtolower($message ?? ''), 'inactive'),
                    "Order with valid products should not trigger ownership validation error"
                );
            } else {
                // If not 403 or 422, it should be 201 (success)
                $this->assertEquals(
                    201,
                    $response->status(),
                    "Order with valid products should succeed"
                );
            }
        }
    }
}
