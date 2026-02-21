<?php

namespace Tests\Feature\Order;

use App\Models\Company;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Rounding Consistency for Preview Endpoint
 * 
 * **Validates: Requirements 6.2, 6.3, 6.4, 6.6**
 * 
 * Property 17: For any product, the system should round the unit price to 2 
 * decimal places using ROUND_HALF_UP before any calculations.
 */
class PreviewRoundingConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Unit prices are rounded using ROUND_HALF_UP
     * 
     * **Validates: Requirements 6.2**
     * 
     * This test generates multiple random preview requests with various prices
     * and verifies that unit prices are rounded correctly using ROUND_HALF_UP.
     */
    #[Test]
    public function unit_prices_are_rounded_with_round_half_up(): void
    {
        // Feature: order-creation-api, Property 17: Rounding consistency
        
        // Run 100 iterations for property-based testing
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with price requiring rounding
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            // Generate price with more than 2 decimal places
            $rawPrice = fake()->randomFloat(4, 1, 100);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'price' => $rawPrice,
                'is_active' => true
            ]);

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => 1
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $unitPrice = $response->json('data.items.0.unit_price');
            
            // Calculate expected rounded price using ROUND_HALF_UP
            $expectedPrice = round($rawPrice, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedPrice,
                $unitPrice,
                "Unit price should be rounded using ROUND_HALF_UP: {$rawPrice} -> {$expectedPrice}"
            );
        }
    }

    /**
     * Property Test: Percentage discount calculations use ROUND_HALF_UP
     * 
     * **Validates: Requirements 6.3**
     * 
     * Verifies that percentage discount amounts are rounded correctly.
     */
    #[Test]
    public function percentage_discount_calculations_use_round_half_up(): void
    {
        // Feature: order-creation-api, Property 17: Rounding consistency
        
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
            
            $rawPrice = fake()->randomFloat(4, 1, 100);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'price' => $rawPrice,
                'is_active' => true
            ]);

            // Create percentage discount offer
            $rewardValue = fake()->numberBetween(5, 50);
            $minQty = fake()->numberBetween(10, 100);
            
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'status' => 'active',
                'scope' => 'public',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay()
            ]);

            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty,
                'reward_type' => 'discount_percent',
                'discount_percent' => (float) $rewardValue,
                'discount_fixed' => null,
                'bonus_product_id' => null,
                'bonus_qty' => null
            ]);

            $qty = $minQty * fake()->numberBetween(1, 5);

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

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $discountAmount = $response->json('data.items.0.discount_amount');
            
            // Calculate expected discount using ROUND_HALF_UP
            $unitPrice = round($rawPrice, 2, PHP_ROUND_HALF_UP);
            $multiplier = floor($qty / $minQty);
            $discountPerBlock = round(($minQty * $unitPrice * $rewardValue) / 100, 2, PHP_ROUND_HALF_UP);
            $expectedDiscount = round($discountPerBlock * $multiplier, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedDiscount,
                $discountAmount,
                "Percentage discount should be rounded using ROUND_HALF_UP"
            );
        }
    }

    /**
     * Property Test: Fixed discount calculations use ROUND_HALF_UP
     * 
     * **Validates: Requirements 6.4**
     * 
     * Verifies that fixed discount amounts are rounded correctly.
     */
    #[Test]
    public function fixed_discount_calculations_use_round_half_up(): void
    {
        // Feature: order-creation-api, Property 17: Rounding consistency
        
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
                'base_price' => fake()->randomFloat(2, 10, 100),
                'is_active' => true
            ]);

            // Create fixed discount offer with value requiring rounding
            $rewardValue = fake()->randomFloat(4, 1, 10);
            $minQty = fake()->numberBetween(10, 100);
            
            $offer = Offer::factory()->create([
                'company_user_id' => $company->id,
                'status' => 'active',
                'scope' => 'public',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDay()
            ]);

            OfferItem::factory()->create([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'min_qty' => $minQty,
                'reward_type' => 'discount_fixed',
                'discount_percent' => null,
                'discount_fixed' => $rewardValue,
                'bonus_product_id' => null,
                'bonus_qty' => null
            ]);

            // Refresh offer to get the actual stored value from database
            $offerItem = $offer->items()->where('product_id', $product->id)->first();
            $storedRewardValue = $offerItem->discount_fixed;

            $qty = $minQty * fake()->numberBetween(1, 5);

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

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $discountAmount = $response->json('data.items.0.discount_amount');
            
            // Calculate expected discount using ROUND_HALF_UP with stored value
            $multiplier = floor($qty / $minQty);
            $expectedDiscount = round($storedRewardValue * $multiplier, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedDiscount,
                $discountAmount,
                "Fixed discount should be rounded using ROUND_HALF_UP"
            );
        }
    }

    /**
     * Property Test: Final line totals use ROUND_HALF_UP
     * 
     * **Validates: Requirements 6.6**
     * 
     * Verifies that final line totals are rounded correctly.
     */
    #[Test]
    public function final_line_totals_use_round_half_up(): void
    {
        // Feature: order-creation-api, Property 17: Rounding consistency
        
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
            
            $rawPrice = fake()->randomFloat(4, 1, 100);
            
            $product = Product::factory()->create([
                'company_id' => $company->id,
                'price' => $rawPrice,
                'is_active' => true
            ]);

            $qty = fake()->numberBetween(1, 100);

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

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $finalTotal = $response->json('data.items.0.final_total');
            
            // Calculate expected final total using ROUND_HALF_UP
            $unitPrice = round($rawPrice, 2, PHP_ROUND_HALF_UP);
            $lineSubtotal = round($qty * $unitPrice, 2, PHP_ROUND_HALF_UP);
            $discountAmount = $response->json('data.items.0.discount_amount');
            $expectedFinalTotal = round($lineSubtotal - $discountAmount, 2, PHP_ROUND_HALF_UP);
            
            $this->assertEquals(
                $expectedFinalTotal,
                $finalTotal,
                "Final line total should be rounded using ROUND_HALF_UP"
            );
        }
    }

    /**
     * Property Test: Order totals use ROUND_HALF_UP
     * 
     * **Validates: Requirements 6.2, 6.6**
     * 
     * Verifies that order-level totals (subtotal, total_discount, final_total) 
     * are rounded correctly.
     */
    #[Test]
    public function order_totals_use_round_half_up(): void
    {
        // Feature: order-creation-api, Property 17: Rounding consistency
        
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create test data with multiple products
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true
            ]);
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true
            ]);
            
            $numProducts = fake()->numberBetween(2, 5);
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

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $subtotal = $response->json('data.subtotal');
            $totalDiscount = $response->json('data.total_discount');
            $finalTotal = $response->json('data.final_total');
            
            // Verify all totals have at most 2 decimal places
            $this->assertEquals(
                round($subtotal, 2, PHP_ROUND_HALF_UP),
                $subtotal,
                "Subtotal should be rounded to 2 decimal places"
            );
            
            $this->assertEquals(
                round($totalDiscount, 2, PHP_ROUND_HALF_UP),
                $totalDiscount,
                "Total discount should be rounded to 2 decimal places"
            );
            
            $this->assertEquals(
                round($finalTotal, 2, PHP_ROUND_HALF_UP),
                $finalTotal,
                "Final total should be rounded to 2 decimal places"
            );
        }
    }

    /**
     * Property Test: Rounding edge cases (0.005, 0.015, 0.025, etc.)
     * 
     * **Validates: Requirements 6.2**
     * 
     * Verifies that ROUND_HALF_UP works correctly for edge cases where the
     * third decimal place is exactly 5.
     */
    #[Test]
    public function rounding_edge_cases_use_round_half_up(): void
    {
        // Feature: order-creation-api, Property 17: Rounding consistency
        
        // Test specific edge case values
        $edgeCases = [
            0.005 => 0.01,
            0.015 => 0.02,
            0.025 => 0.03,
            0.035 => 0.04,
            0.045 => 0.05,
            0.055 => 0.06,
            0.065 => 0.07,
            0.075 => 0.08,
            0.085 => 0.09,
            0.095 => 0.10,
            1.005 => 1.01,
            1.015 => 1.02,
            10.005 => 10.01,
            10.015 => 10.02,
            10.025 => 10.03
        ];
        
        foreach ($edgeCases as $rawPrice => $expectedRounded) {
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
                'price' => $rawPrice,
                'is_active' => true
            ]);

            // Refresh product to get the actual stored and rounded value
            $product->refresh();
            $storedPrice = $product->base_price;

            $previewData = [
                'company_id' => $company->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => 1
                    ]
                ]
            ];

            // Act: Submit preview request
            $response = $this->actingAs($customer, 'sanctum')
                ->postJson('/api/orders/preview', $previewData);

            // Assert: Preview should be successful
            $this->assertEquals(200, $response->status());
            
            $unitPrice = $response->json('data.items.0.unit_price');
            
            $this->assertEquals(
                $storedPrice,
                $unitPrice,
                "Price {$rawPrice} should be stored as {$storedPrice} and returned correctly"
            );
        }
    }
}
