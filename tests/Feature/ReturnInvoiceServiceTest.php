<?php

namespace Tests\Feature;

use App\Exceptions\ReturnInvoice\DuplicateReturnException;
use App\Exceptions\ReturnInvoice\InvoiceNotPaidException;
use App\Exceptions\ReturnInvoice\ReturnWindowExpiredException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnInvoice;
use App\Models\ReturnPolicy;
use App\Models\User;
use App\Services\ReturnInvoiceService;
use App\Services\ReturnRefundCalculator;
use App\Services\ReturnRequestValidator;
use App\Repositories\ReturnInvoiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Tests: ReturnInvoiceService
 *
 * **Validates: Requirements 3.1-3.15, 5.1-5.5, 6.1-6.4, 7.4**
 *
 * Covers:
 * - Successful return invoice creation with all items
 * - Rejection of unpaid invoice
 * - Rejection of duplicate return
 * - Rejection when return window has expired
 * - Partial return (only some items from the invoice)
 * - Company data isolation (company cannot access another company's return invoices)
 */
class ReturnInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReturnInvoiceService(
            new ReturnRequestValidator(),
            new ReturnRefundCalculator(),
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
            'name'                       => 'Standard Policy',
            'return_window_days'         => 30,
            'max_return_ratio'           => 1.0,
            'bonus_return_enabled'       => false,
            'bonus_return_ratio'         => null,
            'discount_deduction_enabled' => false,
            'min_days_before_expiry'     => 0,
            'is_default'                 => true,
            'is_active'                  => true,
        ], $overrides));
    }

    private function makeInvoice(User $company, ReturnPolicy $policy, array $overrides = []): Invoice
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $order = Order::create([
            'order_no'         => 'ORD-' . strtoupper(Str::random(8)),
            'company_user_id'  => $company->id,
            'customer_user_id' => $customer->id,
            'status'           => 'delivered',
            'submitted_at'     => now(),
        ]);

        return Invoice::create(array_merge([
            'invoice_no'              => 'INV-' . strtoupper(Str::random(8)),
            'order_id'                => $order->id,
            'subtotal_snapshot'       => 1000.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 1000.00,
            'issued_at'               => now(),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ], $overrides));
    }

    private function makeInvoiceItem(Invoice $invoice, array $overrides = []): InvoiceItem
    {
        $product = Product::factory()->create([
            'company_user_id' => $invoice->order->company_user_id,
        ]);

        return InvoiceItem::create(array_merge([
            'invoice_id'           => $invoice->id,
            'product_id'           => $product->id,
            'description_snapshot' => 'Test Product',
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
    // 1. Successful return invoice creation with all items
    // =========================================================================

    /**
     * Test: validate() + create() successfully creates a return invoice with all items.
     *
     * **Validates: Requirements 3.1, 3.3, 5.1, 5.2, 5.4**
     */
    #[Test]
    public function successful_return_invoice_creation_with_all_items(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy, ['issued_at' => now()->subDays(5)]);

        $item1 = $this->makeInvoiceItem($invoice, ['qty' => 3, 'unit_price_snapshot' => 100.00]);
        $item2 = $this->makeInvoiceItem($invoice, ['qty' => 2, 'unit_price_snapshot' => 200.00]);

        $items = [
            ['invoice_item_id' => $item1->id, 'quantity' => 3],
            ['invoice_item_id' => $item2->id, 'quantity' => 2],
        ];

        // Should not throw
        $this->service->validate($invoice, $items, $policy);

        $returnInvoice = $this->service->create($invoice, $items, $policy);

        // Assert return_invoices record exists
        $this->assertDatabaseHas('return_invoices', [
            'original_invoice_id' => $invoice->id,
            'company_id'          => $company->id,
            'return_policy_id'    => $policy->id,
            'status'              => 'pending',
        ]);

        // Assert return_invoice_items records exist
        $this->assertDatabaseHas('return_invoice_items', [
            'return_invoice_id' => $returnInvoice->id,
            'original_item_id'  => $item1->id,
            'returned_quantity' => 3,
        ]);

        $this->assertDatabaseHas('return_invoice_items', [
            'return_invoice_id' => $returnInvoice->id,
            'original_item_id'  => $item2->id,
            'returned_quantity' => 2,
        ]);

        // Assert total_refund_amount equals sum of item refunds
        // discount_deduction_enabled = false → refund = unit_price × qty
        $expectedRefund = (100.00 * 3) + (200.00 * 2); // 300 + 400 = 700
        $this->assertEquals($expectedRefund, (float) $returnInvoice->total_refund_amount);
    }

    // =========================================================================
    // 2. Rejection of unpaid invoice
    // =========================================================================

    /**
     * Test: validate() throws InvoiceNotPaidException when invoice is not paid.
     *
     * **Validates: Requirements 3.3, 3.4**
     */
    #[Test]
    public function rejects_unpaid_invoice(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy, ['status' => 'draft']);

        $item  = $this->makeInvoiceItem($invoice);
        $items = [['invoice_item_id' => $item->id, 'quantity' => 1]];

        $this->expectException(InvoiceNotPaidException::class);

        $this->service->validate($invoice, $items, $policy);
    }

    // =========================================================================
    // 3. Rejection of duplicate return
    // =========================================================================

    /**
     * Test: validate() throws DuplicateReturnException when a return already exists.
     *
     * **Validates: Requirements 3.1, 3.2**
     */
    #[Test]
    public function rejects_duplicate_return(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy);

        $item  = $this->makeInvoiceItem($invoice);
        $items = [['invoice_item_id' => $item->id, 'quantity' => 1]];

        // Create the first return invoice
        $this->service->validate($invoice, $items, $policy);
        $this->service->create($invoice, $items, $policy);

        // Attempt a second return for the same invoice
        $this->expectException(DuplicateReturnException::class);

        $this->service->validate($invoice, $items, $policy);
    }

    // =========================================================================
    // 4. Rejection when return window has expired
    // =========================================================================

    /**
     * Test: validate() throws ReturnWindowExpiredException when return window has passed.
     *
     * **Validates: Requirements 3.6, 3.7**
     */
    #[Test]
    public function rejects_when_return_window_has_expired(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company, ['return_window_days' => 7]);

        // Invoice issued 10 days ago — beyond the 7-day window
        $invoice = $this->makeInvoice($company, $policy, [
            'issued_at' => now()->subDays(10),
        ]);

        $item  = $this->makeInvoiceItem($invoice);
        $items = [['invoice_item_id' => $item->id, 'quantity' => 1]];

        $this->expectException(ReturnWindowExpiredException::class);

        $this->service->validate($invoice, $items, $policy);
    }

    // =========================================================================
    // 5. Partial return (only some items from the invoice)
    // =========================================================================

    /**
     * Test: create() supports partial returns — only a subset of items returned.
     *
     * **Validates: Requirements 6.1, 6.2, 6.3**
     */
    #[Test]
    public function partial_return_only_some_items(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company, ['max_return_ratio' => 1.0]);
        $invoice = $this->makeInvoice($company, $policy, ['issued_at' => now()->subDays(2)]);

        $item1 = $this->makeInvoiceItem($invoice, ['qty' => 4, 'unit_price_snapshot' => 50.00]);
        $item2 = $this->makeInvoiceItem($invoice, ['qty' => 6, 'unit_price_snapshot' => 80.00]);

        // Only return item1 (partial return — item2 is not included)
        $items = [
            ['invoice_item_id' => $item1->id, 'quantity' => 2],
        ];

        $this->service->validate($invoice, $items, $policy);
        $returnInvoice = $this->service->create($invoice, $items, $policy);

        // Only item1 should have a return_invoice_items record
        $this->assertDatabaseHas('return_invoice_items', [
            'return_invoice_id' => $returnInvoice->id,
            'original_item_id'  => $item1->id,
            'returned_quantity' => 2,
        ]);

        $this->assertDatabaseMissing('return_invoice_items', [
            'return_invoice_id' => $returnInvoice->id,
            'original_item_id'  => $item2->id,
        ]);

        // Refund = 50.00 × 2 = 100.00
        $this->assertEquals(100.00, (float) $returnInvoice->total_refund_amount);
    }

    // =========================================================================
    // 6. Company data isolation
    // =========================================================================

    /**
     * Test: findById() throws ModelNotFoundException when accessing another company's return invoice.
     *
     * **Validates: Requirement 7.4**
     */
    #[Test]
    public function company_cannot_access_another_companys_return_invoice(): void
    {
        // Company A setup
        $companyA = $this->makeCompany();
        $policyA  = $this->makePolicy($companyA);
        $invoiceA = $this->makeInvoice($companyA, $policyA);
        $itemA    = $this->makeInvoiceItem($invoiceA);

        $itemsA = [['invoice_item_id' => $itemA->id, 'quantity' => 1]];
        $this->service->validate($invoiceA, $itemsA, $policyA);
        $returnInvoiceA = $this->service->create($invoiceA, $itemsA, $policyA);

        // Company B setup
        $companyB = $this->makeCompany();

        // Company B tries to access Company A's return invoice
        $this->expectException(ModelNotFoundException::class);

        $this->service->findById($returnInvoiceA->id, $companyB->id);
    }
}
