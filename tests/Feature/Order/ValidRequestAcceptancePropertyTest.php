<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Valid Request Acceptance
 * 
 * **Validates: Requirements 1.2, 1.3**
 * 
 * Property 40: For any structurally valid order request with correct calculations 
 * and no stale data, the system should accept the request and return HTTP 201.
 */
class ValidRequestAcceptancePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Valid orders with single item should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * This test generates multiple random valid order requests with a single item
     * and verifies that all are accepted with HTTP 201.
     */
    #[Test]
    public function valid_order_with_single_item_is_accepted(): void
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

            // Generate valid order data
            $qty = fake()->numberBetween(1, 1000);
            $unitPrice = round($product->base_price, 2);
            $finalTotal = round($unitPrice * $qty, 2);
            
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => $finalTotal,
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be accepted with HTTP 201
            $this->assertEquals(
                201,
                $response->status(),
                "Valid order should be accepted with HTTP 201"
            );
            
            $this->assertTrue(
                $response->json('success'),
                "Response should indicate success"
            );
            
            $this->assertEquals(
                'Order created successfully',
                $response->json('message'),
                "Response should have success message"
            );
            
            $this->assertNotNull(
                $response->json('data.order'),
                "Response should contain order data"
            );
        }
    }

    /**
     * Property Test: Valid orders with multiple items should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * Verifies that orders with multiple valid items are accepted.
     */
    #[Test]
    public function valid_order_with_multiple_items_is_accepted(): void
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
            
            // Create multiple products
            $numProducts = fake()->numberBetween(2, 10);
            $products = Product::factory()->count($numProducts)->create([
                'company_user_id' => $company->id,
                'is_active' => true
            ]);

            $orderItems = [];
            foreach ($products as $product) {
                $qty = fake()->numberBetween(1, 100);
                $unitPrice = round($product->base_price, 2);
                $finalTotal = round($unitPrice * $qty, 2);
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unitPrice,
                    'discount_amount_snapshot' => 0.00,
                    'final_line_total_snapshot' => $finalTotal,
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

            // Assert: Request should be accepted with HTTP 201
            $this->assertEquals(
                201,
                $response->status(),
                "Valid order with {$numProducts} items should be accepted with HTTP 201"
            );
            
            $this->assertTrue($response->json('success'));
            $this->assertNotNull($response->json('data.order'));
            
            // Verify all items are in response
            $responseItems = $response->json('data.order.items');
            $this->assertCount(
                $numProducts,
                $responseItems,
                "Response should contain all {$numProducts} items"
            );
        }
    }

    /**
     * Property Test: Valid orders with notes should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3, 4.6**
     * 
     * Verifies that orders with optional notes are accepted and notes are persisted.
     */
    #[Test]
    public function valid_order_with_notes_is_accepted(): void
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

            $qty = fake()->numberBetween(1, 100);
            $unitPrice = round($product->base_price, 2);
            $finalTotal = round($unitPrice * $qty, 2);
            $notes = fake()->sentence();
            
            $orderData = [
                'company_id' => $company->id,
                'notes' => $notes,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => $finalTotal,
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be accepted with HTTP 201
            $this->assertEquals(201, $response->status());
            $this->assertTrue($response->json('success'));
            
            // Verify notes are in response
            $this->assertEquals(
                $notes,
                $response->json('data.order.notes'),
                "Response should contain the submitted notes"
            );
        }
    }

    /**
     * Property Test: Valid orders with various quantities should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * Verifies that orders with different valid quantity values are accepted.
     */
    #[Test]
    public function valid_order_with_various_quantities_is_accepted(): void
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

            // Test various valid quantities
            $qty = fake()->randomElement([
                1,                              // Minimum valid
                fake()->numberBetween(2, 10),   // Small
                fake()->numberBetween(11, 100), // Medium
                fake()->numberBetween(101, 1000), // Large
                fake()->numberBetween(1001, 10000) // Very large
            ]);
            
            $unitPrice = round($product->base_price, 2);
            $finalTotal = round($unitPrice * $qty, 2);
            
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => $finalTotal,
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be accepted with HTTP 201
            $this->assertEquals(
                201,
                $response->status(),
                "Valid order with qty={$qty} should be accepted with HTTP 201"
            );
            
            // Verify quantity in response
            $responseQty = $response->json('data.order.items.0.qty');
            $this->assertEquals(
                $qty,
                $responseQty,
                "Response should contain the correct quantity"
            );
        }
    }

    /**
     * Property Test: Valid orders with various prices should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * Verifies that orders with different valid price values are accepted.
     */
    #[Test]
    public function valid_order_with_various_prices_is_accepted(): void
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
            
            // Test various valid prices
            $price = fake()->randomElement([
                0.01,                           // Minimum valid
                fake()->randomFloat(2, 0.01, 1), // Very small
                fake()->randomFloat(2, 1, 10),   // Small
                fake()->randomFloat(2, 10, 100), // Medium
                fake()->randomFloat(2, 100, 1000), // Large
                fake()->randomFloat(2, 1000, 10000) // Very large
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $price,
                'is_active' => true
            ]);

            $qty = fake()->numberBetween(1, 100);
            $finalTotal = round($price * $qty, 2);
            
            $orderData = [
                'company_id' => $company->id,
                'order_items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price_snapshot' => $price,
                        'discount_amount_snapshot' => 0.00,
                        'final_line_total_snapshot' => $finalTotal,
                        'selected_offer_id' => null
                    ]
                ]
            ];

            // Act: Submit order
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders', $orderData);

            // Assert: Request should be accepted with HTTP 201
            $this->assertEquals(
                201,
                $response->status(),
                "Valid order with price={$price} should be accepted with HTTP 201"
            );
            
            // Verify price in response
            $responsePrice = $response->json('data.order.items.0.unit_price');
            $this->assertEquals(
                round($price, 2),
                $responsePrice,
                "Response should contain the correct price"
            );
        }
    }
}
