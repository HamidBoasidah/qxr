<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\ReturnPolicy;

interface ReturnPolicyServiceInterface
{
    /**
     * Create a new return policy for a company.
     * If is_default = true, all other active policies for the same company
     * will be set to is_default = false inside a DB transaction with locking.
     */
    public function create(int $companyId, array $data): ReturnPolicy;

    /**
     * Update an existing return policy while preserving the single-default constraint.
     */
    public function update(ReturnPolicy $policy, array $data): ReturnPolicy;

    /**
     * Resolve the return policy for a given invoice.
     * Reads directly from invoice->return_policy_id — NO fallback logic.
     *
     * @throws \App\Exceptions\ReturnPolicy\NoPolicyFoundException if policy not found or inactive
     */
    public function resolveForInvoice(Invoice $invoice): ReturnPolicy;

    /**
     * Set a policy as the default for its company.
     * Unsets the previous default inside a DB transaction with row-level locking.
     */
    public function setAsDefault(ReturnPolicy $policy): void;
}
