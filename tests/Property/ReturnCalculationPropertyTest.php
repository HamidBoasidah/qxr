<?php

namespace Tests\Property;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnInvoice;
use App\Models\ReturnInvoiceItem;
use App\Models\ReturnPolicy;
use App\Models\User;
use App\Services\ReturnInvoiceService;
use App\Services\ReturnRefundCalculator;
use App\Services\ReturnRequestValidator;
use App\Repositories\ReturnInvoiceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Tests: Return Refund Calculations
 *
 * Property 9:  percent discount refund calculation
 * Property 10: fixed discount proportional distribution
 * Property 11: discount_deduction_enabled = false uses full price
 * Property 12: total refund equals sum of items
 * Property 13: expiry snapshot copied correctly
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
 */
class ReturnCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ReturnRefundCalculator $calculator;
    private ReturnInvoiceService   $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ReturnRefundCalculator();
        $this->service    = new ReturnInvoiceService(
            new ReturnRequestValidator(),
            $this->calculator,
            new ReturnInvoiceRepository(new \App\Models\ReturnInvoice()),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompany(): User
    {
        return User::factory()->create(['user_type' => 'company', 'is_active' => true]);
    }

    private function makePolicy(User $company, array $overrides = []): ReturnPolicy
    {
        return ReturnPolicy::create(array_merge([
            'company_id'                 => $company->id,
            'name'                       => 'Test Policy',
            'return_window_days'         => 30,
            'max_return_ratio'           => 1.0,
            'bonus_return_enabled'       => false,
            'bonus_return_ratio'         => null,
            'discount_deduction_enabled' => true,
            'min_days_before_expiry'     => 0,
            'is_default'                 => true,
            'is_active'                  => true,
        ], $overrides));
    }

    private function makeInvoice(User $company, ReturnPolicy $policy): Invoice
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);
        $order    = Order::create([
            'order_no'         => 'ORD-' . strtoupper(Str::random(8)),
            'company_user_id'  => $company->id,
            'customer_user_id' => $customer->id,
            'status'           => 'delivered',
            'submitted_at'     => now(),
        ]);

        return Invoice::create([
            'invoice_no'              => 'INV-' . strtoupper(Str::random(8)),
            'order_id'                => $order->id,
            'subtotal_snapshot'       => 1000.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 1000.00,
            'issued_at'               => now()->subDays(2),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ]);
    }

    private function makeInvoiceItem(Invoice $invoice, array $overrides = []): InvoiceItem
    {
        $product = Product::factory()->create([
            'company_user_id' => $invoice->order->company_user_id,
        ]);

        return InvoiceItem::create(array_merge([
            'invoice_id'           => $invoice->id,
            'product_id'           => $product->id,
            'description_snapshot' => 'Product',
            'qty'                  => 5,
            'unit_price_snapshot'  => 100.00,
            'line_total_snapshot'  => 500.00,
            'expiry_date'          => now()->addYear()->toDateString(),
            'discount_type'        => null,
            'discount_value'       => null,
            'is_bonus'             => false,
        ], $overrides));
    }

    // =========================================================================
    // Property 9: percent discount refund calculation
    // =========================================================================

    /**
     * Property 9: For any item with discount_type='percent' and discount_deduction_enabled=true,
     * refund_amount = unit_price × (1 - discount_value/100) × returned_qty
     *
     * Validates: Requirements 4.1, 4.3
     */
    #[Test]
    public function property9_percent_discount_refund_formula_holds(): void
    {
        // Feature: return-policy-invoice-system, Property 9: percent discount refund calculation

        for ($i = 0; $i < 100; $i++) {
            $unitPrice     = fake()->randomFloat(4, 1.0, 500.0);
            $discountValue = fake()->randomFloat(4, 0.01, 99.99);
            $qty           = fake()->numberBetween(1, 10);
            $returnedQty   = fake()->numberBetween(1, $qty);

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['discount_deduction_enabled' => true]);
            $invoice = $this->makeInvoice($company, $policy);

            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => $unitPrice,
                'discount_type'       => 'percent',
                'discount_value'      => $discountValue,
            ]);

            $refund = $this->calculator->calculateItemRefund($item, $returnedQty, $policy);

            // Expected: unit_price × (1 - discount_value/100) × returned_qty
            $netUnitPrice    = $unitPrice * (1 - $discountValue / 100);
            $netUnitPrice    = round($netUnitPrice, 4, PHP_ROUND_HALF_UP);
            $expectedRefund  = round($netUnitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);

            $this->assertEquals(
                $expectedRefund,
                $refund,
                "Iteration {$i}: percent discount formula failed. " .
                "unit_price={$unitPrice}, discount={$discountValue}%, returned_qty={$returnedQty}"
            );
        }
    }

    /**
     * Property 9: Refund with percent discount is always less than full-price refund.
     *
     * Validates: Requirements 4.1, 4.3
     */
    #[Test]
    public function property9_percent_discount_refund_is_less_than_full_price(): void
    {
        // Feature: return-policy-invoice-system, Property 9: percent discount refund calculation

        for ($i = 0; $i < 100; $i++) {
            $unitPrice     = fake()->randomFloat(4, 1.0, 500.0);
            $discountValue = fake()->randomFloat(4, 0.01, 99.99); // strictly positive discount
            $qty           = fake()->numberBetween(1, 10);
            $returnedQty   = fake()->numberBetween(1, $qty);

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['discount_deduction_enabled' => true]);
            $invoice = $this->makeInvoice($company, $policy);

            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => $unitPrice,
                'discount_type'       => 'percent',
                'discount_value'      => $discountValue,
            ]);

            $discountedRefund = $this->calculator->calculateItemRefund($item, $returnedQty, $policy);
            $fullPriceRefund  = round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);

            $this->assertLessThan(
                $fullPriceRefund,
                $discountedRefund,
                "Iteration {$i}: discounted refund must be less than full-price refund"
            );
        }
    }

    // =========================================================================
    // Property 10: fixed discount proportional distribution
    // =========================================================================

    /**
     * Property 10: For any invoice with fixed discount, the sum of all proportionally
     * distributed discounts equals the total fixed discount (within rounding tolerance).
     *
     * Validates: Requirement 4.2
     */
    #[Test]
    public function property10_sum_of_distributed_discounts_equals_total_fixed_discount(): void
    {
        // Feature: return-policy-invoice-system, Property 10: fixed discount proportional distribution

        for ($i = 0; $i < 100; $i++) {
            $company     = $this->makeCompany();
            $policy      = $this->makePolicy($company);
            $invoice     = $this->makeInvoice($company, $policy);
            $itemCount   = fake()->numberBetween(2, 6);
            $fixedDiscount = fake()->randomFloat(4, 1.0, 200.0);

            $items = new Collection();
            for ($j = 0; $j < $itemCount; $j++) {
                $items->push($this->makeInvoiceItem($invoice, [
                    'qty'                 => fake()->numberBetween(1, 10),
                    'unit_price_snapshot' => fake()->randomFloat(4, 5.0, 200.0),
                    'discount_type'       => 'fixed',
                    'discount_value'      => $fixedDiscount,
                ]));
            }

            $distributedPrices = $this->calculator->distributeFixedDiscount($items, $fixedDiscount);

            // Reconstruct the total distributed discount from net prices
            $totalDistributedDiscount = 0.0;
            foreach ($items as $item) {
                $itemId    = $item->getKey();
                $itemTotal = (float) $item->unit_price_snapshot * (int) $item->qty;
                $netTotal  = $distributedPrices[$itemId] * (int) $item->qty;
                $totalDistributedDiscount += ($itemTotal - $netTotal);
            }

            // Allow rounding tolerance: each item can accumulate up to 0.0001 per unit
            // With qty up to 10 and 6 items, max accumulated error ≈ itemCount × qty × 0.0001
            $tolerance = $itemCount * 0.001;

            $this->assertEqualsWithDelta(
                $fixedDiscount,
                $totalDistributedDiscount,
                $tolerance,
                "Iteration {$i}: sum of distributed discounts ({$totalDistributedDiscount}) " .
                "should equal fixed discount ({$fixedDiscount})"
            );
        }
    }

    /**
     * Property 10: Each item's proportional discount is proportional to its value share.
     *
     * Validates: Requirement 4.2
     */
    #[Test]
    public function property10_proportional_discount_matches_value_share(): void
    {
        // Feature: return-policy-invoice-system, Property 10: fixed discount proportional distribution

        for ($i = 0; $i < 100; $i++) {
            $company       = $this->makeCompany();
            $policy        = $this->makePolicy($company);
            $invoice       = $this->makeInvoice($company, $policy);
            $fixedDiscount = fake()->randomFloat(4, 1.0, 100.0);

            $item1 = $this->makeInvoiceItem($invoice, [
                'qty'                 => fake()->numberBetween(1, 5),
                'unit_price_snapshot' => fake()->randomFloat(4, 10.0, 100.0),
                'discount_type'       => 'fixed',
                'discount_value'      => $fixedDiscount,
            ]);
            $item2 = $this->makeInvoiceItem($invoice, [
                'qty'                 => fake()->numberBetween(1, 5),
                'unit_price_snapshot' => fake()->randomFloat(4, 10.0, 100.0),
                'discount_type'       => 'fixed',
                'discount_value'      => $fixedDiscount,
            ]);

            $items = new Collection([$item1, $item2]);

            $total1 = (float) $item1->unit_price_snapshot * (int) $item1->qty;
            $total2 = (float) $item2->unit_price_snapshot * (int) $item2->qty;
            $invoiceTotal = $total1 + $total2;

            $distributedPrices = $this->calculator->distributeFixedDiscount($items, $fixedDiscount);

            $expectedDiscount1 = round($fixedDiscount * ($total1 / $invoiceTotal), 4, PHP_ROUND_HALF_UP);
            $expectedDiscount2 = round($fixedDiscount * ($total2 / $invoiceTotal), 4, PHP_ROUND_HALF_UP);

            $actualNet1 = $distributedPrices[$item1->getKey()];
            $actualNet2 = $distributedPrices[$item2->getKey()];

            $actualDiscount1 = round($total1 - ($actualNet1 * (int) $item1->qty), 4, PHP_ROUND_HALF_UP);
            $actualDiscount2 = round($total2 - ($actualNet2 * (int) $item2->qty), 4, PHP_ROUND_HALF_UP);

            $this->assertEqualsWithDelta(
                $expectedDiscount1,
                $actualDiscount1,
                0.001,
                "Iteration {$i}: item1 proportional discount mismatch"
            );
            $this->assertEqualsWithDelta(
                $expectedDiscount2,
                $actualDiscount2,
                0.001,
                "Iteration {$i}: item2 proportional discount mismatch"
            );
        }
    }

    // =========================================================================
    // Property 11: discount_deduction_enabled = false uses full price
    // =========================================================================

    /**
     * Property 11: When discount_deduction_enabled=false, refund = unit_price × returned_qty
     * regardless of any discount fields on the item.
     *
     * Validates: Requirement 4.4
     */
    #[Test]
    public function property11_full_price_used_when_discount_deduction_disabled(): void
    {
        // Feature: return-policy-invoice-system, Property 11: discount_deduction_enabled = false uses full price

        $discountTypes = ['percent', 'fixed', null];

        for ($i = 0; $i < 100; $i++) {
            $unitPrice   = fake()->randomFloat(4, 1.0, 500.0);
            $qty         = fake()->numberBetween(1, 10);
            $returnedQty = fake()->numberBetween(1, $qty);
            $discountType = $discountTypes[array_rand($discountTypes)];

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['discount_deduction_enabled' => false]);
            $invoice = $this->makeInvoice($company, $policy);

            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => $unitPrice,
                'discount_type'       => $discountType,
                'discount_value'      => $discountType ? fake()->randomFloat(4, 1.0, 50.0) : null,
            ]);

            $refund         = $this->calculator->calculateItemRefund($item, $returnedQty, $policy);
            $expectedRefund = round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);

            $this->assertEquals(
                $expectedRefund,
                $refund,
                "Iteration {$i}: with discount_deduction_enabled=false, " .
                "refund must equal unit_price × returned_qty regardless of discount_type={$discountType}"
            );
        }
    }

    /**
     * Property 11: With discount_deduction_enabled=false, refund equals full price
     * even when a large percent discount exists.
     *
     * Validates: Requirement 4.4
     */
    #[Test]
    public function property11_full_price_refund_ignores_large_percent_discount(): void
    {
        // Feature: return-policy-invoice-system, Property 11: discount_deduction_enabled = false uses full price

        for ($i = 0; $i < 100; $i++) {
            $unitPrice   = fake()->randomFloat(4, 10.0, 500.0);
            $qty         = fake()->numberBetween(1, 10);
            $returnedQty = fake()->numberBetween(1, $qty);
            $bigDiscount = fake()->randomFloat(4, 50.0, 99.99); // large discount

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['discount_deduction_enabled' => false]);
            $invoice = $this->makeInvoice($company, $policy);

            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => $unitPrice,
                'discount_type'       => 'percent',
                'discount_value'      => $bigDiscount,
            ]);

            $refund         = $this->calculator->calculateItemRefund($item, $returnedQty, $policy);
            $expectedRefund = round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);

            $this->assertEquals(
                $expectedRefund,
                $refund,
                "Iteration {$i}: full price must be used even with {$bigDiscount}% discount when deduction disabled"
            );
        }
    }

    // =========================================================================
    // Property 12: total refund equals sum of items
    // =========================================================================

    /**
     * Property 12: For any created return invoice, total_refund_amount = Σ refund_amount
     * across all return_invoice_items.
     *
     * Validates: Requirements 4.5, 4.6
     */
    #[Test]
    public function property12_total_refund_equals_sum_of_item_refunds(): void
    {
        // Feature: return-policy-invoice-system, Property 12: total refund equals sum of items

        for ($i = 0; $i < 100; $i++) {
            $company   = $this->makeCompany();
            $policy    = $this->makePolicy($company, ['discount_deduction_enabled' => false]);
            $invoice   = $this->makeInvoice($company, $policy);
            $itemCount = fake()->numberBetween(1, 5);

            $invoiceItems = [];
            $requestItems = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $qty  = fake()->numberBetween(2, 10);
                $item = $this->makeInvoiceItem($invoice, [
                    'qty'                 => $qty,
                    'unit_price_snapshot' => fake()->randomFloat(4, 5.0, 200.0),
                ]);
                $returnedQty    = fake()->numberBetween(1, $qty);
                $invoiceItems[] = $item;
                $requestItems[] = ['invoice_item_id' => $item->id, 'quantity' => $returnedQty];
            }

            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $returnInvoice->load('items');

            $sumOfItems = $returnInvoice->items->sum(fn($item) => (float) $item->refund_amount);
            $sumOfItems = round($sumOfItems, 4, PHP_ROUND_HALF_UP);

            $this->assertEqualsWithDelta(
                $sumOfItems,
                (float) $returnInvoice->total_refund_amount,
                0.0001,
                "Iteration {$i}: total_refund_amount must equal sum of item refund_amounts"
            );
        }
    }

    /**
     * Property 12: total_refund_amount is always non-negative.
     *
     * Validates: Requirements 4.5, 4.6
     */
    #[Test]
    public function property12_total_refund_amount_is_non_negative(): void
    {
        // Feature: return-policy-invoice-system, Property 12: total refund equals sum of items

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);

            $qty  = fake()->numberBetween(2, 10);
            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => fake()->randomFloat(4, 1.0, 200.0),
                'discount_type'       => 'percent',
                'discount_value'      => fake()->randomFloat(4, 0.01, 99.99),
            ]);

            $returnedQty   = fake()->numberBetween(1, $qty);
            $requestItems  = [['invoice_item_id' => $item->id, 'quantity' => $returnedQty]];
            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);

            $this->assertGreaterThanOrEqual(
                0,
                (float) $returnInvoice->total_refund_amount,
                "Iteration {$i}: total_refund_amount must be non-negative"
            );
        }
    }

    // =========================================================================
    // Property 13: expiry snapshot copied correctly
    // =========================================================================

    /**
     * Property 13: For any return invoice item, expiry_date_snapshot must equal
     * the expiry_date from the original invoice_items record.
     *
     * Validates: Requirement 4.7
     */
    #[Test]
    public function property13_expiry_date_snapshot_matches_original_invoice_item(): void
    {
        // Feature: return-policy-invoice-system, Property 13: expiry snapshot copied correctly

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['discount_deduction_enabled' => false]);
            $invoice = $this->makeInvoice($company, $policy);

            // Random expiry date (some null, some set)
            $hasExpiry  = fake()->boolean(70); // 70% chance of having expiry
            $expiryDate = $hasExpiry
                ? fake()->dateTimeBetween('+1 month', '+3 years')->format('Y-m-d')
                : null;

            $qty  = fake()->numberBetween(2, 10);
            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => fake()->randomFloat(4, 5.0, 100.0),
                'expiry_date'         => $expiryDate,
            ]);

            $returnedQty   = fake()->numberBetween(1, $qty);
            $requestItems  = [['invoice_item_id' => $item->id, 'quantity' => $returnedQty]];
            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $returnInvoice->load('items');

            $returnItem = $returnInvoice->items->first();

            $expectedSnapshot = $expiryDate;
            $actualSnapshot   = $returnItem->expiry_date_snapshot
                ? $returnItem->expiry_date_snapshot->format('Y-m-d')
                : null;

            $this->assertEquals(
                $expectedSnapshot,
                $actualSnapshot,
                "Iteration {$i}: expiry_date_snapshot must match original invoice_item.expiry_date. " .
                "Expected: {$expectedSnapshot}, Got: {$actualSnapshot}"
            );
        }
    }

    /**
     * Property 13: expiry_date_snapshot is immutable — changing the original item's
     * expiry_date after return invoice creation does not affect the snapshot.
     *
     * Validates: Requirement 4.7
     */
    #[Test]
    public function property13_expiry_snapshot_is_immutable_after_creation(): void
    {
        // Feature: return-policy-invoice-system, Property 13: expiry snapshot copied correctly

        for ($i = 0; $i < 100; $i++) {
            $company    = $this->makeCompany();
            $policy     = $this->makePolicy($company, ['discount_deduction_enabled' => false]);
            $invoice    = $this->makeInvoice($company, $policy);
            $expiryDate = fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d');

            $qty  = fake()->numberBetween(2, 10);
            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => $qty,
                'unit_price_snapshot' => 50.0,
                'expiry_date'         => $expiryDate,
            ]);

            $requestItems  = [['invoice_item_id' => $item->id, 'quantity' => 1]];
            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $returnInvoice->load('items');

            $snapshotAtCreation = $returnInvoice->items->first()->expiry_date_snapshot?->format('Y-m-d');

            // Simulate changing the original item's expiry_date after the return was created
            $newExpiryDate = fake()->dateTimeBetween('+3 years', '+5 years')->format('Y-m-d');
            $item->update(['expiry_date' => $newExpiryDate]);

            // Reload the return invoice item — snapshot must remain unchanged
            $returnInvoice->load('items');
            $snapshotAfterChange = $returnInvoice->items->first()->expiry_date_snapshot?->format('Y-m-d');

            $this->assertEquals(
                $snapshotAtCreation,
                $snapshotAfterChange,
                "Iteration {$i}: expiry_date_snapshot must not change after original item is updated"
            );

            $this->assertEquals(
                $expiryDate,
                $snapshotAfterChange,
                "Iteration {$i}: snapshot must still equal the original expiry_date at creation time"
            );
        }
    }
}
