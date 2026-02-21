<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use App\Services\OfferSelector;
use App\Services\PreviewValidator;
use App\Services\PricingCalculator;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Price Staleness Detection
 * 
 * **Validates: Requirements 9.8**
 * 
 * Property 37: For any confirmation request, if any product's current price differs
 * from the preview's unit_price by more than 0.01 (after ROUND_HALF_UP rounding),
 * the system should reject with HTTP 409 and keep the preview_token in cache.
 */
class PriceStalenessDetectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test 37: Price change detection
     * 
     * **Validates: Requirements 9.8**
     * 
     * For any confirmation request, if any product's current price differs from
     * the preview's unit_price by more than 0.01 (after ROUND_HALF_UP rounding),
     * the system should reject with HTTP 409 and keep the preview_token in cache.
     */
    #[Test]
    public function price_change_exceeding_tolerance_is_detected(): void
    {
        // Feature: order-creation-api, Property 37: Price change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and product
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            // Generate original price
            $originalPrice = fake()->randomFloat(2, 1, 1000);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $originalPrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(1, 1000);
            
            // Create preview data with original price
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($originalPrice, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ]
                ]
            ];
            
            // Generate a price change that exceeds 0.01 tolerance
            // Change can be positive or negative
            $priceChange = fake()->randomFloat(2, 0.02, 100);
            if (fake()->boolean()) {
                $priceChange = -$priceChange;
            }
            
            $newPrice = $originalPrice + $priceChange;
            
            // Update product price to simulate price change
            $product->base_price = $newPrice;
            $product->save();
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should detect price change
            $roundedOriginal = round($originalPrice, 2, PHP_ROUND_HALF_UP);
            $roundedNew = round($newPrice, 2, PHP_ROUND_HALF_UP);
            $actualDifference = abs($roundedNew - $roundedOriginal);
            
            $this->assertFalse(
                $result['valid'],
                "Should detect price change when difference exceeds 0.01. " .
                "Original: {$roundedOriginal}, New: {$roundedNew}, " .
                "Difference: {$actualDifference}, Iteration: {$i}"
            );
            
            $this->assertNotEmpty(
                $result['changes'],
                "Should have changes array when price changed (iteration {$i})"
            );
            
            // Verify the change details
            $priceChange = collect($result['changes'])->firstWhere('type', 'price_changed');
            
            $this->assertNotNull(
                $priceChange,
                "Should have a price_changed entry in changes (iteration {$i})"
            );
            
            $this->assertEquals(
                $product->id,
                $priceChange['product_id'],
                "Price change should reference correct product (iteration {$i})"
            );
            
            $this->assertEquals(
                $product->name,
                $priceChange['product_name'],
                "Price change should include product name (iteration {$i})"
            );
            
            $this->assertEquals(
                $roundedOriginal,
                $priceChange['preview_price'],
                "Price change should include preview price (iteration {$i})"
            );
            
            $this->assertEquals(
                $roundedNew,
                $priceChange['current_price'],
                "Price change should include current price (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 37: Price change within tolerance is not detected
     * 
     * **Validates: Requirements 9.8**
     * 
     * For any confirmation request, if the price difference is within 0.01 tolerance
     * (after ROUND_HALF_UP rounding), the system should accept the preview as valid.
     */
    #[Test]
    public function price_change_within_tolerance_is_not_detected(): void
    {
        // Feature: order-creation-api, Property 37: Price change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and product
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            // Generate original price
            $originalPrice = fake()->randomFloat(2, 1, 1000);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $originalPrice,
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(1, 1000);
            
            // Create preview data with original price
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($originalPrice, 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ]
                ]
            ];
            
            // Generate a price change within 0.01 tolerance
            // This includes: exactly 0.01, 0.005, 0.001, 0.00, etc.
            $priceChange = fake()->randomFloat(4, 0, 0.01);
            if (fake()->boolean()) {
                $priceChange = -$priceChange;
            }
            
            $newPrice = $originalPrice + $priceChange;
            
            // Update product price
            $product->base_price = $newPrice;
            $product->save();
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should NOT detect price change if within tolerance
            $roundedOriginal = round($originalPrice, 2, PHP_ROUND_HALF_UP);
            $roundedNew = round($newPrice, 2, PHP_ROUND_HALF_UP);
            $actualDifference = abs($roundedNew - $roundedOriginal);
            
            // Verify no price_changed entry exists when within tolerance
            $priceChange = collect($result['changes'])->firstWhere('type', 'price_changed');
            
            if ($actualDifference <= 0.01) {
                $this->assertNull(
                    $priceChange,
                    "Should NOT have a price_changed entry when difference is within 0.01 tolerance. " .
                    "Original: {$roundedOriginal}, New: {$roundedNew}, " .
                    "Difference: {$actualDifference}, Iteration: {$i}"
                );
            } else {
                // If rounding caused difference to exceed tolerance, it should be detected
                $this->assertNotNull(
                    $priceChange,
                    "Should have a price_changed entry when rounding causes difference to exceed 0.01. " .
                    "Original: {$roundedOriginal}, New: {$roundedNew}, " .
                    "Difference: {$actualDifference}, Iteration: {$i}"
                );
            }
        }
    }

    /**
     * Property Test 37: Tolerance boundary behavior
     * 
     * **Validates: Requirements 9.8**
     * 
     * For any confirmation request, the system should use > 0.01 comparison,
     * meaning differences of exactly 0.01 or less should not be detected.
     * 
     * Note: Due to floating-point precision, we test cases where the difference
     * is clearly within or clearly exceeds the tolerance, avoiding exact boundary cases.
     */
    #[Test]
    public function tolerance_boundary_behavior_is_correct(): void
    {
        // Feature: order-creation-api, Property 37: Price change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        // Test cases with clear tolerance boundaries
        $testCases = [
            // Cases that should NOT be detected (within tolerance)
            ['original' => 10.00, 'new' => 10.00, 'should_detect' => false, 'description' => 'no change'],
            ['original' => 10.00, 'new' => 10.005, 'should_detect' => false, 'description' => 'rounds to same value'],
            ['original' => 10.004, 'new' => 10.005, 'should_detect' => false, 'description' => 'both round to 10.00'],
            
            // Cases that should be detected (exceeds tolerance)
            ['original' => 10.00, 'new' => 10.02, 'should_detect' => true, 'description' => 'clearly exceeds 0.01'],
            ['original' => 10.00, 'new' => 10.05, 'should_detect' => true, 'description' => 'clearly exceeds 0.01'],
            ['original' => 10.00, 'new' => 9.98, 'should_detect' => true, 'description' => 'clearly exceeds 0.01 (negative)'],
        ];
        
        foreach ($testCases as $index => $testCase) {
            // Arrange: Create customer and product
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $testCase['original'],
                'is_active' => true
            ]);
            
            $qty = fake()->numberBetween(1, 1000);
            
            // Create preview data with original price
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'unit_price' => round($testCase['original'], 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ]
                ]
            ];
            
            // Update product price
            $product->base_price = $testCase['new'];
            $product->save();
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert
            $roundedOriginal = round($testCase['original'], 2, PHP_ROUND_HALF_UP);
            $roundedNew = round($testCase['new'], 2, PHP_ROUND_HALF_UP);
            $actualDifference = abs($roundedNew - $roundedOriginal);
            
            $priceChange = collect($result['changes'])->firstWhere('type', 'price_changed');
            
            if ($testCase['should_detect']) {
                $this->assertNotNull(
                    $priceChange,
                    "Test case {$index} ({$testCase['description']}): Should detect price change. " .
                    "Original: {$roundedOriginal}, New: {$roundedNew}, " .
                    "Difference: {$actualDifference}"
                );
            } else {
                $this->assertNull(
                    $priceChange,
                    "Test case {$index} ({$testCase['description']}): Should NOT detect price change. " .
                    "Original: {$roundedOriginal}, New: {$roundedNew}, " .
                    "Difference: {$actualDifference}"
                );
            }
        }
    }

    /**
     * Property Test 37: Multiple products with mixed price changes
     * 
     * **Validates: Requirements 9.8**
     * 
     * For any confirmation request with multiple products, if any product's price
     * exceeds the tolerance, the entire preview should be invalidated.
     */
    #[Test]
    public function multiple_products_with_any_price_change_invalidates_preview(): void
    {
        // Feature: order-creation-api, Property 37: Price change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        for ($i = 0; $i < 100; $i++) {
            // Arrange: Create customer and company
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            // Create 2-5 products
            $numProducts = fake()->numberBetween(2, 5);
            $products = [];
            $previewItems = [];
            
            for ($j = 0; $j < $numProducts; $j++) {
                $originalPrice = fake()->randomFloat(2, 1, 1000);
                
                $product = Product::factory()->create([
                    'company_user_id' => $company->id,
                    'base_price' => $originalPrice,
                    'is_active' => true
                ]);
                
                $products[] = [
                    'product' => $product,
                    'original_price' => $originalPrice
                ];
                
                $previewItems[] = [
                    'product_id' => $product->id,
                    'qty' => fake()->numberBetween(1, 100),
                    'unit_price' => round($originalPrice, 2, PHP_ROUND_HALF_UP),
                    'selected_offer_id' => null
                ];
            }
            
            // Change price of at least one product to exceed tolerance
            $productToChange = fake()->numberBetween(0, $numProducts - 1);
            $priceChange = fake()->randomFloat(2, 0.02, 100);
            if (fake()->boolean()) {
                $priceChange = -$priceChange;
            }
            
            $newPrice = $products[$productToChange]['original_price'] + $priceChange;
            $products[$productToChange]['product']->base_price = $newPrice;
            $products[$productToChange]['product']->save();
            
            $previewData = ['items' => $previewItems];
            
            // Act: Revalidate preview
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert: Should detect price change
            $this->assertFalse(
                $result['valid'],
                "Should invalidate preview when any product price changes beyond tolerance (iteration {$i})"
            );
            
            $this->assertNotEmpty(
                $result['changes'],
                "Should have changes array (iteration {$i})"
            );
            
            // Verify at least one price_changed entry exists
            $priceChanges = collect($result['changes'])->where('type', 'price_changed');
            
            $this->assertGreaterThan(
                0,
                $priceChanges->count(),
                "Should have at least one price_changed entry (iteration {$i})"
            );
        }
    }

    /**
     * Property Test 37: Rounding behavior with ROUND_HALF_UP
     * 
     * **Validates: Requirements 9.8**
     * 
     * For any confirmation request, the price comparison should use ROUND_HALF_UP
     * rounding to 2 decimal places before checking the 0.01 tolerance.
     */
    #[Test]
    public function price_comparison_uses_round_half_up(): void
    {
        // Feature: order-creation-api, Property 37: Price change detection
        
        $orderRepository = app(OrderRepository::class);
        $offerSelector = app(OfferSelector::class);
        $pricingCalculator = app(PricingCalculator::class);
        $previewValidator = new PreviewValidator($orderRepository, $offerSelector, $pricingCalculator);
        
        // Test specific rounding edge cases
        $testCases = [
            ['original' => 10.004, 'new' => 10.005, 'should_detect' => false], // Both round to 10.00
            ['original' => 10.005, 'new' => 10.015, 'should_detect' => false], // 10.01 vs 10.02, diff = 0.01 (boundary)
            ['original' => 10.005, 'new' => 10.025, 'should_detect' => true],  // 10.01 vs 10.03, diff = 0.02
            ['original' => 10.994, 'new' => 10.995, 'should_detect' => false], // Both round to 10.99
            ['original' => 10.995, 'new' => 11.005, 'should_detect' => false], // 11.00 vs 11.01, diff = 0.01 (boundary)
        ];
        
        foreach ($testCases as $index => $testCase) {
            // Arrange
            $customer = User::factory()->create([
                'user_type' => 'customer',
                'is_active' => true,
                'email' => 'customer_' . uniqid() . '@example.com'
            ]);
            
            $company = User::factory()->create([
                'user_type' => 'company',
                'is_active' => true,
                'email' => 'company_' . uniqid() . '@example.com'
            ]);
            
            $product = Product::factory()->create([
                'company_user_id' => $company->id,
                'base_price' => $testCase['original'],
                'is_active' => true
            ]);
            
            $previewData = [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'qty' => 100,
                        'unit_price' => round($testCase['original'], 2, PHP_ROUND_HALF_UP),
                        'selected_offer_id' => null
                    ]
                ]
            ];
            
            // Update price
            $product->base_price = $testCase['new'];
            $product->save();
            
            // Act
            $result = $previewValidator->revalidate($previewData, $customer);
            
            // Assert
            $roundedOriginal = round($testCase['original'], 2, PHP_ROUND_HALF_UP);
            $roundedNew = round($testCase['new'], 2, PHP_ROUND_HALF_UP);
            $actualDifference = abs($roundedNew - $roundedOriginal);
            
            if ($testCase['should_detect']) {
                $this->assertFalse(
                    $result['valid'],
                    "Test case {$index}: Should detect price change. " .
                    "Original: {$testCase['original']} -> {$roundedOriginal}, " .
                    "New: {$testCase['new']} -> {$roundedNew}, " .
                    "Difference: {$actualDifference}"
                );
            } else {
                $this->assertTrue(
                    $result['valid'],
                    "Test case {$index}: Should NOT detect price change. " .
                    "Original: {$testCase['original']} -> {$roundedOriginal}, " .
                    "New: {$testCase['new']} -> {$roundedNew}, " .
                    "Difference: {$actualDifference}"
                );
            }
        }
    }
}
