<?php

namespace Tests\Unit;

use App\Models\InvoiceItem;
use App\Models\ReturnPolicy;
use App\Services\ReturnRefundCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Unit tests for ReturnRefundCalculator.
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4
 *
 * Uses in-memory model instances (no database interaction).
 */
class ReturnRefundCalculatorTest extends TestCase
{
    private ReturnRefundCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ReturnRefundCalculator();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeItem(array $attrs): InvoiceItem
    {
        return (new InvoiceItem())->forceFill([
            'id'                  => $attrs['id']                  ?? 1,
            'unit_price_snapshot' => $attrs['unit_price_snapshot'] ?? 100.0,
            'qty'                 => $attrs['qty']                 ?? 1,
            'discount_type'       => $attrs['discount_type']       ?? null,
            'discount_value'      => $attrs['discount_value']      ?? null,
        ]);
    }

    private function makePolicy(bool $discountDeductionEnabled): ReturnPolicy
    {
        return (new ReturnPolicy())->forceFill([
            'discount_deduction_enabled' => $discountDeductionEnabled,
        ]);
    }

    // =========================================================================
    // Requirement 4.1 — Percent discount
    // =========================================================================

    /**
     * Validates: Requirements 4.1, 4.3
     * net_unit_price = unit_price × (1 − discount_value / 100)
     * refund = net_unit_price × returned_qty
     */
    public function test_percent_discount_calculates_net_unit_price(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 200.0, 'qty' => 5, 'discount_type' => 'percent', 'discount_value' => 10.0]);
        $policy = $this->makePolicy(true);

        // net_unit_price = 200 × 0.9 = 180 → refund = 180 × 2 = 360
        $result = $this->calculator->calculateItemRefund($item, 2, $policy);

        $this->assertSame(360.0, $result);
    }

    /**
     * Validates: Requirements 4.1, 4.3
     * Percent discount of 0% should return full price.
     */
    public function test_percent_discount_zero_returns_full_price(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 100.0, 'qty' => 3, 'discount_type' => 'percent', 'discount_value' => 0.0]);
        $policy = $this->makePolicy(true);

        $result = $this->calculator->calculateItemRefund($item, 3, $policy);

        $this->assertSame(300.0, $result);
    }

    /**
     * Validates: Requirements 4.1, 4.3
     * Percent discount of 100% should return 0.
     */
    public function test_percent_discount_100_returns_zero(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 50.0, 'qty' => 2, 'discount_type' => 'percent', 'discount_value' => 100.0]);
        $policy = $this->makePolicy(true);

        $result = $this->calculator->calculateItemRefund($item, 2, $policy);

        $this->assertSame(0.0, $result);
    }

    /**
     * Validates: Requirements 4.1, 4.3
     * Fractional percent discount — result rounded to 4 decimal places.
     */
    public function test_percent_discount_rounding_to_4_decimal_places(): void
    {
        // net_unit_price = 10 × (1 − 33.333/100) = 10 × 0.66667 = 6.6667
        $item   = $this->makeItem(['unit_price_snapshot' => 10.0, 'qty' => 1, 'discount_type' => 'percent', 'discount_value' => 33.333]);
        $policy = $this->makePolicy(true);

        $result = $this->calculator->calculateItemRefund($item, 1, $policy);

        $expected = round(round(10.0 * (1 - 33.333 / 100), 4, PHP_ROUND_HALF_UP) * 1, 4, PHP_ROUND_HALF_UP);
        $this->assertSame($expected, $result);
    }

    // =========================================================================
    // Requirement 4.2 — Fixed discount distribution
    // =========================================================================

    /**
     * Validates: Requirements 4.2
     * distributeFixedDiscount distributes proportionally by item value.
     *
     *   Item A: price=100, qty=2 → total=200
     *   Item B: price=50,  qty=4 → total=200
     *   invoice_total=400, fixed_discount=40
     *   Item A: proportional_discount=20 → net_unit=90
     *   Item B: proportional_discount=20 → net_unit=45
     */
    public function test_distribute_fixed_discount_proportionally(): void
    {
        $itemA = $this->makeItem(['id' => 1, 'unit_price_snapshot' => 100.0, 'qty' => 2]);
        $itemB = $this->makeItem(['id' => 2, 'unit_price_snapshot' => 50.0,  'qty' => 4]);

        $items  = new Collection([$itemA, $itemB]);
        $result = $this->calculator->distributeFixedDiscount($items, 40.0);

        $this->assertSame(90.0, $result[1]);
        $this->assertSame(45.0, $result[2]);
    }

    /**
     * Validates: Requirements 4.2
     * Unequal item totals distribute discount proportionally.
     *
     *   Item A: price=300, qty=1 → total=300
     *   Item B: price=100, qty=1 → total=100
     *   invoice_total=400, fixed_discount=80
     *   Item A: proportional_discount=60 → net_unit=240
     *   Item B: proportional_discount=20 → net_unit=80
     */
    public function test_distribute_fixed_discount_unequal_items(): void
    {
        $itemA = $this->makeItem(['id' => 10, 'unit_price_snapshot' => 300.0, 'qty' => 1]);
        $itemB = $this->makeItem(['id' => 20, 'unit_price_snapshot' => 100.0, 'qty' => 1]);

        $items  = new Collection([$itemA, $itemB]);
        $result = $this->calculator->distributeFixedDiscount($items, 80.0);

        $this->assertSame(240.0, $result[10]);
        $this->assertSame(80.0,  $result[20]);
    }

    /**
     * Validates: Requirements 4.2
     * Single item receives the full fixed discount.
     *
     *   Item: price=200, qty=3 → total=600, fixed_discount=60
     *   net_item_total=540, net_unit_price=180
     */
    public function test_distribute_fixed_discount_single_item(): void
    {
        $item  = $this->makeItem(['id' => 5, 'unit_price_snapshot' => 200.0, 'qty' => 3]);
        $items = new Collection([$item]);

        $result = $this->calculator->distributeFixedDiscount($items, 60.0);

        $this->assertSame(180.0, $result[5]);
    }

    /**
     * Validates: Requirements 4.2
     * distributeFixedDiscount result rounded to 4 decimal places.
     *
     *   Item A: price=10, qty=3 → total=30
     *   Item B: price=10, qty=7 → total=70
     *   invoice_total=100, fixed_discount=1
     *   Item A: proportional_discount=0.3 → net_total=29.7 → net_unit=9.9
     *   Item B: proportional_discount=0.7 → net_total=69.3 → net_unit=9.9
     */
    public function test_distribute_fixed_discount_rounding(): void
    {
        $itemA = $this->makeItem(['id' => 1, 'unit_price_snapshot' => 10.0, 'qty' => 3]);
        $itemB = $this->makeItem(['id' => 2, 'unit_price_snapshot' => 10.0, 'qty' => 7]);

        $items  = new Collection([$itemA, $itemB]);
        $result = $this->calculator->distributeFixedDiscount($items, 1.0);

        $this->assertSame(round(9.9, 4, PHP_ROUND_HALF_UP), $result[1]);
        $this->assertSame(round(9.9, 4, PHP_ROUND_HALF_UP), $result[2]);
    }

    /**
     * Validates: Requirements 4.2, 4.3
     * calculateItemRefundWithDistribution uses fixed discount distribution correctly.
     *
     *   Item A: price=100, qty=2 → total=200
     *   Item B: price=50,  qty=4 → total=200
     *   invoice_total=400, fixed_discount=40
     *   Item A net_unit_price=90 → returning 1 unit → refund=90
     */
    public function test_fixed_discount_refund_with_distribution(): void
    {
        $itemA = $this->makeItem(['id' => 1, 'unit_price_snapshot' => 100.0, 'qty' => 2, 'discount_type' => 'fixed', 'discount_value' => 40.0]);
        $itemB = $this->makeItem(['id' => 2, 'unit_price_snapshot' => 50.0,  'qty' => 4, 'discount_type' => 'fixed', 'discount_value' => 40.0]);

        $policy   = $this->makePolicy(true);
        $allItems = new Collection([$itemA, $itemB]);

        $result = $this->calculator->calculateItemRefundWithDistribution($itemA, 1, $policy, $allItems);

        $this->assertSame(90.0, $result);
    }

    // =========================================================================
    // Requirement 4.4 — discount_deduction_enabled = false uses full price
    // =========================================================================

    /**
     * Validates: Requirements 4.4
     * When discount_deduction_enabled = false, use full unit_price regardless of percent discount.
     */
    public function test_discount_deduction_disabled_ignores_percent_discount(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 100.0, 'qty' => 3, 'discount_type' => 'percent', 'discount_value' => 20.0]);
        $policy = $this->makePolicy(false);

        // full price: 100 × 2 = 200
        $result = $this->calculator->calculateItemRefund($item, 2, $policy);

        $this->assertSame(200.0, $result);
    }

    /**
     * Validates: Requirements 4.4
     * When discount_deduction_enabled = false, use full unit_price regardless of fixed discount.
     */
    public function test_discount_deduction_disabled_ignores_fixed_discount(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 50.0, 'qty' => 4, 'discount_type' => 'fixed', 'discount_value' => 100.0]);
        $policy = $this->makePolicy(false);

        // full price: 50 × 3 = 150
        $result = $this->calculator->calculateItemRefund($item, 3, $policy);

        $this->assertSame(150.0, $result);
    }

    /**
     * Validates: Requirements 4.4
     * When discount_deduction_enabled = false and no discount set, still uses full price.
     */
    public function test_discount_deduction_disabled_no_discount_uses_full_price(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 75.0, 'qty' => 2, 'discount_type' => null, 'discount_value' => null]);
        $policy = $this->makePolicy(false);

        $result = $this->calculator->calculateItemRefund($item, 2, $policy);

        $this->assertSame(150.0, $result);
    }

    /**
     * Validates: Requirements 4.4
     * calculateItemRefundWithDistribution also respects discount_deduction_enabled = false.
     */
    public function test_discount_deduction_disabled_in_distribution_method(): void
    {
        $itemA = $this->makeItem(['id' => 1, 'unit_price_snapshot' => 100.0, 'qty' => 2, 'discount_type' => 'fixed', 'discount_value' => 40.0]);
        $itemB = $this->makeItem(['id' => 2, 'unit_price_snapshot' => 50.0,  'qty' => 4, 'discount_type' => 'fixed', 'discount_value' => 40.0]);

        $policy   = $this->makePolicy(false);
        $allItems = new Collection([$itemA, $itemB]);

        // discount_deduction_enabled = false → full price: 100 × 2 = 200
        $result = $this->calculator->calculateItemRefundWithDistribution($itemA, 2, $policy, $allItems);

        $this->assertSame(200.0, $result);
    }

    // =========================================================================
    // Requirement 4.3 — discount_deduction_enabled = true applies discount
    // =========================================================================

    /**
     * Validates: Requirements 4.3
     * When discount_deduction_enabled = true and no discount on item, use full price.
     */
    public function test_discount_deduction_enabled_no_discount_uses_full_price(): void
    {
        $item   = $this->makeItem(['unit_price_snapshot' => 80.0, 'qty' => 5, 'discount_type' => null, 'discount_value' => null]);
        $policy = $this->makePolicy(true);

        $result = $this->calculator->calculateItemRefund($item, 5, $policy);

        $this->assertSame(400.0, $result);
    }

    // =========================================================================
    // Rounding — Requirements 4.1, 4.2
    // =========================================================================

    /**
     * Validates: Requirements 4.1
     * Rounding uses PHP_ROUND_HALF_UP to 4 decimal places.
     */
    public function test_rounding_half_up_on_refund_amount(): void
    {
        // unit_price=1, discount=3% → net=0.97 → refund=0.97×3=2.91
        $item   = $this->makeItem(['unit_price_snapshot' => 1.0, 'qty' => 3, 'discount_type' => 'percent', 'discount_value' => 3.0]);
        $policy = $this->makePolicy(true);

        $result = $this->calculator->calculateItemRefund($item, 3, $policy);

        $expected = round(round(1.0 * (1 - 3.0 / 100), 4, PHP_ROUND_HALF_UP) * 3, 4, PHP_ROUND_HALF_UP);
        $this->assertSame($expected, $result);
    }

    /**
     * Validates: Requirements 4.2
     * distributeFixedDiscount returns array keyed by item ID.
     */
    public function test_distribute_fixed_discount_returns_keyed_by_item_id(): void
    {
        $itemA = $this->makeItem(['id' => 42, 'unit_price_snapshot' => 100.0, 'qty' => 1]);
        $itemB = $this->makeItem(['id' => 99, 'unit_price_snapshot' => 100.0, 'qty' => 1]);

        $items  = new Collection([$itemA, $itemB]);
        $result = $this->calculator->distributeFixedDiscount($items, 20.0);

        $this->assertArrayHasKey(42, $result);
        $this->assertArrayHasKey(99, $result);
    }

    /**
     * Validates: Requirements 4.2
     * When invoice total is zero, distributeFixedDiscount returns full unit price (no division by zero).
     */
    public function test_distribute_fixed_discount_zero_invoice_total_returns_full_price(): void
    {
        $item  = $this->makeItem(['id' => 1, 'unit_price_snapshot' => 0.0, 'qty' => 5]);
        $items = new Collection([$item]);

        $result = $this->calculator->distributeFixedDiscount($items, 50.0);

        $this->assertSame(0.0, $result[1]);
    }
}
