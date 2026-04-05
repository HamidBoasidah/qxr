<?php

namespace Tests\Property;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\ReturnPolicy;
use App\Models\User;
use App\Services\ReturnPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Tests: Return Policy
 *
 * Property 1: uniqueness of default policy
 * Property 2: policy binding at invoice creation
 * Property 16: input validation rejects invalid policy fields
 *
 * Validates: Requirements 1.2, 1.3, 1.4, 1.6, 8.1, 8.2, 8.3, 8.4
 */
class ReturnPolicyPropertyTest extends TestCase
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

    private function basePolicyData(array $overrides = []): array
    {
        return array_merge([
            'name'                       => 'Policy ' . Str::random(5),
            'return_window_days'         => fake()->numberBetween(1, 90),
            'max_return_ratio'           => fake()->randomFloat(4, 0.01, 1.00),
            'bonus_return_enabled'       => false,
            'bonus_return_ratio'         => null,
            'discount_deduction_enabled' => true,
            'min_days_before_expiry'     => fake()->numberBetween(0, 30),
            'is_default'                 => false,
            'is_active'                  => true,
        ], $overrides);
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
            'subtotal_snapshot'       => 1000.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 1000.00,
            'issued_at'               => now(),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ]);
    }

    private function validatePolicyRules(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'name'                       => ['required', 'string', 'max:255'],
            'return_window_days'         => ['required', 'integer', 'min:1'],
            'max_return_ratio'           => ['required', 'numeric', 'between:0.01,1.00'],
            'bonus_return_enabled'       => ['sometimes', 'boolean'],
            'bonus_return_ratio'         => ['required_if:bonus_return_enabled,true', 'nullable', 'numeric', 'between:0.00,1.00'],
            'discount_deduction_enabled' => ['sometimes', 'boolean'],
            'min_days_before_expiry'     => ['required', 'integer', 'min:0'],
            'is_default'                 => ['sometimes', 'boolean'],
            'is_active'                  => ['sometimes', 'boolean'],
        ]);
    }

    // =========================================================================
    // Property 1: uniqueness of default policy
    // =========================================================================

    /**
     * Property 1: For any sequence of create operations with is_default=true,
     * at most one default active policy exists per company at any time.
     *
     * Validates: Requirements 1.2, 1.3
     */
    #[Test]
    public function property1_at_most_one_default_policy_after_sequential_creates(): void
    {
        // Feature: return-policy-invoice-system, Property 1: uniqueness of default policy

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $count   = fake()->numberBetween(2, 8);

            for ($j = 0; $j < $count; $j++) {
                $this->service->create($company->id, $this->basePolicyData(['is_default' => true]));
            }

            $defaultCount = ReturnPolicy::where('company_id', $company->id)
                ->where('is_default', true)
                ->where('is_active', true)
                ->count();

            $this->assertLessThanOrEqual(
                1,
                $defaultCount,
                "Iteration {$i}: company {$company->id} has {$defaultCount} default policies after {$count} creates"
            );
        }
    }

    /**
     * Property 1: For any sequence of setAsDefault() calls, at most one default
     * active policy exists per company at any time.
     *
     * Validates: Requirements 1.2, 1.3
     */
    #[Test]
    public function property1_at_most_one_default_policy_after_set_as_default_calls(): void
    {
        // Feature: return-policy-invoice-system, Property 1: uniqueness of default policy

        for ($i = 0; $i < 100; $i++) {
            $company  = $this->makeCompany();
            $count    = fake()->numberBetween(2, 6);
            $policies = [];

            for ($j = 0; $j < $count; $j++) {
                $policies[] = $this->service->create($company->id, $this->basePolicyData());
            }

            // Randomly call setAsDefault on a random subset
            $callCount = fake()->numberBetween(1, $count);
            shuffle($policies);
            for ($k = 0; $k < $callCount; $k++) {
                $this->service->setAsDefault($policies[$k]);
            }

            $defaultCount = ReturnPolicy::where('company_id', $company->id)
                ->where('is_default', true)
                ->where('is_active', true)
                ->count();

            $this->assertLessThanOrEqual(
                1,
                $defaultCount,
                "Iteration {$i}: {$defaultCount} default policies after {$callCount} setAsDefault calls"
            );
        }
    }

    /**
     * Property 1: Default policies of different companies are independent.
     * Setting a default for company A must not affect company B.
     *
     * Validates: Requirements 1.3
     */
    #[Test]
    public function property1_default_policy_isolation_between_companies(): void
    {
        // Feature: return-policy-invoice-system, Property 1: uniqueness of default policy

        for ($i = 0; $i < 100; $i++) {
            $companyA = $this->makeCompany();
            $companyB = $this->makeCompany();

            $policyA = $this->service->create($companyA->id, $this->basePolicyData(['is_default' => true]));
            $policyB = $this->service->create($companyB->id, $this->basePolicyData(['is_default' => true]));

            // Create a new default for company A
            $this->service->create($companyA->id, $this->basePolicyData(['is_default' => true]));

            // Company B's default must remain unchanged
            $this->assertTrue(
                $policyB->fresh()->is_default,
                "Iteration {$i}: Company B default policy should not be affected by Company A operations"
            );

            $defaultCountB = ReturnPolicy::where('company_id', $companyB->id)
                ->where('is_default', true)
                ->count();

            $this->assertEquals(
                1,
                $defaultCountB,
                "Iteration {$i}: Company B should still have exactly 1 default policy"
            );
        }
    }

    /**
     * Property 1: update() with is_default=true preserves the single-default constraint.
     *
     * Validates: Requirements 1.6
     */
    #[Test]
    public function property1_update_preserves_single_default_constraint(): void
    {
        // Feature: return-policy-invoice-system, Property 1: uniqueness of default policy

        for ($i = 0; $i < 100; $i++) {
            $company  = $this->makeCompany();
            $count    = fake()->numberBetween(2, 5);
            $policies = [];

            for ($j = 0; $j < $count; $j++) {
                $policies[] = $this->service->create($company->id, $this->basePolicyData());
            }

            // Pick a random policy and update it to be default
            $target = $policies[array_rand($policies)];
            $this->service->update($target, ['is_default' => true]);

            $defaultCount = ReturnPolicy::where('company_id', $company->id)
                ->where('is_default', true)
                ->count();

            $this->assertEquals(
                1,
                $defaultCount,
                "Iteration {$i}: Exactly 1 default policy must exist after update(is_default=true)"
            );
        }
    }

    // =========================================================================
    // Property 2: policy binding at invoice creation
    // =========================================================================

    /**
     * Property 2: For any invoice, resolveForInvoice() always reads from
     * invoice.return_policy_id directly — no fallback to company default.
     *
     * Validates: Requirement 1.4
     */
    #[Test]
    public function property2_policy_always_read_from_invoice_return_policy_id(): void
    {
        // Feature: return-policy-invoice-system, Property 2: policy binding at invoice creation

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();

            // Create a company default policy
            $defaultPolicy = $this->service->create($company->id, $this->basePolicyData([
                'is_default' => true,
                'name'       => 'Default Policy',
            ]));

            // Create a different policy and bind it to the invoice
            $invoicePolicy = $this->service->create($company->id, $this->basePolicyData([
                'is_default' => false,
                'name'       => 'Invoice-Specific Policy',
            ]));

            $invoice  = $this->makeInvoice($company, $invoicePolicy);
            $resolved = $this->service->resolveForInvoice($invoice);

            $this->assertEquals(
                $invoicePolicy->id,
                $resolved->id,
                "Iteration {$i}: resolveForInvoice must return the policy on the invoice, not the company default"
            );

            $this->assertNotEquals(
                $defaultPolicy->id,
                $resolved->id,
                "Iteration {$i}: resolveForInvoice must NOT fall back to the company default"
            );
        }
    }

    /**
     * Property 2: The return_policy_id on an invoice never changes after creation.
     *
     * Validates: Requirement 1.4
     */
    #[Test]
    public function property2_invoice_return_policy_id_is_immutable_after_creation(): void
    {
        // Feature: return-policy-invoice-system, Property 2: policy binding at invoice creation

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();

            $policy1 = $this->service->create($company->id, $this->basePolicyData(['is_default' => true]));
            $policy2 = $this->service->create($company->id, $this->basePolicyData());

            $invoice = $this->makeInvoice($company, $policy1);

            // Capture the policy ID at creation time
            $originalPolicyId = $invoice->return_policy_id;

            // Change the company default to policy2 — should NOT affect the invoice
            $this->service->setAsDefault($policy2);

            // The invoice's return_policy_id must remain unchanged
            $this->assertEquals(
                $originalPolicyId,
                $invoice->fresh()->return_policy_id,
                "Iteration {$i}: invoice.return_policy_id must not change after creation"
            );
        }
    }

    // =========================================================================
    // Property 16: input validation rejects invalid policy fields
    // =========================================================================

    /**
     * Property 16: return_window_days must be a positive integer (min:1).
     * Any non-positive value must be rejected.
     *
     * Validates: Requirement 8.1
     */
    #[Test]
    public function property16_rejects_non_positive_return_window_days(): void
    {
        // Feature: return-policy-invoice-system, Property 16: input validation rejects invalid policy fields

        $invalidValues = [0, -1, -10, -100];

        for ($i = 0; $i < 100; $i++) {
            $invalidDay = $invalidValues[array_rand($invalidValues)];

            $data = $this->basePolicyData(['return_window_days' => $invalidDay]);
            $validator = $this->validatePolicyRules($data);

            $this->assertTrue(
                $validator->fails(),
                "Iteration {$i}: return_window_days={$invalidDay} should fail validation"
            );
            $this->assertArrayHasKey(
                'return_window_days',
                $validator->errors()->toArray(),
                "Iteration {$i}: validation error should be on return_window_days"
            );
        }
    }

    /**
     * Property 16: max_return_ratio must be between 0.01 and 1.00 inclusive.
     * Values outside this range must be rejected.
     *
     * Validates: Requirement 8.2
     */
    #[Test]
    public function property16_rejects_max_return_ratio_outside_valid_range(): void
    {
        // Feature: return-policy-invoice-system, Property 16: input validation rejects invalid policy fields

        for ($i = 0; $i < 100; $i++) {
            // Generate a value outside [0.01, 1.00]
            $invalidRatio = fake()->randomElement([
                0.0,
                -0.5,
                fake()->randomFloat(4, -10, 0.009),
                fake()->randomFloat(4, 1.001, 10),
            ]);

            $data      = $this->basePolicyData(['max_return_ratio' => $invalidRatio]);
            $validator = $this->validatePolicyRules($data);

            $this->assertTrue(
                $validator->fails(),
                "Iteration {$i}: max_return_ratio={$invalidRatio} should fail validation"
            );
            $this->assertArrayHasKey(
                'max_return_ratio',
                $validator->errors()->toArray(),
                "Iteration {$i}: validation error should be on max_return_ratio"
            );
        }
    }

    /**
     * Property 16: bonus_return_ratio must be between 0.00 and 1.00 when bonus_return_enabled=true.
     * Values outside this range must be rejected.
     *
     * Validates: Requirement 8.3
     */
    #[Test]
    public function property16_rejects_bonus_return_ratio_outside_valid_range_when_bonus_enabled(): void
    {
        // Feature: return-policy-invoice-system, Property 16: input validation rejects invalid policy fields

        for ($i = 0; $i < 100; $i++) {
            $invalidRatio = fake()->randomElement([
                -0.1,
                fake()->randomFloat(4, -5, -0.001),
                fake()->randomFloat(4, 1.001, 5),
            ]);

            $data = $this->basePolicyData([
                'bonus_return_enabled' => true,
                'bonus_return_ratio'   => $invalidRatio,
            ]);
            $validator = $this->validatePolicyRules($data);

            $this->assertTrue(
                $validator->fails(),
                "Iteration {$i}: bonus_return_ratio={$invalidRatio} with bonus_enabled=true should fail"
            );
            $this->assertArrayHasKey(
                'bonus_return_ratio',
                $validator->errors()->toArray(),
                "Iteration {$i}: validation error should be on bonus_return_ratio"
            );
        }
    }

    /**
     * Property 16: bonus_return_ratio is required when bonus_return_enabled=true.
     *
     * Validates: Requirement 8.3
     */
    #[Test]
    public function property16_rejects_missing_bonus_return_ratio_when_bonus_enabled(): void
    {
        // Feature: return-policy-invoice-system, Property 16: input validation rejects invalid policy fields

        for ($i = 0; $i < 100; $i++) {
            $data = $this->basePolicyData([
                'bonus_return_enabled' => true,
                'bonus_return_ratio'   => null,
            ]);
            $validator = $this->validatePolicyRules($data);

            $this->assertTrue(
                $validator->fails(),
                "Iteration {$i}: null bonus_return_ratio with bonus_enabled=true should fail"
            );
        }
    }

    /**
     * Property 16: min_days_before_expiry must be a non-negative integer.
     * Negative values must be rejected.
     *
     * Validates: Requirement 8.4
     */
    #[Test]
    public function property16_rejects_negative_min_days_before_expiry(): void
    {
        // Feature: return-policy-invoice-system, Property 16: input validation rejects invalid policy fields

        for ($i = 0; $i < 100; $i++) {
            $invalidDays = fake()->numberBetween(-100, -1);

            $data      = $this->basePolicyData(['min_days_before_expiry' => $invalidDays]);
            $validator = $this->validatePolicyRules($data);

            $this->assertTrue(
                $validator->fails(),
                "Iteration {$i}: min_days_before_expiry={$invalidDays} should fail validation"
            );
            $this->assertArrayHasKey(
                'min_days_before_expiry',
                $validator->errors()->toArray(),
                "Iteration {$i}: validation error should be on min_days_before_expiry"
            );
        }
    }

    /**
     * Property 16: Valid policy data must always pass validation.
     *
     * Validates: Requirements 8.1, 8.2, 8.3, 8.4
     */
    #[Test]
    public function property16_accepts_valid_policy_fields(): void
    {
        // Feature: return-policy-invoice-system, Property 16: input validation rejects invalid policy fields

        for ($i = 0; $i < 100; $i++) {
            $bonusEnabled = fake()->boolean();
            $data = [
                'name'                       => 'Policy ' . Str::random(5),
                'return_window_days'         => fake()->numberBetween(1, 365),
                'max_return_ratio'           => fake()->randomFloat(4, 0.01, 1.00),
                'bonus_return_enabled'       => $bonusEnabled,
                'bonus_return_ratio'         => $bonusEnabled ? fake()->randomFloat(4, 0.00, 1.00) : null,
                'discount_deduction_enabled' => fake()->boolean(),
                'min_days_before_expiry'     => fake()->numberBetween(0, 90),
                'is_default'                 => fake()->boolean(),
                'is_active'                  => fake()->boolean(),
            ];

            $validator = $this->validatePolicyRules($data);

            $this->assertFalse(
                $validator->fails(),
                "Iteration {$i}: valid data should pass validation. Errors: " .
                json_encode($validator->errors()->toArray())
            );
        }
    }
}
