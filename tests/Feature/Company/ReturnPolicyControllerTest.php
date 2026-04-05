<?php

namespace Tests\Feature\Company;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\ReturnInvoice;
use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnPolicyControllerTest extends TestCase
{
    use RefreshDatabase;

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
            'is_default'                 => false,
            'is_active'                  => true,
        ], $overrides));
    }

    private function makeInvoiceLinkedToPolicy(User $company, ReturnPolicy $policy): Invoice
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
            'subtotal_snapshot'       => 500.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 500.00,
            'issued_at'               => now()->subDays(5),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ]);
    }

    private function makeReturnInvoiceLinkedToPolicy(User $company, ReturnPolicy $policy): ReturnInvoice
    {
        $invoice = $this->makeInvoiceLinkedToPolicy($company, $policy);

        return ReturnInvoice::create([
            'original_invoice_id' => $invoice->id,
            'company_id'          => $company->id,
            'return_policy_id'    => $policy->id,
            'total_refund_amount' => 100.00,
            'status'              => 'pending',
        ]);
    }

    private function validPolicyPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                       => 'New Policy',
            'return_window_days'         => 14,
            'max_return_ratio'           => 0.5,
            'bonus_return_enabled'       => false,
            'discount_deduction_enabled' => true,
            'min_days_before_expiry'     => 0,
            'is_default'                 => false,
            'is_active'                  => true,
        ], $overrides);
    }

    // =========================================================================
    // 1. company_can_view_own_return_policies_index
    // =========================================================================

    public function test_company_can_view_own_return_policies_index(): void
    {
        $company = $this->makeCompany();
        $this->makePolicy($company, ['name' => 'My Policy']);

        $response = $this->actingAs($company, 'web')
            ->get('/company/return-policies');

        $response->assertStatus(200);
    }

    // =========================================================================
    // 2. company_cannot_see_other_company_policies
    // =========================================================================

    public function test_company_cannot_see_other_company_policies(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->makePolicy($companyA, ['name' => 'Company A Policy']);
        $this->makePolicy($companyB, ['name' => 'Company B Policy']);

        $response = $this->actingAs($companyA, 'web')
            ->get('/company/return-policies');

        $response->assertStatus(200);

        // Company A should only see their own policies
        $this->assertDatabaseHas('return_policies', ['company_id' => $companyA->id, 'name' => 'Company A Policy']);
        $this->assertDatabaseHas('return_policies', ['company_id' => $companyB->id, 'name' => 'Company B Policy']);

        // The Inertia response props should only contain Company A's policies
        $response->assertInertia(fn ($page) => $page
            ->where('policies.data.0.company_id', $companyA->id)
        );
    }

    // =========================================================================
    // 3. company_can_create_return_policy
    // =========================================================================

    public function test_company_can_create_return_policy(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company, 'web')
            ->post('/company/return-policies', $this->validPolicyPayload());

        $response->assertStatus(302);

        $this->assertDatabaseHas('return_policies', [
            'company_id' => $company->id,
            'name'       => 'New Policy',
        ]);
    }

    // =========================================================================
    // 4. company_can_update_unlinked_policy
    // =========================================================================

    public function test_company_can_update_unlinked_policy(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $response = $this->actingAs($company, 'web')
            ->patch("/company/return-policies/{$policy->id}", [
                'name' => 'Updated Policy Name',
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('return_policies', [
            'id'   => $policy->id,
            'name' => 'Updated Policy Name',
        ]);
    }

    // =========================================================================
    // 5. company_cannot_update_policy_linked_to_invoices
    // =========================================================================

    public function test_company_cannot_update_policy_linked_to_invoices(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        // Link an invoice to this policy
        $this->makeInvoiceLinkedToPolicy($company, $policy);

        $response = $this->actingAs($company, 'web')
            ->patch("/company/return-policies/{$policy->id}", [
                'name' => 'Should Fail',
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 6. company_can_delete_policy_not_linked_to_return_invoices
    // =========================================================================

    public function test_company_can_delete_policy_not_linked_to_return_invoices(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $response = $this->actingAs($company, 'web')
            ->delete("/company/return-policies/{$policy->id}");

        $response->assertStatus(302);

        $this->assertSoftDeleted('return_policies', ['id' => $policy->id]);
    }

    // =========================================================================
    // 7. company_cannot_delete_policy_linked_to_return_invoices
    // =========================================================================

    public function test_company_cannot_delete_policy_linked_to_return_invoices(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        // Create a return invoice linked to this policy
        $this->makeReturnInvoiceLinkedToPolicy($company, $policy);

        $response = $this->actingAs($company, 'web')
            ->delete("/company/return-policies/{$policy->id}");

        $response->assertStatus(422);
        $response->assertSessionHasErrors(['message']);
    }

    // =========================================================================
    // 8. company_cannot_access_other_company_policy
    // =========================================================================

    public function test_company_cannot_access_other_company_policy(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $policyB = $this->makePolicy($companyB);

        $response = $this->actingAs($companyA, 'web')
            ->get("/company/return-policies/{$policyB->id}");

        $response->assertStatus(403);
    }
}
