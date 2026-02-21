<?php

namespace Tests\Feature\Order;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Valid Request Acceptance for Preview Endpoint
 * 
 * **Validates: Requirements 1.2, 1.3, 2.6**
 * 
 * Property 1: For any structurally valid preview request with valid company_id, 
 * non-empty items array, valid product_ids, and positive quantities, the system 
 * should accept the request and return HTTP 200 with preview data.
 */
class PreviewValidRequestAcceptancePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Valid preview requests should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * This test generates multiple random valid preview requests and verifies
     * that all are accepted with HTTP 200 and return preview data.
     */
    #[Test]
    public function valid_preview_request_is_accepted(): void
    {
        // Feature: order-creation-api, Property 1: Valid request acceptance
        
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

            $qty = fake()->numberBetween(1, 1000);
            
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be accepted with HTTP 200
            $this->assertEquals(
                200,
                $response->status(),
                "Valid preview request should be accepted with HTTP 200"
            );
            
            // Verify response structure
            $response->assertJsonStructure([
                'success',
                'data' => [
                    'preview_token',
                    'items',
                    'subtotal',
                    'total_discount',
                    'final_total'
                ]
            ]);
            
            // Verify preview token format
            $previewToken = $response->json('data.preview_token');
            $this->assertMatchesRegularExpression(
                '/^PV-\d{8}-[A-Z0-9]{4}$/',
                $previewToken,
                "Preview token should match format PV-YYYYMMDD-XXXX"
            );
        }
    }

    /**
     * Property Test: Valid preview with multiple items should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * Verifies that preview requests with multiple valid items are accepted.
     */
    #[Test]
    public function valid_preview_with_multiple_items_is_accepted(): void
    {
        // Feature: order-creation-api, Property 1: Valid request acceptance
        
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
            $numProducts = fake()->numberBetween(2, 10);
            $products = Product::factory()->count($numProducts)->create([
                'company_id' => $company->id,
                'is_active' => true
            ]);

            $items = [];
            foreach ($products as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 1000)
                ];
            }
            
            $previewData = [
                'company_id' => $company->id,
                'items' => $items
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Request should be accepted with HTTP 200
            $this->assertEquals(
                200,
                $response->status(),
                "Valid preview with {$numProducts} items should be accepted with HTTP 200"
            );
            
            // Verify all items are in response
            $responseItems = $response->json('data.items');
            $this->assertCount(
                $numProducts,
                $responseItems,
                "Response should contain all {$numProducts} items"
            );
        }
    }

    /**
     * Property Test: Valid preview with optional notes should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * Verifies that preview requests with notes are accepted and notes are preserved.
     */
    #[Test]
    public function valid_preview_with_notes_is_accepted(): void
    {
        // Feature: order-creation-api, Property 1: Valid request acceptance
        
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

            // Assert: Request should be accepted with HTTP 200
            $this->assertEquals(
                200,
                $response->status(),
                "Valid preview with notes should be accepted with HTTP 200"
            );
            
            // Verify notes are preserved
            $this->assertEquals(
                $notes,
                $response->json('data.notes'),
                "Notes should be preserved in preview response"
            );
        }
    }

    /**
     * Property Test: Valid preview with varying quantities should be accepted
     * 
     * **Validates: Requirements 1.2, 1.3**
     * 
     * Verifies that preview requests with various valid quantity values are accepted.
     */
    #[Test]
    public function valid_preview_with_varying_quantities_is_accepted(): void
    {
        // Feature: order-creation-api, Property 1: Valid request acceptance
        
        // Test various quantity ranges
        $quantityRanges = [
            [1, 10],           // Small quantities
            [11, 100],         // Medium quantities
            [101, 1000],       // Large quantities
            [1001, 10000]      // Very large quantities
        ];
        
        foreach ($quantityRanges as $range) {
            for ($i = 0; $i < 25; $i++) {
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

                $qty = fake()->numberBetween($range[0], $range[1]);
                
                $previewData = [
                    'company_id' => $company->id,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'qty' => $qty
                        ]
                    ]
                ];

                // Act: Submit preview request
                $response = $this->actingAs($customer, 'sanctum')
                    ->postJson('/api/orders/preview', $previewData);

                // Assert: Request should be accepted with HTTP 200
                $this->assertEquals(
                    200,
                    $response->status(),
                    "Valid preview with qty={$qty} should be accepted with HTTP 200"
                );
            }
        }
    }

    /**
     * Property Test: Valid preview response contains calculated totals
     * 
     * **Validates: Requirements 1.3**
     * 
     * Verifies that preview responses contain properly calculated totals.
     */
    #[Test]
    public function valid_preview_response_contains_calculated_totals(): void
    {
        // Feature: order-creation-api, Property 1: Valid request acceptance
        
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

            $qty = fake()->numberBetween(1, 1000);
            
            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Response contains calculated totals
            $this->assertEquals(200, $response->status());
            
            $subtotal = $response->json('data.subtotal');
            $totalDiscount = $response->json('data.total_discount');
            $finalTotal = $response->json('data.final_total');
            
            $this->assertIsNumeric($subtotal, "Subtotal should be numeric");
            $this->assertIsNumeric($totalDiscount, "Total discount should be numeric");
            $this->assertIsNumeric($finalTotal, "Final total should be numeric");
            
            // Verify totals are non-negative
            $this->assertGreaterThanOrEqual(0, $subtotal, "Subtotal should be non-negative");
            $this->assertGreaterThanOrEqual(0, $totalDiscount, "Total discount should be non-negative");
            $this->assertGreaterThanOrEqual(0, $finalTotal, "Final total should be non-negative");
        }
    }
}
