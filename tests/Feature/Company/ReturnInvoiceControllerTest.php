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

class ReturnInvoiceControllerTest extends TestCase
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
            'subtotal_snapshot'       => 500.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 500.00,
            'issued_at'               => now()->subDays(5),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ], $overrides));
    }

    private function makeReturnInvoice(User $company, ReturnPolicy $policy, array $overrides = []): ReturnInvoice
    {
        $invoice = $this->makeInvoice($company, $policy);

        return ReturnInvoice::create(array_merge([
            'original_invoice_id' => $invoice->id,
            'company_id'          => $company->id,
            'return_policy_id'    => $policy->id,
            'total_refund_amount' => 100.00,
            'status'              => 'pending',
        ], $overrides));
    }

    // =========================================================================
    // 1. company_can_view_own_return_invoices_index
    // =========================================================================

    public function test_company_can_view_own_return_invoices_index(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $this->makeReturnInvoice($company, $policy);

        $response = $this->actingAs($company, 'web')
            ->get('/company/return-invoices');

        $response->assertStatus(200);
    }

    // =========================================================================
    // 2. company_cannot_see_other_company_return_invoices
    // =========================================================================

    public function test_company_cannot_see_other_company_return_invoices(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $policyA = $this->makePolicy($companyA);
        $policyB = $this->makePolicy($companyB);

        $returnInvoiceA = $this->makeReturnInvoice($companyA, $policyA);
        $returnInvoiceB = $this->makeReturnInvoice($companyB, $policyB);

        $response = $this->actingAs($companyA, 'web')
            ->get('/company/return-invoices');

        $response->assertStatus(200);

        // Company A's return invoice should be in the DB
        $this->assertDatabaseHas('return_invoices', ['id' => $returnInvoiceA->id, 'company_id' => $companyA->id]);
        // Company B's return invoice should also be in the DB but not visible to A
        $this->assertDatabaseHas('return_invoices', ['id' => $returnInvoiceB->id, 'company_id' => $companyB->id]);

        // The Inertia response should only contain Company A's return invoices
        $response->assertInertia(fn ($page) => $page
            ->where('returnInvoices.data.0.company_id', $companyA->id)
        );
    }

    // =========================================================================
    // 3. company_can_approve_pending_return_invoice
    // =========================================================================

    public function test_company_can_approve_pending_return_invoice(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $returnInvoice = $this->makeReturnInvoice($company, $policy, ['status' => 'pending']);

        $response = $this->actingAs($company, 'web')
            ->patch("/company/return-invoices/{$returnInvoice->id}/approve");

        $response->assertStatus(302);

        $this->assertDatabaseHas('return_invoices', [
            'id'     => $returnInvoice->id,
            'status' => 'approved',
        ]);
    }

    // =========================================================================
    // 4. company_can_reject_pending_return_invoice
    // =========================================================================

    public function test_company_can_reject_pending_return_invoice(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $returnInvoice = $this->makeReturnInvoice($company, $policy, ['status' => 'pending']);

        $response = $this->actingAs($company, 'web')
            ->patch("/company/return-invoices/{$returnInvoice->id}/reject");

        $response->assertStatus(302);

        $this->assertDatabaseHas('return_invoices', [
            'id'     => $returnInvoice->id,
            'status' => 'rejected',
        ]);
    }

    // =========================================================================
    // 5. company_cannot_approve_already_approved_invoice
    // =========================================================================

    public function test_company_cannot_approve_already_approved_invoice(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $returnInvoice = $this->makeReturnInvoice($company, $policy, ['status' => 'approved']);

        $response = $this->actingAs($company, 'web')
            ->patch("/company/return-invoices/{$returnInvoice->id}/approve");

        $response->assertStatus(422);
    }

    // =========================================================================
    // 6. company_cannot_reject_already_rejected_invoice
    // =========================================================================

    public function test_company_cannot_reject_already_rejected_invoice(): void
    {
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);
        $returnInvoice = $this->makeReturnInvoice($company, $policy, ['status' => 'rejected']);

        $response = $this->actingAs($company, 'web')
            ->patch("/company/return-invoices/{$returnInvoice->id}/reject");

        $response->assertStatus(422);
    }

    // =========================================================================
    // 7. company_cannot_approve_other_company_return_invoice
    // =========================================================================

    public function test_company_cannot_approve_other_company_return_invoice(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $policyB       = $this->makePolicy($companyB);
        $returnInvoice = $this->makeReturnInvoice($companyB, $policyB, ['status' => 'pending']);

        $response = $this->actingAs($companyA, 'web')
            ->patch("/company/return-invoices/{$returnInvoice->id}/approve");

        $response->assertStatus(403);
    }

    // =========================================================================
    // 8. company_cannot_access_other_company_return_invoice_show
    // =========================================================================

    public function test_company_cannot_access_other_company_return_invoice_show(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $policyB       = $this->makePolicy($companyB);
        $returnInvoice = $this->makeReturnInvoice($companyB, $policyB);

        $response = $this->actingAs($companyA, 'web')
            ->get("/company/return-invoices/{$returnInvoice->id}");

        $response->assertStatus(403);
    }
}
