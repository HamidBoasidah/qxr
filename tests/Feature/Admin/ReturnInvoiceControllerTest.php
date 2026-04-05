<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
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

    private function makeAdmin(): Admin
    {
        return Admin::factory()->create(['is_active' => true]);
    }

    private function makeCompany(): User
    {
        return User::factory()->create(['user_type' => 'company', 'is_active' => true]);
    }

    private function makePolicy(User $company): ReturnPolicy
    {
        return ReturnPolicy::create([
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
        ]);
    }

    private function makeInvoice(User $company, ReturnPolicy $policy): Invoice
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
    // 1. admin_can_view_all_companies_return_invoices
    // =========================================================================

    public function test_admin_can_view_all_companies_return_invoices(): void
    {
        $admin    = $this->makeAdmin();
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $policyA = $this->makePolicy($companyA);
        $policyB = $this->makePolicy($companyB);

        $returnInvoiceA = $this->makeReturnInvoice($companyA, $policyA);
        $returnInvoiceB = $this->makeReturnInvoice($companyB, $policyB);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/return-invoices');

        $response->assertStatus(200);

        // Both companies' return invoices should be in the DB
        $this->assertDatabaseHas('return_invoices', ['id' => $returnInvoiceA->id, 'company_id' => $companyA->id]);
        $this->assertDatabaseHas('return_invoices', ['id' => $returnInvoiceB->id, 'company_id' => $companyB->id]);
    }

    // =========================================================================
    // 2. admin_can_view_single_return_invoice
    // =========================================================================

    public function test_admin_can_view_single_return_invoice(): void
    {
        $admin   = $this->makeAdmin();
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $returnInvoice = $this->makeReturnInvoice($company, $policy);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/return-invoices/{$returnInvoice->id}");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 3. admin_cannot_create_return_invoice
    // =========================================================================

    public function test_admin_cannot_create_return_invoice(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/return-invoices', [
                'original_invoice_id' => 1,
                'items'               => [],
            ]);

        $response->assertStatus(405);
    }

    // =========================================================================
    // 4. admin_cannot_approve_return_invoice
    // =========================================================================

    public function test_admin_cannot_approve_return_invoice(): void
    {
        $admin   = $this->makeAdmin();
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $returnInvoice = $this->makeReturnInvoice($company, $policy, ['status' => 'pending']);

        // The approve route doesn't exist in admin — expect 404
        $response = $this->actingAs($admin, 'admin')
            ->patch("/admin/return-invoices/{$returnInvoice->id}/approve");

        $response->assertStatus(404);
    }
}
