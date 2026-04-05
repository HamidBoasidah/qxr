<?php

namespace Tests\Feature;

use App\Exceptions\ReturnPolicy\NoPolicyFoundException;
use App\Exceptions\ReturnPolicy\PolicyInUseException;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ReturnPolicy;
use App\Models\User;
use App\Services\ReturnPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature Tests: ReturnPolicyService
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6**
 *
 * Covers:
 * - Creating a new return policy
 * - Setting a policy as default cancels the previous default
 * - At most one default policy per company at any time
 * - resolveForInvoice reads directly from invoice.return_policy_id
 * - Updating a policy preserves the is_default uniqueness constraint
 */
class ReturnPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReturnPolicyService();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompany(): User
    {
        return User::factory()->create(['user_type' => 'company', 'is_active' => true]);
    }

    private function makeInvoice(User $company, ReturnPolicy $policy, string $status = 'paid'): Invoice
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'is_active' => true]);

        $order = Order::create([
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
            'issued_at'               => now(),
            'status'                  => $status,
            'return_policy_id'        => $policy->id,
        ]);
    }

    private function policyData(array $overrides = []): array
    {
        return array_merge([
            'name'                       => 'Standard Policy',
            'return_window_days'         => 30,
            'max_return_ratio'           => 0.5,
            'bonus_return_enabled'       => false,
            'bonus_return_ratio'         => null,
            'discount_deduction_enabled' => true,
            'min_days_before_expiry'     => 0,
            'is_default'                 => false,
            'is_active'                  => true,
        ], $overrides);
    }

    // =========================================================================
    // 1. Creating a new return policy
    // =========================================================================

    /**
     * Test: create() persists a new return policy with all fields.
     *
     * **Validates: Requirement 1.1**
     */
    #[Test]
    public function create_persists_new_policy_with_all_fields(): void
    {
        $company = $this->makeCompany();

        $policy = $this->service->create($company->id, $this->policyData([
            'name'                       => 'My Policy',
            'return_window_days'         => 14,
            'max_return_ratio'           => 0.75,
            'bonus_return_enabled'       => true,
            'bonus_return_ratio'         => 0.5,
            'discount_deduction_enabled' => false,
            'min_days_before_expiry'     => 7,
            'is_active'                  => true,
        ]));

        $this->assertInstanceOf(ReturnPolicy::class, $policy);
        $this->assertDatabaseHas('return_policies', [
            'id'                         => $policy->id,
            'company_id'                 => $company->id,
            'name'                       => 'My Policy',
            'return_window_days'         => 14,
            'bonus_return_enabled'       => true,
            'discount_deduction_enabled' => false,
            'min_days_before_expiry'     => 7,
            'is_active'                  => true,
        ]);
    }

    /**
     * Test: create() with is_default = false does not affect other policies.
     *
     * **Validates: Requirement 1.1**
     */
    #[Test]
    public function create_non_default_policy_does_not_affect_existing_defaults(): void
    {
        $company = $this->makeCompany();

        // Create an existing default policy
        $existing = $this->service->create($company->id, $this->policyData(['is_default' => true]));

        // Create a new non-default policy
        $this->service->create($company->id, $this->policyData(['is_default' => false, 'name' => 'Second Policy']));

        // The original default should still be default
        $this->assertTrue($existing->fresh()->is_default);
        $this->assertEquals(1, ReturnPolicy::where('company_id', $company->id)->where('is_default', true)->count());
    }

    // =========================================================================
    // 2. Setting a policy as default cancels the previous default
    // =========================================================================

    /**
     * Test: create() with is_default = true unsets the previous default.
     *
     * **Validates: Requirement 1.2**
     */
    #[Test]
    public function create_with_is_default_true_unsets_previous_default(): void
    {
        $company = $this->makeCompany();

        $first = $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'First']));
        $this->assertTrue($first->fresh()->is_default);

        $second = $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'Second']));

        $this->assertFalse($first->fresh()->is_default, 'Previous default should be unset');
        $this->assertTrue($second->fresh()->is_default, 'New policy should be default');
    }

    /**
     * Test: setAsDefault() unsets the previous default and sets the new one.
     *
     * **Validates: Requirement 1.2**
     */
    #[Test]
    public function set_as_default_unsets_previous_default(): void
    {
        $company = $this->makeCompany();

        $first  = $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'First']));
        $second = $this->service->create($company->id, $this->policyData(['is_default' => false, 'name' => 'Second']));

        $this->service->setAsDefault($second);

        $this->assertFalse($first->fresh()->is_default, 'Previous default should be unset');
        $this->assertTrue($second->fresh()->is_default, 'Target policy should now be default');
    }

    /**
     * Test: update() with is_default = true unsets the previous default.
     *
     * **Validates: Requirement 1.6**
     */
    #[Test]
    public function update_with_is_default_true_unsets_previous_default(): void
    {
        $company = $this->makeCompany();

        $first  = $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'First']));
        $second = $this->service->create($company->id, $this->policyData(['is_default' => false, 'name' => 'Second']));

        $this->service->update($second, ['is_default' => true]);

        $this->assertFalse($first->fresh()->is_default, 'Previous default should be unset after update');
        $this->assertTrue($second->fresh()->is_default, 'Updated policy should be default');
    }

    // =========================================================================
    // 3. At most one default policy per company at any time
    // =========================================================================

    /**
     * Test: Only one default policy exists after multiple create() calls.
     *
     * **Validates: Requirement 1.3**
     */
    #[Test]
    public function at_most_one_default_policy_per_company_after_multiple_creates(): void
    {
        $company = $this->makeCompany();

        $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'P1']));
        $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'P2']));
        $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'P3']));

        $defaultCount = ReturnPolicy::where('company_id', $company->id)
            ->where('is_default', true)
            ->count();

        $this->assertEquals(1, $defaultCount, 'There must be exactly one default policy');
    }

    /**
     * Test: Only one default policy exists after multiple setAsDefault() calls.
     *
     * **Validates: Requirement 1.3**
     */
    #[Test]
    public function at_most_one_default_policy_per_company_after_multiple_set_as_default(): void
    {
        $company = $this->makeCompany();

        $policies = collect(range(1, 5))->map(fn($i) =>
            $this->service->create($company->id, $this->policyData(['name' => "Policy $i"]))
        );

        foreach ($policies as $policy) {
            $this->service->setAsDefault($policy);
        }

        $defaultCount = ReturnPolicy::where('company_id', $company->id)
            ->where('is_default', true)
            ->count();

        $this->assertEquals(1, $defaultCount, 'There must be exactly one default policy after repeated setAsDefault calls');
        $this->assertTrue($policies->last()->fresh()->is_default, 'The last policy set as default should be the current default');
    }

    /**
     * Test: Default policies of different companies are independent.
     *
     * **Validates: Requirement 1.3**
     */
    #[Test]
    public function default_policies_are_isolated_per_company(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $policyA = $this->service->create($companyA->id, $this->policyData(['is_default' => true, 'name' => 'A Policy']));
        $policyB = $this->service->create($companyB->id, $this->policyData(['is_default' => true, 'name' => 'B Policy']));

        // Setting a new default for company A should not affect company B
        $policyA2 = $this->service->create($companyA->id, $this->policyData(['is_default' => true, 'name' => 'A Policy 2']));

        $this->assertFalse($policyA->fresh()->is_default);
        $this->assertTrue($policyA2->fresh()->is_default);
        $this->assertTrue($policyB->fresh()->is_default, 'Company B default should be unaffected');
    }

    /**
     * Test: update() preserves the single-default constraint when updating the current default.
     *
     * **Validates: Requirement 1.6**
     */
    #[Test]
    public function update_preserves_single_default_when_updating_current_default(): void
    {
        $company = $this->makeCompany();

        $policy = $this->service->create($company->id, $this->policyData(['is_default' => true]));

        // Update the default policy (keeping is_default = true)
        $this->service->update($policy, ['name' => 'Updated Name', 'is_default' => true]);

        $defaultCount = ReturnPolicy::where('company_id', $company->id)
            ->where('is_default', true)
            ->count();

        $this->assertEquals(1, $defaultCount);
        $this->assertEquals('Updated Name', $policy->fresh()->name);
    }

    // =========================================================================
    // 4. resolveForInvoice reads from invoice.return_policy_id
    // =========================================================================

    /**
     * Test: resolveForInvoice() returns the policy linked to the invoice.
     *
     * **Validates: Requirement 1.4**
     */
    #[Test]
    public function resolve_for_invoice_returns_policy_from_invoice_return_policy_id(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->service->create($company->id, $this->policyData(['name' => 'Invoice Policy']));
        $invoice = $this->makeInvoice($company, $policy);

        $resolved = $this->service->resolveForInvoice($invoice);

        $this->assertEquals($policy->id, $resolved->id);
        $this->assertEquals('Invoice Policy', $resolved->name);
    }

    /**
     * Test: resolveForInvoice() returns the specific policy on the invoice,
     * not the company's default policy.
     *
     * **Validates: Requirement 1.4** — reads from invoice.return_policy_id directly
     */
    #[Test]
    public function resolve_for_invoice_reads_from_invoice_not_company_default(): void
    {
        $company = $this->makeCompany();

        // Create a default policy for the company
        $defaultPolicy  = $this->service->create($company->id, $this->policyData(['is_default' => true, 'name' => 'Default']));

        // Create a different policy and assign it to the invoice
        $invoicePolicy  = $this->service->create($company->id, $this->policyData(['is_default' => false, 'name' => 'Invoice Specific']));
        $invoice        = $this->makeInvoice($company, $invoicePolicy);

        $resolved = $this->service->resolveForInvoice($invoice);

        $this->assertEquals($invoicePolicy->id, $resolved->id, 'Should return the policy on the invoice, not the default');
        $this->assertNotEquals($defaultPolicy->id, $resolved->id);
    }

    /**
     * Test: resolveForInvoice() throws NoPolicyFoundException when the policy is inactive.
     *
     * **Validates: Requirement 1.5**
     */
    #[Test]
    public function resolve_for_invoice_throws_when_policy_is_inactive(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->service->create($company->id, $this->policyData(['is_active' => false]));
        $invoice = $this->makeInvoice($company, $policy);

        $this->expectException(NoPolicyFoundException::class);

        $this->service->resolveForInvoice($invoice);
    }

    /**
     * Test: resolveForInvoice() throws NoPolicyFoundException when the policy is force-deleted.
     *
     * **Validates: Requirement 1.5**
     */
    #[Test]
    public function resolve_for_invoice_throws_when_policy_does_not_exist(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->service->create($company->id, $this->policyData());
        $invoice = $this->makeInvoice($company, $policy);

        // Force-delete the policy to simulate a missing policy
        $policy->forceDelete();

        $this->expectException(NoPolicyFoundException::class);

        $this->service->resolveForInvoice($invoice);
    }

    // =========================================================================
    // 5. update() rejects modification when policy is linked to invoices
    // =========================================================================

    /**
     * Test: update() throws PolicyInUseException when the policy is linked to invoices.
     *
     * **Validates: Requirements 1.8, 1.9**
     */
    #[Test]
    public function update_throws_policy_in_use_exception_when_linked_to_invoices(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->service->create($company->id, $this->policyData(['name' => 'Linked Policy']));

        // Link an invoice to this policy
        $this->makeInvoice($company, $policy);

        $this->expectException(PolicyInUseException::class);
        $this->expectExceptionMessage('Cannot modify a policy that is linked to existing invoices');

        $this->service->update($policy, ['name' => 'Modified Name']);
    }

    /**
     * Test: update() succeeds when the policy has no linked invoices.
     *
     * **Validates: Requirements 1.8, 1.9**
     */
    #[Test]
    public function update_succeeds_when_policy_has_no_linked_invoices(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->service->create($company->id, $this->policyData(['name' => 'Unlinked Policy']));

        $updated = $this->service->update($policy, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertDatabaseHas('return_policies', [
            'id'   => $policy->id,
            'name' => 'Updated Name',
        ]);
    }
}
