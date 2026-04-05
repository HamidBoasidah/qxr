<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnPolicyControllerTest extends TestCase
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

    // =========================================================================
    // 1. admin_can_view_all_companies_return_policies
    // =========================================================================

    public function test_admin_can_view_all_companies_return_policies(): void
    {
        $admin    = $this->makeAdmin();
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->makePolicy($companyA, ['name' => 'Company A Policy']);
        $this->makePolicy($companyB, ['name' => 'Company B Policy']);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/return-policies');

        $response->assertStatus(200);

        // Both companies' policies should be in the DB and visible to admin
        $this->assertDatabaseHas('return_policies', ['company_id' => $companyA->id, 'name' => 'Company A Policy']);
        $this->assertDatabaseHas('return_policies', ['company_id' => $companyB->id, 'name' => 'Company B Policy']);
    }

    // =========================================================================
    // 2. admin_can_view_single_return_policy
    // =========================================================================

    public function test_admin_can_view_single_return_policy(): void
    {
        $admin   = $this->makeAdmin();
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/return-policies/{$policy->id}");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 3. admin_cannot_create_return_policy
    // =========================================================================

    public function test_admin_cannot_create_return_policy(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/return-policies', [
                'name'                       => 'New Policy',
                'return_window_days'         => 14,
                'max_return_ratio'           => 0.5,
                'bonus_return_enabled'       => false,
                'discount_deduction_enabled' => true,
                'min_days_before_expiry'     => 0,
                'is_default'                 => false,
                'is_active'                  => true,
            ]);

        $response->assertStatus(405);
    }

    // =========================================================================
    // 4. admin_cannot_update_return_policy
    // =========================================================================

    public function test_admin_cannot_update_return_policy(): void
    {
        $admin   = $this->makeAdmin();
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $response = $this->actingAs($admin, 'admin')
            ->patch("/admin/return-policies/{$policy->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(405);
    }

    // =========================================================================
    // 5. admin_cannot_delete_return_policy
    // =========================================================================

    public function test_admin_cannot_delete_return_policy(): void
    {
        $admin   = $this->makeAdmin();
        $company = $this->makeCompany();
        $policy  = $this->makePolicy($company);

        $response = $this->actingAs($admin, 'admin')
            ->delete("/admin/return-policies/{$policy->id}");

        $response->assertStatus(405);
    }
}
