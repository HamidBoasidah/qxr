<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Non-Customer Role Rejection
 * 
 * **Validates: Requirements 2.2**
 * 
 * Property 39: For any authenticated user with role != 'customer', 
 * the system should reject order creation requests with HTTP 403.
 */
class NonCustomerRoleRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Company users should not be able to create orders
     * 
     * **Validates: Requirements 2.2**
     * 
     * This test generates multiple random order requests from company users
     * and verifies that all are rejected with HTTP 403.
     */
    #[Test]
    public function company_user_cannot_create_orders(): void
    {
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create company user (not customer)
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
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

            // Act: Try to create order as company user
            $response = $this->actingAs($company, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected with HTTP 403
            $this->assertEquals(
                403,
                $response->status(),
                "Company user should not be able to create orders (HTTP 403 expected)"
            );
        }
    }

    /**
     * Property Test: Unauthenticated requests should be rejected
     * 
     * **Validates: Requirements 2.1**
     * 
     * Verifies that unauthenticated requests are rejected with HTTP 401.
     */
    #[Test]
    public function unauthenticated_user_cannot_create_orders(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
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

            // Act: Try to create order without authentication
            $response = $this->postJson('/api/orders', $orderData);

            // Assert: Request should be rejected with HTTP 401
            $this->assertEquals(
                401,
                $response->status(),
                "Unauthenticated user should not be able to create orders (HTTP 401 expected)"
            );
        }
    }

    /**
     * Property Test: Customer users should be able to create orders
     * 
     * **Validates: Requirements 2.2**
     * 
     * Verifies that customer users pass the role check
     * (may still fail for other reasons, but not role validation).
     */
    #[Test]
    public function customer_user_passes_role_validation(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer user
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

            // Act: Create order as customer user
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Should NOT be rejected with HTTP 403 (role check should pass)
            $this->assertNotEquals(
                403,
                $response->status(),
                "Customer user should pass role validation (not HTTP 403)"
            );
            
            // Should also not be 401
            $this->assertNotEquals(
                401,
                $response->status(),
                "Customer user should be authenticated (not HTTP 401)"
            );
            
            // Should be either 201 (success) or 422 (other validation error)
            $this->assertContains(
                $response->status(),
                [201, 422],
                "Customer user should get 201 or 422, not 401/403"
            );
        }
    }

    /**
     * Property Test: Inactive customer users should still pass role check
     * 
     * **Validates: Requirements 2.2**
     * 
     * Verifies that the role check only validates user_type, not is_active status.
     * (Inactive users may be rejected for other reasons, but not role validation)
     */
    #[Test]
    public function inactive_customer_passes_role_validation(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create inactive customer user
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => false, // Inactive
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => fake()->randomFloat(2, 1, 100),
                'is_active' => true
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

            // Act: Create order as inactive customer user
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Should NOT be rejected with HTTP 403 for role
            // The role check only validates user_type='customer', not is_active
            $this->assertNotEquals(
                403,
                $response->status(),
                "Inactive customer should not fail role validation (user_type is still 'customer')"
            );
        }
    }
}
