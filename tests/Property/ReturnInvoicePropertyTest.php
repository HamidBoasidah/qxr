<?php

namespace Tests\Property;

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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Tests: Return Invoice Integrity
 *
 * Property 3:  snapshot immutability
 * Property 4:  no duplicate return invoice
 * Property 14: return invoice data completeness
 * Property 15: company data isolation
 *
 * Validates: Requirements 2.1, 2.2, 2.3, 3.1, 5.1, 5.2, 5.4, 7.3, 7.4
 */
class ReturnInvoicePropertyTest extends TestCase
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
            'name'                       => 'Test Policy',
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
        $order    = Order::create([
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
            'issued_at'               => now()->subDays(2),
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
    // Property 3: snapshot immutability
    // =========================================================================

    /**
     * Property 3: After a return invoice is created, the snapshot fields in
     * return_invoice_items (unit_price_snapshot, discount_type_snapshot,
     * discount_value_snapshot, expiry_date_snapshot) must not change even if
     * the original invoice_items record is updated.
     *
     * Validates: Requirements 2.1, 2.2, 2.3
     */
    #[Test]
    public function property3_snapshot_fields_are_immutable_after_return_creation(): void
    {
        // Feature: return-policy-invoice-system, Property 3: snapshot immutability

        for ($i = 0; $i < 100; $i++) {
            $company    = $this->makeCompany();
            $policy     = $this->makePolicy($company);
            $invoice    = $this->makeInvoice($company, $policy);
            $unitPrice  = fake()->randomFloat(4, 10.0, 500.0);
            $expiryDate = fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d');

            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => 5,
                'unit_price_snapshot' => $unitPrice,
                'discount_type'       => 'percent',
                'discount_value'      => fake()->randomFloat(4, 1.0, 50.0),
                'expiry_date'         => $expiryDate,
            ]);

            $requestItems  = [['invoice_item_id' => $item->id, 'quantity' => 2]];
            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $returnInvoice->load('items');

            $returnItem = $returnInvoice->items->first();

            // Capture snapshot values at creation
            $snapshotUnitPrice    = (float) $returnItem->unit_price_snapshot;
            $snapshotDiscountType = $returnItem->discount_type_snapshot;
            $snapshotDiscountVal  = $returnItem->discount_value_snapshot;
            $snapshotExpiry       = $returnItem->expiry_date_snapshot?->format('Y-m-d');

            // Simulate modifying the original invoice item (e.g., price change)
            $item->update([
                'unit_price_snapshot' => $unitPrice * 2,
                'discount_type'       => 'fixed',
                'discount_value'      => 999.99,
                'expiry_date'         => now()->addYears(10)->toDateString(),
            ]);

            // Reload the return invoice item — snapshots must remain unchanged
            $returnInvoice->load('items');
            $returnItemAfter = $returnInvoice->items->first();

            $this->assertEquals(
                $snapshotUnitPrice,
                (float) $returnItemAfter->unit_price_snapshot,
                "Iteration {$i}: unit_price_snapshot must not change after original item update"
            );
            $this->assertEquals(
                $snapshotDiscountType,
                $returnItemAfter->discount_type_snapshot,
                "Iteration {$i}: discount_type_snapshot must not change after original item update"
            );
            $this->assertEquals(
                $snapshotExpiry,
                $returnItemAfter->expiry_date_snapshot?->format('Y-m-d'),
                "Iteration {$i}: expiry_date_snapshot must not change after original item update"
            );
        }
    }

    /**
     * Property 3: Snapshot values at creation time match the original invoice_items values.
     *
     * Validates: Requirements 2.1, 2.2
     */
    #[Test]
    public function property3_snapshot_values_match_original_at_creation_time(): void
    {
        // Feature: return-policy-invoice-system, Property 3: snapshot immutability

        for ($i = 0; $i < 100; $i++) {
            $company    = $this->makeCompany();
            $policy     = $this->makePolicy($company);
            $invoice    = $this->makeInvoice($company, $policy);
            $unitPrice  = fake()->randomFloat(4, 10.0, 500.0);
            $discountType = fake()->randomElement(['percent', 'fixed', null]);
            $discountVal  = $discountType ? fake()->randomFloat(4, 1.0, 50.0) : null;
            $expiryDate   = fake()->boolean(70)
                ? fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d')
                : null;

            $item = $this->makeInvoiceItem($invoice, [
                'qty'                 => 5,
                'unit_price_snapshot' => $unitPrice,
                'discount_type'       => $discountType,
                'discount_value'      => $discountVal,
                'expiry_date'         => $expiryDate,
            ]);

            $requestItems  = [['invoice_item_id' => $item->id, 'quantity' => 1]];
            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $returnInvoice->load('items');

            $returnItem = $returnInvoice->items->first();

            $this->assertEquals(
                round($unitPrice, 4),
                round((float) $returnItem->unit_price_snapshot, 4),
                "Iteration {$i}: unit_price_snapshot must match original unit_price_snapshot"
            );
            $this->assertEquals(
                $discountType,
                $returnItem->discount_type_snapshot,
                "Iteration {$i}: discount_type_snapshot must match original discount_type"
            );
            $this->assertEquals(
                $expiryDate,
                $returnItem->expiry_date_snapshot?->format('Y-m-d'),
                "Iteration {$i}: expiry_date_snapshot must match original expiry_date"
            );
        }
    }

    // =========================================================================
    // Property 4: no duplicate return invoice
    // =========================================================================

    /**
     * Property 4: For any original_invoice_id, at most one return_invoices record
     * can exist. A second attempt must throw DuplicateReturnException.
     *
     * Validates: Requirements 3.1, 5.3, 5.5
     */
    #[Test]
    public function property4_no_duplicate_return_invoice_for_same_original(): void
    {
        // Feature: return-policy-invoice-system, Property 4: no duplicate return invoice

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => 5]);

            $requestItems = [['invoice_item_id' => $item->id, 'quantity' => 1]];

            // First return — must succeed
            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $this->assertNotNull($returnInvoice->id, "Iteration {$i}: first return must succeed");

            // Second return for the same invoice — must throw
            $this->expectException(DuplicateReturnException::class);

            $this->service->create($invoice, $requestItems, $policy);
        }
    }

    /**
     * Property 4: The unique constraint is enforced at the DB level.
     * Only one return_invoices record exists per original_invoice_id.
     *
     * Validates: Requirements 3.1, 5.3
     */
    #[Test]
    public function property4_database_has_at_most_one_return_per_invoice(): void
    {
        // Feature: return-policy-invoice-system, Property 4: no duplicate return invoice

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => 5]);

            $requestItems = [['invoice_item_id' => $item->id, 'quantity' => 1]];

            $this->service->create($invoice, $requestItems, $policy);

            // Attempt second return (will throw — catch it)
            try {
                $this->service->create($invoice, $requestItems, $policy);
            } catch (DuplicateReturnException $e) {
                // Expected
            }

            // Verify DB has exactly one return invoice for this original invoice
            $count = ReturnInvoice::where('original_invoice_id', $invoice->id)->count();

            $this->assertEquals(
                1,
                $count,
                "Iteration {$i}: DB must have exactly 1 return invoice for invoice {$invoice->id}"
            );
        }
    }

    /**
     * Property 4: Different invoices can each have their own return invoice.
     *
     * Validates: Requirements 3.1, 5.3
     */
    #[Test]
    public function property4_different_invoices_can_each_have_a_return(): void
    {
        // Feature: return-policy-invoice-system, Property 4: no duplicate return invoice

        for ($i = 0; $i < 100; $i++) {
            $company  = $this->makeCompany();
            $policy   = $this->makePolicy($company);
            $count    = fake()->numberBetween(2, 5);
            $invoices = [];

            for ($j = 0; $j < $count; $j++) {
                $invoice = $this->makeInvoice($company, $policy);
                $item    = $this->makeInvoiceItem($invoice, ['qty' => 5]);
                $this->service->create($invoice, [['invoice_item_id' => $item->id, 'quantity' => 1]], $policy);
                $invoices[] = $invoice;
            }

            // Each invoice must have exactly one return
            foreach ($invoices as $invoice) {
                $returnCount = ReturnInvoice::where('original_invoice_id', $invoice->id)->count();
                $this->assertEquals(
                    1,
                    $returnCount,
                    "Iteration {$i}: each invoice must have exactly 1 return invoice"
                );
            }
        }
    }

    // =========================================================================
    // Property 14: return invoice data completeness
    // =========================================================================

    /**
     * Property 14: Any created return invoice response must contain all required fields:
     * original_invoice_id, return_policy_id, total_refund_amount, created_at,
     * and all item-level details.
     *
     * Validates: Requirements 5.1, 5.2, 5.4, 7.3
     */
    #[Test]
    public function property14_return_invoice_contains_all_required_fields(): void
    {
        // Feature: return-policy-invoice-system, Property 14: return invoice data completeness

        for ($i = 0; $i < 100; $i++) {
            $company   = $this->makeCompany();
            $policy    = $this->makePolicy($company);
            $invoice   = $this->makeInvoice($company, $policy);
            $itemCount = fake()->numberBetween(1, 4);

            $requestItems = [];
            for ($j = 0; $j < $itemCount; $j++) {
                $qty  = fake()->numberBetween(2, 10);
                $item = $this->makeInvoiceItem($invoice, ['qty' => $qty]);
                $requestItems[] = ['invoice_item_id' => $item->id, 'quantity' => fake()->numberBetween(1, $qty)];
            }

            $returnInvoice = $this->service->create($invoice, $requestItems, $policy);
            $returnInvoice->load('items');

            // Assert top-level required fields
            $this->assertNotNull($returnInvoice->id, "Iteration {$i}: id must be present");
            $this->assertEquals(
                $invoice->id,
                $returnInvoice->original_invoice_id,
                "Iteration {$i}: original_invoice_id must be present and correct"
            );
            $this->assertEquals(
                $policy->id,
                $returnInvoice->return_policy_id,
                "Iteration {$i}: return_policy_id must be present and correct"
            );
            $this->assertNotNull(
                $returnInvoice->total_refund_amount,
                "Iteration {$i}: total_refund_amount must be present"
            );
            $this->assertNotNull(
                $returnInvoice->created_at,
                "Iteration {$i}: created_at must be present"
            );
            $this->assertEquals(
                'pending',
                $returnInvoice->status,
                "Iteration {$i}: status must be 'pending' on creation"
            );

            // Assert item-level required fields
            $this->assertCount(
                $itemCount,
                $returnInvoice->items,
                "Iteration {$i}: return invoice must have {$itemCount} items"
            );

            foreach ($returnInvoice->items as $returnItem) {
                $this->assertNotNull($returnItem->original_item_id, "Iteration {$i}: original_item_id must be present");
                $this->assertNotNull($returnItem->returned_quantity, "Iteration {$i}: returned_quantity must be present");
                $this->assertNotNull($returnItem->unit_price_snapshot, "Iteration {$i}: unit_price_snapshot must be present");
                $this->assertNotNull($returnItem->refund_amount, "Iteration {$i}: refund_amount must be present");
                $this->assertGreaterThan(0, (float) $returnItem->refund_amount, "Iteration {$i}: refund_amount must be positive");
            }
        }
    }

    /**
     * Property 14: findById() returns the complete return invoice with all items.
     *
     * Validates: Requirements 7.1, 7.3
     */
    #[Test]
    public function property14_find_by_id_returns_complete_data(): void
    {
        // Feature: return-policy-invoice-system, Property 14: return invoice data completeness

        for ($i = 0; $i < 100; $i++) {
            $company   = $this->makeCompany();
            $policy    = $this->makePolicy($company);
            $invoice   = $this->makeInvoice($company, $policy);
            $itemCount = fake()->numberBetween(1, 4);

            $requestItems = [];
            for ($j = 0; $j < $itemCount; $j++) {
                $qty  = fake()->numberBetween(2, 10);
                $item = $this->makeInvoiceItem($invoice, ['qty' => $qty]);
                $requestItems[] = ['invoice_item_id' => $item->id, 'quantity' => fake()->numberBetween(1, $qty)];
            }

            $created = $this->service->create($invoice, $requestItems, $policy);

            // Retrieve via findById
            $found = $this->service->findById($created->id, $company->id);

            $this->assertEquals($created->id, $found->id, "Iteration {$i}: findById must return the correct record");
            $this->assertEquals($invoice->id, $found->original_invoice_id, "Iteration {$i}: original_invoice_id must match");
            $this->assertEquals($policy->id, $found->return_policy_id, "Iteration {$i}: return_policy_id must match");
            $this->assertNotNull($found->total_refund_amount, "Iteration {$i}: total_refund_amount must be present");
            $this->assertCount($itemCount, $found->items, "Iteration {$i}: all items must be loaded");
        }
    }

    // =========================================================================
    // Property 15: company data isolation
    // =========================================================================

    /**
     * Property 15: A company must not be able to access return invoices belonging
     * to another company.
     *
     * Validates: Requirement 7.4
     */
    #[Test]
    public function property15_company_cannot_access_other_companys_return_invoices(): void
    {
        // Feature: return-policy-invoice-system, Property 15: company data isolation

        for ($i = 0; $i < 100; $i++) {
            // Company A creates a return invoice
            $companyA = $this->makeCompany();
            $policyA  = $this->makePolicy($companyA);
            $invoiceA = $this->makeInvoice($companyA, $policyA);
            $itemA    = $this->makeInvoiceItem($invoiceA, ['qty' => 5]);

            $returnInvoiceA = $this->service->create(
                $invoiceA,
                [['invoice_item_id' => $itemA->id, 'quantity' => 1]],
                $policyA
            );

            // Company B tries to access Company A's return invoice
            $companyB = $this->makeCompany();

            $this->expectException(ModelNotFoundException::class);

            $this->service->findById($returnInvoiceA->id, $companyB->id);
        }
    }

    /**
     * Property 15: listForCompany() only returns return invoices for the requesting company.
     *
     * Validates: Requirement 7.4
     */
    #[Test]
    public function property15_list_for_company_only_returns_own_invoices(): void
    {
        // Feature: return-policy-invoice-system, Property 15: company data isolation

        for ($i = 0; $i < 100; $i++) {
            $companyA = $this->makeCompany();
            $companyB = $this->makeCompany();

            $policyA = $this->makePolicy($companyA);
            $policyB = $this->makePolicy($companyB);

            $countA = fake()->numberBetween(1, 3);
            $countB = fake()->numberBetween(1, 3);

            // Create return invoices for Company A
            for ($j = 0; $j < $countA; $j++) {
                $invoiceA = $this->makeInvoice($companyA, $policyA);
                $itemA    = $this->makeInvoiceItem($invoiceA, ['qty' => 5]);
                $this->service->create($invoiceA, [['invoice_item_id' => $itemA->id, 'quantity' => 1]], $policyA);
            }

            // Create return invoices for Company B
            for ($j = 0; $j < $countB; $j++) {
                $invoiceB = $this->makeInvoice($companyB, $policyB);
                $itemB    = $this->makeInvoiceItem($invoiceB, ['qty' => 5]);
                $this->service->create($invoiceB, [['invoice_item_id' => $itemB->id, 'quantity' => 1]], $policyB);
            }

            // Company A's list must only contain Company A's return invoices
            $listA = $this->service->listForCompany($companyA->id, 100);
            foreach ($listA->items() as $returnInvoice) {
                $this->assertEquals(
                    $companyA->id,
                    $returnInvoice->company_id,
                    "Iteration {$i}: Company A's list must not contain Company B's return invoices"
                );
            }

            // Company B's list must only contain Company B's return invoices
            $listB = $this->service->listForCompany($companyB->id, 100);
            foreach ($listB->items() as $returnInvoice) {
                $this->assertEquals(
                    $companyB->id,
                    $returnInvoice->company_id,
                    "Iteration {$i}: Company B's list must not contain Company A's return invoices"
                );
            }

            $this->assertCount(
                $countA,
                $listA->items(),
                "Iteration {$i}: Company A must have exactly {$countA} return invoices"
            );
            $this->assertCount(
                $countB,
                $listB->items(),
                "Iteration {$i}: Company B must have exactly {$countB} return invoices"
            );
        }
    }

    /**
     * Property 15: A company can always access its own return invoices.
     *
     * Validates: Requirement 7.4
     */
    #[Test]
    public function property15_company_can_always_access_own_return_invoices(): void
    {
        // Feature: return-policy-invoice-system, Property 15: company data isolation

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => 5]);

            $created = $this->service->create(
                $invoice,
                [['invoice_item_id' => $item->id, 'quantity' => 1]],
                $policy
            );

            // Should not throw
            $found = $this->service->findById($created->id, $company->id);

            $this->assertEquals(
                $created->id,
                $found->id,
                "Iteration {$i}: company must be able to access its own return invoice"
            );
        }
    }
}
