<?php

namespace App\Services;

use App\Contracts\ReturnPolicyServiceInterface;
use App\Exceptions\ReturnPolicy\NoPolicyFoundException;
use App\Exceptions\ReturnPolicy\PolicyInUseException;
use App\Models\Invoice;
use App\Models\ReturnPolicy;
use App\Repositories\ReturnPolicyRepository;
use Illuminate\Support\Facades\DB;

class ReturnPolicyService implements ReturnPolicyServiceInterface
{
    public function __construct(
        private readonly ReturnPolicyRepository $repository,
    ) {}

    /**
     * Create a new return policy for a company.
     *
     * If is_default = true, all other active policies for the same company
     * are set to is_default = false inside a DB transaction with row-level locking.
     *
     * Validates: Requirements 1.1, 1.2, 1.3
     */
    public function create(int $companyId, array $data): ReturnPolicy
    {
        $isDefault = (bool) ($data['is_default'] ?? false);

        return DB::transaction(function () use ($companyId, $data, $isDefault) {
            if ($isDefault) {
                // Acquire row-level lock on all company policies to prevent concurrent default conflicts
                ReturnPolicy::where('company_id', $companyId)->lockForUpdate()->get();

                // Unset any existing default for this company
                ReturnPolicy::where('company_id', $companyId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return ReturnPolicy::create(array_merge($data, [
                'company_id' => $companyId,
                'is_default' => $isDefault,
            ]));
        });
    }

    /**
     * Update an existing return policy while preserving the single-default constraint.
     *
     * Rejects the update with PolicyInUseException (HTTP 422) if any invoice is
     * currently linked to this policy via invoices.return_policy_id.
     *
     * If is_default is being set to true, unsets the previous default inside a
     * DB transaction with row-level locking.
     *
     * Validates: Requirements 1.2, 1.3, 1.6, 1.8, 1.9
     *
     * @throws PolicyInUseException if the policy is linked to existing invoices
     */
    public function update(ReturnPolicy $policy, array $data): ReturnPolicy
    {
        if ($policy->invoices()->exists()) {
            throw new PolicyInUseException();
        }

        $isDefault = isset($data['is_default']) ? (bool) $data['is_default'] : $policy->is_default;

        return DB::transaction(function () use ($policy, $data, $isDefault) {
            $companyId = $policy->company_id;

            if ($isDefault) {
                // Acquire row-level lock on all company policies
                ReturnPolicy::where('company_id', $companyId)->lockForUpdate()->get();

                // Unset any existing default (excluding the current policy)
                ReturnPolicy::where('company_id', $companyId)
                    ->where('is_default', true)
                    ->where('id', '!=', $policy->id)
                    ->update(['is_default' => false]);
            }

            $policy->update(array_merge($data, ['is_default' => $isDefault]));

            return $policy->fresh();
        });
    }

    /**
     * Set a policy as the default for its company.
     *
     * Unsets the previous default and sets the given policy as default,
     * all inside a DB transaction with row-level locking.
     *
     * Validates: Requirements 1.2, 1.3
     */
    public function setAsDefault(ReturnPolicy $policy): void
    {
        $companyId = $policy->company_id;

        DB::transaction(function () use ($companyId, $policy) {
            // Acquire row-level lock on all company policies to prevent concurrent default conflicts
            ReturnPolicy::where('company_id', $companyId)->lockForUpdate()->get();

            // Unset the current default
            ReturnPolicy::where('company_id', $companyId)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            // Set the new default
            ReturnPolicy::where('id', $policy->id)
                ->update(['is_default' => true]);
        });
    }

    /**
     * Resolve the return policy for a given invoice.
     *
     * Reads directly from invoice->return_policy_id — NO fallback logic.
     * The policy must exist and be active.
     *
     * Validates: Requirements 1.4, 1.5
     *
     * @throws NoPolicyFoundException if the policy is not found or not active
     */
    public function resolveForInvoice(Invoice $invoice): ReturnPolicy
    {
        $policy = ReturnPolicy::where('id', $invoice->return_policy_id)
            ->where('is_active', true)
            ->first();

        if ($policy === null) {
            throw new NoPolicyFoundException();
        }

        return $policy;
    }
}
