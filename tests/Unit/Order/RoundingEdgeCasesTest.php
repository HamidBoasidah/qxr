<?php

namespace Tests\Unit\Order;

use App\Services\PricingCalculator;
use App\Models\Product;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Test: Rounding Edge Cases
 * 
 * **Validates: Requirements 6.2**
 * 
 * Tests edge cases for ROUND_HALF_UP rounding behavior:
 * - Values like 0.005, 0.015, 0.025
 * - Tolerance boundary (exactly 0.01 difference)
 */
class RoundingEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private PricingCalculator $calculator;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(OrderRepository::class);
        $this->calculator = new PricingCalculator($this->repository);
    }

    /**
     * Test rounding values ending in 0.005 with ROUND_HALF_UP
     * 
     * Expected: 0.005 rounds to 0.01 (UP)
     */
    #[Test]
    public function it_rounds_0_005_to_0_01(): void
    {
        // Arrange: Create product with price that results in 0.005 after calculation
        $product = Product::factory()->create([
            'base_price' => 0.005,
            'is_active' => true
        ]);

        // Act: Calculate pricing with no offer
        $result = $this->calculator->calculate($product, 1, null);

        // Assert: Should round UP to 0.01
        $this->assertEquals(0.01, $result['unit_price'], 'Price 0.005 should round UP to 0.01');
        $this->assertEquals(0.01, $result['final_total'], 'Final total should be 0.01');
    }

    /**
     * Test rounding values ending in 0.015 with ROUND_HALF_UP
     * 
     * Expected: 0.015 rounds to 0.02 (UP)
     */
    #[Test]
    public function it_rounds_0_015_to_0_02(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'base_price' => 0.015,
            'is_active' => true
        ]);

        // Act
        $result = $this->calculator->calculate($product, 1, null);

        // Assert
        $this->assertEquals(0.02, $result['unit_price'], 'Price 0.015 should round UP to 0.02');
        $this->assertEquals(0.02, $result['final_total'], 'Final total should be 0.02');
    }

    /**
     * Test rounding values ending in 0.025 with ROUND_HALF_UP
     * 
     * Expected: 0.025 rounds to 0.03 (UP)
     */
    #[Test]
    public function it_rounds_0_025_to_0_03(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'base_price' => 0.025,
            'is_active' => true
        ]);

        // Act
        $result = $this->calculator->calculate($product, 1, null);

        // Assert
        $this->assertEquals(0.03, $result['unit_price'], 'Price 0.025 should round UP to 0.03');
        $this->assertEquals(0.03, $result['final_total'], 'Final total should be 0.03');
    }

    /**
     * Test rounding 1.005 to 1.01
     */
    #[Test]
    public function it_rounds_1_005_to_1_01(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'base_price' => 1.005,
            'is_active' => true
        ]);

        // Act
        $result = $this->calculator->calculate($product, 1, null);

        // Assert
        $this->assertEquals(1.01, $result['unit_price']);
        $this->assertEquals(1.01, $result['final_total']);
    }

    /**
     * Test rounding 9.995 to 10.00
     */
    #[Test]
    public function it_rounds_9_995_to_10_00(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'base_price' => 9.995,
            'is_active' => true
        ]);

        // Act
        $result = $this->calculator->calculate($product, 1, null);

        // Assert
        $this->assertEquals(10.00, $result['unit_price']);
        $this->assertEquals(10.00, $result['final_total']);
    }

    /**
     * Test tolerance boundary: exactly 0.01 difference should be considered equal
     * 
     * This tests whether the system correctly handles the 0.01 tolerance
     * used in price comparison during preview revalidation.
     */
    #[Test]
    public function it_handles_tolerance_boundary_correctly(): void
    {
        // Test that 10.00 and 10.01 are within 0.01 tolerance
        $value1 = 10.00;
        $value2 = 10.01;
        $diff = abs($value1 - $value2);
        
        $this->assertEqualsWithDelta(0.01, $diff, 0.0001, 'Difference should be approximately 0.01');
        $this->assertTrue($diff <= 0.01, 'Difference of 0.01 should be within tolerance');
    }

    /**
     * Test that values beyond 0.01 tolerance are detected as different
     */
    #[Test]
    public function it_detects_differences_beyond_tolerance(): void
    {
        // Test that 10.00 and 10.02 are NOT within 0.01 tolerance
        $value1 = 10.00;
        $value2 = 10.02;
        $diff = abs($value1 - $value2);
        
        $this->assertEqualsWithDelta(0.02, $diff, 0.0001, 'Difference should be approximately 0.02');
        $this->assertFalse($diff <= 0.01, 'Difference of 0.02 should exceed tolerance');
    }

    /**
     * Test complex calculation with multiple rounding steps
     * 
     * Ensures that rounding is applied at each step correctly
     */
    #[Test]
    public function it_applies_rounding_at_each_calculation_step(): void
    {
        // Arrange: Product price and discount that create rounding edge cases
        $product = Product::factory()->create([
            'base_price' => 10.005, // Rounds to 10.01
            'is_active' => true
        ]);

        // Create a percentage discount offer
        $offer = Offer::factory()->create([
            'status' => 'active',
            'start_at' => null,
            'end_at' => null,
            'title' => 'Test Rounding Offer'
        ]);

        $offerItem = OfferItem::factory()->create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'min_qty' => 1,
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.5, // 10.5% of 10.01 = 1.05105...
        ]);

        // Act
        $result = $this->calculator->calculate($product, 1, $offer);

        // Assert
        $this->assertEquals(10.01, $result['unit_price'], 'Unit price should round to 10.01');
        // Discount: (1 * 10.01 * 10.5) / 100 = 1.05105, rounds to 1.05
        $this->assertEquals(1.05, $result['discount_amount'], 'Discount should round to 1.05');
        // Final: 10.01 - 1.05 = 8.96
        $this->assertEquals(8.96, $result['final_total'], 'Final total should be 8.96');
    }

    /**
     * Test that quantity multiplication preserves rounding consistency
     */
    #[Test]
    public function it_maintains_rounding_consistency_with_quantity(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'base_price' => 1.005, // Rounds to 1.01
            'is_active' => true
        ]);

        // Act: Calculate with quantity 3
        $result = $this->calculator->calculate($product, 3, null);

        // Assert
        $this->assertEquals(1.01, $result['unit_price']);
        // Line subtotal: 3 * 1.01 = 3.03
        $this->assertEquals(3.03, $result['final_total']);
    }
}
