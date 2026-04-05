<?php

namespace Tests\Unit;

use App\Exceptions\ReturnInvoice\BonusReturnExceededException;
use App\Exceptions\ReturnInvoice\DuplicateReturnException;
use App\Exceptions\ReturnInvoice\ExpiryTooCloseException;
use App\Exceptions\ReturnInvoice\InvoiceNotPaidException;
use App\Exceptions\ReturnInvoice\QuantityExceededException;
use App\Exceptions\ReturnInvoice\ReturnRatioExceededException;
use App\Exceptions\ReturnInvoice\ReturnWindowExpiredException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnInvoice;
use App\Models\ReturnPolicy;
use App\Models\User;
use App\Services\ReturnRequestValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Unit tests for ReturnRequestValidator.
 *
 * Validates: Requirements 1.7, 3.1–3.15
 *
 * Uses in-memory model instances (no database interaction) for most tests.
 * Tests that require database interaction (assertNoDuplicateReturn) use RefreshDatabase.
 */
class ReturnRequestValidatorTest extends TestCase
{
    use RefreshDatabase;

    private ReturnRequestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ReturnRequestValidator();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeInvoice(array $attrs = []): Invoice
    {
        $invoice = (new Invoice())->forceFill([
            'id'         => $attrs['id']         ?? 1,
            'status'     => $attrs['status']     ?? 'paid',
            'issued_at'  => $attrs['issued_at']  ?? Carbon::now()->subDays(5),
            'company_id' => $attrs['company_id'] ?? 1,
        ]);

        if (isset($attrs['items'])) {
            $invoice->setRelation('items', Collection::make($attrs['items']));
        }

        return $invoice;
    }

    private function makeItem(array $attrs = []): InvoiceItem
    {
        return (new InvoiceItem())->forceFill([
            'id'          => $attrs['id']          ?? 1,
            'invoice_id'  => $attrs['invoice_id']  ?? 1,
            'qty'         => $attrs['qty']          ?? 10,
            'is_bonus'    => $attrs['is_bonus']     ?? false,
            'expiry_date' => $attrs['expiry_date']  ?? null,
        ]);
    }

    private function makePolicy(array $attrs = []): ReturnPolicy
    {
        return (new ReturnPolicy())->forceFill([
            'id'                     => $attrs['id']                     ?? 1,
            'return_window_days'     => $attrs['return_window_days']     ?? 30,
            'max_return_ratio'       => $attrs['max_return_ratio']       ?? 1.0,
            'bonus_return_enabled'   => $attrs['bonus_return_enabled']   ?? false,
            'bonus_return_ratio'     => $attrs['bonus_return_ratio']     ?? 0.5,
            'min_days_before_expiry' => $attrs['min_days_before_expiry'] ?? 0,
        ]);
    }

    // =========================================================================
    // assertInvoicePaid — Requirement 3.3, 3.4
    // =========================================================================

    /**
     * Validates: Requirements 3.3, 3.4
     * An invoice with status != 'paid' must throw InvoiceNotPaidException.
     */
    public function test_assert_invoice_paid_throws_when_status_is_draft(): void
    {
        $invoice = $this->makeInvoice(['status' => 'draft']);

        $this->expectException(InvoiceNotPaidException::class);

        $this->validator->assertInvoicePaid($invoice);
    }

    /**
     * Validates: Requirements 3.3, 3.4
     * An invoice with status 'cancelled' must throw InvoiceNotPaidException.
     */
    public function test_assert_invoice_paid_throws_when_status_is_cancelled(): void
    {
        $invoice = $this->makeInvoice(['status' => 'cancelled']);

        $this->expectException(InvoiceNotPaidException::class);

        $this->validator->assertInvoicePaid($invoice);
    }

    /**
     * Validates: Requirements 3.3
     * A paid invoice must not throw any exception.
     */
    public function test_assert_invoice_paid_passes_when_status_is_paid(): void
    {
        $invoice = $this->makeInvoice(['status' => 'paid']);

        $this->validator->assertInvoicePaid($invoice);

        $this->assertTrue(true); // no exception thrown
    }

    // =========================================================================
    // assertNoDuplicateReturn — Requirements 3.1, 3.2
    // =========================================================================

    /**
     * Validates: Requirements 3.1, 3.2
     * If a return invoice already exists for the original invoice, throw DuplicateReturnException.
     */
    public function test_assert_no_duplicate_return_throws_when_return_exists(): void
    {
        // Create a real return invoice in the database
        $company  = User::factory()->create(['user_type' => 'company', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $policy = ReturnPolicy::create([
            'company_id'                 => $company->id,
            'name'                       => 'Test Policy',
            'return_window_days'         => 30,
            'max_return_ratio'           => 1.0,
            'bonus_return_enabled'       => false,
            'discount_deduction_enabled' => false,
            'min_days_before_expiry'     => 0,
            'is_default'                 => true,
            'is_active'                  => true,
        ]);

        $order = Order::create([
            'order_no'         => 'ORD-' . strtoupper(Str::random(8)),
            'company_user_id'  => $company->id,
            'customer_user_id' => $customer->id,
            'status'           => 'delivered',
            'submitted_at'     => now(),
        ]);

        $invoice = Invoice::create([
            'invoice_no'              => 'INV-' . strtoupper(Str::random(8)),
            'order_id'                => $order->id,
            'subtotal_snapshot'       => 100.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 100.00,
            'issued_at'               => now(),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ]);

        // Create a return invoice for this invoice
        ReturnInvoice::create([
            'original_invoice_id' => $invoice->id,
            'company_id'          => $company->id,
            'return_policy_id'    => $policy->id,
            'total_refund_amount' => 100.00,
            'status'              => 'pending',
        ]);

        $this->expectException(DuplicateReturnException::class);

        $this->validator->assertNoDuplicateReturn($invoice->id);
    }

    /**
     * Validates: Requirements 3.1
     * If no return invoice exists, no exception should be thrown.
     */
    public function test_assert_no_duplicate_return_passes_when_no_return_exists(): void
    {
        // Use an invoice ID that has no return invoice
        $this->validator->assertNoDuplicateReturn(99999);

        $this->assertTrue(true); // no exception thrown
    }

    // =========================================================================
    // assertWithinReturnWindow — Requirements 3.6, 3.7
    // =========================================================================

    /**
     * Validates: Requirements 3.6, 3.7
     * If elapsed days > return_window_days, throw ReturnWindowExpiredException.
     */
    public function test_assert_within_return_window_throws_when_window_expired(): void
    {
        $invoice = $this->makeInvoice(['issued_at' => Carbon::now()->subDays(31)]);
        $policy  = $this->makePolicy(['return_window_days' => 30]);

        $this->expectException(ReturnWindowExpiredException::class);

        $this->validator->assertWithinReturnWindow($invoice, $policy);
    }

    /**
     * Validates: Requirements 3.6
     * An invoice issued 1 day ago with a 30-day window must pass.
     */
    public function test_assert_within_return_window_passes_when_one_day_elapsed(): void
    {
        $invoice = $this->makeInvoice(['issued_at' => Carbon::now()->subDay()]);
        $policy  = $this->makePolicy(['return_window_days' => 30]);

        $this->validator->assertWithinReturnWindow($invoice, $policy);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.6
     * If elapsed days < return_window_days, the request should pass.
     */
    public function test_assert_within_return_window_passes_when_within_window(): void
    {
        $invoice = $this->makeInvoice(['issued_at' => Carbon::now()->subDays(5)]);
        $policy  = $this->makePolicy(['return_window_days' => 30]);

        $this->validator->assertWithinReturnWindow($invoice, $policy);

        $this->assertTrue(true);
    }

    // =========================================================================
    // assertItemQuantities — Requirements 3.10, 3.11
    // =========================================================================

    /**
     * Validates: Requirements 3.10, 3.11
     * Returned quantity exceeding original quantity must throw QuantityExceededException.
     */
    public function test_assert_item_quantities_throws_when_returned_exceeds_original(): void
    {
        $item    = $this->makeItem(['id' => 1, 'qty' => 5]);
        $invoice = $this->makeInvoice(['items' => [$item]]);

        $items = [['invoice_item_id' => 1, 'quantity' => 6]];

        $this->expectException(QuantityExceededException::class);

        $this->validator->assertItemQuantities($items, $invoice);
    }

    /**
     * Validates: Requirements 3.10, 3.11
     * Returned quantity equal to original quantity must pass.
     */
    public function test_assert_item_quantities_passes_when_returned_equals_original(): void
    {
        $item    = $this->makeItem(['id' => 1, 'qty' => 5]);
        $invoice = $this->makeInvoice(['items' => [$item]]);

        $items = [['invoice_item_id' => 1, 'quantity' => 5]];

        $this->validator->assertItemQuantities($items, $invoice);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.10, 3.11
     * Returned quantity less than original quantity must pass.
     */
    public function test_assert_item_quantities_passes_when_returned_less_than_original(): void
    {
        $item    = $this->makeItem(['id' => 1, 'qty' => 10]);
        $invoice = $this->makeInvoice(['items' => [$item]]);

        $items = [['invoice_item_id' => 1, 'quantity' => 3]];

        $this->validator->assertItemQuantities($items, $invoice);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.10, 3.11
     * An invoice_item_id not found in the invoice must throw QuantityExceededException.
     */
    public function test_assert_item_quantities_throws_when_item_not_in_invoice(): void
    {
        $item    = $this->makeItem(['id' => 1, 'qty' => 5]);
        $invoice = $this->makeInvoice(['items' => [$item]]);

        $items = [['invoice_item_id' => 999, 'quantity' => 1]];

        $this->expectException(QuantityExceededException::class);

        $this->validator->assertItemQuantities($items, $invoice);
    }

    // =========================================================================
    // assertReturnRatio — Requirements 3.12, 3.13, 6.4
    // =========================================================================

    /**
     * Validates: Requirements 3.12, 3.13
     * Return ratio exceeding max_return_ratio must throw ReturnRatioExceededException.
     */
    public function test_assert_return_ratio_throws_when_ratio_exceeded(): void
    {
        $item1   = $this->makeItem(['id' => 1, 'qty' => 5]);
        $item2   = $this->makeItem(['id' => 2, 'qty' => 5]);
        $invoice = $this->makeInvoice(['items' => [$item1, $item2]]);
        $policy  = $this->makePolicy(['max_return_ratio' => 0.5]);

        // total_original = 10, returned = 6 → ratio = 0.6 > 0.5
        $items = [
            ['invoice_item_id' => 1, 'quantity' => 3],
            ['invoice_item_id' => 2, 'quantity' => 3],
        ];

        $this->expectException(ReturnRatioExceededException::class);

        $this->validator->assertReturnRatio($items, $invoice, $policy);
    }

    /**
     * Validates: Requirements 3.12, 6.4
     * Return ratio equal to max_return_ratio must pass (boundary).
     */
    public function test_assert_return_ratio_passes_on_exact_boundary(): void
    {
        $item1   = $this->makeItem(['id' => 1, 'qty' => 5]);
        $item2   = $this->makeItem(['id' => 2, 'qty' => 5]);
        $invoice = $this->makeInvoice(['items' => [$item1, $item2]]);
        $policy  = $this->makePolicy(['max_return_ratio' => 0.5]);

        // total_original = 10, returned = 5 → ratio = 0.5 == 0.5
        $items = [
            ['invoice_item_id' => 1, 'quantity' => 3],
            ['invoice_item_id' => 2, 'quantity' => 2],
        ];

        $this->validator->assertReturnRatio($items, $invoice, $policy);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.12
     * Return ratio below max_return_ratio must pass.
     */
    public function test_assert_return_ratio_passes_when_below_max(): void
    {
        $item    = $this->makeItem(['id' => 1, 'qty' => 10]);
        $invoice = $this->makeInvoice(['items' => [$item]]);
        $policy  = $this->makePolicy(['max_return_ratio' => 1.0]);

        $items = [['invoice_item_id' => 1, 'quantity' => 5]];

        $this->validator->assertReturnRatio($items, $invoice, $policy);

        $this->assertTrue(true);
    }

    // =========================================================================
    // assertExpiryDates — Requirements 1.7, 3.8, 3.9
    // =========================================================================

    /**
     * Validates: Requirements 1.7
     * When min_days_before_expiry = 0, expiry check is disabled entirely — no exception thrown
     * even if expiry_date is tomorrow.
     */
    public function test_assert_expiry_dates_skips_check_when_min_days_is_zero(): void
    {
        $policy = $this->makePolicy(['min_days_before_expiry' => 0]);

        // expiry_date is tomorrow — would normally fail if check were active
        $items = [['expiry_date' => Carbon::tomorrow()->toDateString()]];

        $this->validator->assertExpiryDates($items, $policy);

        $this->assertTrue(true); // no exception thrown
    }

    /**
     * Validates: Requirements 1.7
     * When min_days_before_expiry = 0, even an already-expired item passes.
     */
    public function test_assert_expiry_dates_skips_check_for_expired_item_when_min_days_is_zero(): void
    {
        $policy = $this->makePolicy(['min_days_before_expiry' => 0]);

        $items = [['expiry_date' => Carbon::yesterday()->toDateString()]];

        $this->validator->assertExpiryDates($items, $policy);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.8, 3.9
     * When days_to_expiry < min_days_before_expiry, throw ExpiryTooCloseException.
     */
    public function test_assert_expiry_dates_throws_when_expiry_too_close(): void
    {
        $policy = $this->makePolicy(['min_days_before_expiry' => 30]);

        // expiry is 10 days away, but policy requires at least 30
        $items = [['expiry_date' => Carbon::today()->addDays(10)->toDateString()]];

        $this->expectException(ExpiryTooCloseException::class);

        $this->validator->assertExpiryDates($items, $policy);
    }

    /**
     * Validates: Requirements 3.8, 3.9
     * When days_to_expiry >= min_days_before_expiry, no exception is thrown.
     */
    public function test_assert_expiry_dates_passes_when_expiry_far_enough(): void
    {
        $policy = $this->makePolicy(['min_days_before_expiry' => 30]);

        // expiry is 60 days away — well within the allowed range
        $items = [['expiry_date' => Carbon::today()->addDays(60)->toDateString()]];

        $this->validator->assertExpiryDates($items, $policy);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.8
     * Items without an expiry_date are skipped in expiry validation.
     */
    public function test_assert_expiry_dates_skips_items_without_expiry_date(): void
    {
        $policy = $this->makePolicy(['min_days_before_expiry' => 30]);

        $items = [['expiry_date' => null]];

        $this->validator->assertExpiryDates($items, $policy);

        $this->assertTrue(true);
    }

    // =========================================================================
    // assertBonusQuantities — Requirements 3.14, 3.15
    // =========================================================================

    /**
     * Validates: Requirements 3.14, 3.15
     * Returned bonus quantity exceeding allowed ratio must throw BonusReturnExceededException.
     */
    public function test_assert_bonus_quantities_throws_when_bonus_ratio_exceeded(): void
    {
        // original invoice has 10 bonus items
        $bonusItem   = $this->makeItem(['id' => 1, 'qty' => 10, 'is_bonus' => true]);
        $regularItem = $this->makeItem(['id' => 2, 'qty' => 5,  'is_bonus' => false]);
        $invoice     = $this->makeInvoice(['items' => [$bonusItem, $regularItem]]);

        // policy allows returning at most 50% of bonus qty → max 5
        $policy = $this->makePolicy([
            'bonus_return_enabled' => true,
            'bonus_return_ratio'   => 0.5,
        ]);

        // trying to return 6 bonus items (> 5 allowed)
        $items = [
            ['invoice_item_id' => 1, 'quantity' => 6],
            ['invoice_item_id' => 2, 'quantity' => 2],
        ];

        $this->expectException(BonusReturnExceededException::class);

        $this->validator->assertBonusQuantities($items, $invoice, $policy);
    }

    /**
     * Validates: Requirements 3.14
     * Returned bonus quantity equal to allowed ratio must pass (boundary).
     */
    public function test_assert_bonus_quantities_passes_on_exact_boundary(): void
    {
        $bonusItem = $this->makeItem(['id' => 1, 'qty' => 10, 'is_bonus' => true]);
        $invoice   = $this->makeInvoice(['items' => [$bonusItem]]);

        $policy = $this->makePolicy([
            'bonus_return_enabled' => true,
            'bonus_return_ratio'   => 0.5,
        ]);

        // returning exactly 5 (= 0.5 × 10)
        $items = [['invoice_item_id' => 1, 'quantity' => 5]];

        $this->validator->assertBonusQuantities($items, $invoice, $policy);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.14
     * When bonus_return_enabled = false, bonus quantity check is skipped entirely.
     */
    public function test_assert_bonus_quantities_skips_check_when_bonus_disabled(): void
    {
        $bonusItem = $this->makeItem(['id' => 1, 'qty' => 10, 'is_bonus' => true]);
        $invoice   = $this->makeInvoice(['items' => [$bonusItem]]);

        $policy = $this->makePolicy([
            'bonus_return_enabled' => false,
            'bonus_return_ratio'   => 0.1,
        ]);

        // returning all 10 bonus items — would fail if check were active
        $items = [['invoice_item_id' => 1, 'quantity' => 10]];

        $this->validator->assertBonusQuantities($items, $invoice, $policy);

        $this->assertTrue(true);
    }

    /**
     * Validates: Requirements 3.14
     * When there are no bonus items in the invoice, bonus check is skipped.
     */
    public function test_assert_bonus_quantities_skips_when_no_bonus_items_in_invoice(): void
    {
        $regularItem = $this->makeItem(['id' => 1, 'qty' => 10, 'is_bonus' => false]);
        $invoice     = $this->makeInvoice(['items' => [$regularItem]]);

        $policy = $this->makePolicy([
            'bonus_return_enabled' => true,
            'bonus_return_ratio'   => 0.5,
        ]);

        $items = [['invoice_item_id' => 1, 'quantity' => 5]];

        $this->validator->assertBonusQuantities($items, $invoice, $policy);

        $this->assertTrue(true);
    }
}
