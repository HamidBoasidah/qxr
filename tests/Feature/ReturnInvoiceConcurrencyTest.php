<?php

namespace Tests\Feature;

use App\Exceptions\ReturnInvoice\DuplicateReturnException;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Concurrency Tests: ReturnInvoiceConcurrencyTest
 *
 * **Validates: Requirements 3.1, 3.2, 5.3, 5.5**
 *
 * Covers:
 * - Two sequential calls simulating concurrent requests: only one succeeds (HTTP 201 equivalent),
 *   the other is rejected with DuplicateReturnException (HTTP 409 equivalent).
 * - The DB unique constraint on original_invoice_id prevents duplicate return invoices.
 * - The lockForUpdate + re-check inside the transaction guards against race conditions.
 *
 * Note: PHP does not support true multi-threading in tests. Concurrency is simulated
 * by making two sequential service calls and verifying that the DB unique constraint
 * and the pessimistic lock (lockForUpdate + re-check) together ensure only one
 * return invoice is ever created per original invoice.
 */
class ReturnInvoiceConcurrencyTest extends TestCase
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
            'issued_at'               => now()->subDays(5),
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
    // 1. Only one of two concurrent requests succeeds
    // =========================================================================

    /**
     * Test: Two simulated concurrent requests for the same invoice — only one succeeds,
     * the other is rejected with DuplicateReturnException (HTTP 409).
     *
     * Simulates concurrency by running two sequential service->create() calls
     * for the same invoice. The first call succeeds; the second must be rejected
     * because the DB unique constraint on original_invoice_id prevents duplicates,
     * and the re-check inside the transaction catches any race condition.
     *
     * **Validates: Requirements 3.1, 3.2, 5.3, 5.5**
     */
    #[Test]
    public function concurrent_return_requests_only_one_succeeds(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy);
        $item    = $this->makeInvoiceItem($invoice, ['qty' => 3]);

        $items = [['invoice_item_id' => $item->id, 'quantity' => 3]];

        $results   = [];
        $callbacks = [];

        // Simulate two concurrent requests as sequential closures
        for ($i = 0; $i < 2; $i++) {
            $callbacks[] = function () use ($invoice, $items, $policy, &$results): void {
                try {
                    $results[] = $this->service->create($invoice, $items, $policy);
                } catch (DuplicateReturnException $e) {
                    $results[] = 'duplicate';
                }
            };
        }

        // Execute both (sequential simulation of concurrent requests)
        foreach ($callbacks as $callback) {
            $callback();
        }

        // Exactly one should succeed
        $successes = array_filter($results, fn ($r) => $r instanceof ReturnInvoice);
        $this->assertCount(1, $successes, 'Exactly one return invoice should be created.');

        // Exactly one should be rejected as duplicate
        $duplicates = array_filter($results, fn ($r) => $r === 'duplicate');
        $this->assertCount(1, $duplicates, 'Exactly one request should be rejected as duplicate.');
    }

    // =========================================================================
    // 2. DB unique constraint prevents duplicate return invoices
    // =========================================================================

    /**
     * Test: The DB unique constraint on original_invoice_id ensures only one
     * return_invoices record exists per original invoice, even after two attempts.
     *
     * **Validates: Requirements 5.3, 5.5**
     */
    #[Test]
    public function db_unique_constraint_prevents_duplicate_return_invoice(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy);
        $item    = $this->makeInvoiceItem($invoice, ['qty' => 2]);

        $items = [['invoice_item_id' => $item->id, 'quantity' => 2]];

        // First request succeeds
        $this->service->create($invoice, $items, $policy);

        // Second request must throw DuplicateReturnException
        $this->expectException(DuplicateReturnException::class);
        $this->service->create($invoice, $items, $policy);
    }

    // =========================================================================
    // 3. Only one return_invoices record exists after concurrent attempts
    // =========================================================================

    /**
     * Test: After two simulated concurrent requests, only one return_invoices
     * record exists in the database for the given original_invoice_id.
     *
     * **Validates: Requirements 3.1, 5.3**
     */
    #[Test]
    public function only_one_return_invoice_record_exists_after_concurrent_attempts(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy);
        $item    = $this->makeInvoiceItem($invoice, ['qty' => 4]);

        $items = [['invoice_item_id' => $item->id, 'quantity' => 4]];

        // Attempt 1 — should succeed
        try {
            $this->service->create($invoice, $items, $policy);
        } catch (DuplicateReturnException) {
            // Should not happen on first attempt
        }

        // Attempt 2 — should be rejected
        try {
            $this->service->create($invoice, $items, $policy);
        } catch (DuplicateReturnException) {
            // Expected
        }

        // Assert exactly one record in the DB
        $count = ReturnInvoice::where('original_invoice_id', $invoice->id)->count();
        $this->assertEquals(1, $count, 'Only one return invoice should exist for the original invoice.');
    }

    // =========================================================================
    // 4. HTTP layer: second request returns HTTP 409
    // =========================================================================

    /**
     * Test: Via the HTTP layer, the second request for the same invoice returns HTTP 409
     * with the correct error message and error_code.
     *
     * **Validates: Requirements 3.2, 5.5**
     */
    #[Test]
    public function http_second_request_returns_409(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy);
        $item    = $this->makeInvoiceItem($invoice, ['qty' => 2]);

        $payload = [
            'original_invoice_id' => $invoice->id,
            'items'               => [
                ['invoice_item_id' => $item->id, 'quantity' => 2],
            ],
        ];

        // First request — should succeed with HTTP 201
        $response1 = $this->actingAs($company)->postJson('/api/return-invoices', $payload);
        $response1->assertStatus(201);

        // Second request — should be rejected with HTTP 409
        $response2 = $this->actingAs($company)->postJson('/api/return-invoices', $payload);
        $response2->assertStatus(409);
        $response2->assertJsonFragment([
            'success'    => false,
            'error_code' => 'duplicate_return',
        ]);
    }

    // =========================================================================
    // 5. lockForUpdate re-check catches race condition inside transaction
    // =========================================================================

    /**
     * Test: When a return invoice is manually inserted before the service->create()
     * call (simulating a race condition where the first request committed just before
     * the second enters the transaction), the re-check inside the transaction detects
     * the duplicate and throws DuplicateReturnException.
     *
     * This directly tests the "re-check inside transaction" guard described in design.md.
     *
     * **Validates: Requirements 3.1, 3.2, 5.5**
     */
    #[Test]
    public function lock_for_update_recheck_catches_race_condition(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $invoice = $this->makeInvoice($company, $policy);
        $item    = $this->makeInvoiceItem($invoice, ['qty' => 1]);

        $items = [['invoice_item_id' => $item->id, 'quantity' => 1]];

        // Simulate: first request already committed a return invoice
        ReturnInvoice::create([
            'original_invoice_id' => $invoice->id,
            'company_id'          => $company->id,
            'return_policy_id'    => $policy->id,
            'total_refund_amount' => 100.00,
            'status'              => 'pending',
        ]);

        // Second request enters the service — the re-check inside the transaction
        // must detect the existing record and throw DuplicateReturnException
        $this->expectException(DuplicateReturnException::class);

        $this->service->create($invoice, $items, $policy);
    }
}
