<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\ReturnInvoice;
use App\Models\ReturnPolicy;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReturnInvoiceServiceInterface
{
    /**
     * Validate a return request against all business rules.
     * Throws a domain exception on the first failing rule.
     *
     * Validation order (from design.md flow diagram):
     *   1. assertInvoicePaid
     *   2. assertNoDuplicateReturn
     *   3. assertWithinReturnWindow
     *   4. assertItemQuantities
     *   5. assertExpiryDates
     *   6. assertReturnRatio
     *   7. assertBonusQuantities
     *
     * Validates: Requirements 3.1–3.15
     */
    public function validate(Invoice $invoice, array $items, ReturnPolicy $policy): void;

    /**
     * Calculate refund amounts for each returned item.
     *
     * Returns an array keyed by invoice_item_id with the calculated refund_amount.
     *
     * Validates: Requirements 4.1–4.7
     */
    public function calculateRefunds(array $items, ReturnPolicy $policy): array;

    /**
     * Create a return invoice after all validations have passed.
     *
     * Validates: Requirements 5.1–5.5
     */
    public function create(Invoice $invoice, array $items, ReturnPolicy $policy): ReturnInvoice;

    /**
     * Find a return invoice by ID, scoped to the given company.
     *
     * Validates: Requirements 7.1, 7.4
     */
    public function findById(int $id, int $companyId): ReturnInvoice;

    /**
     * List return invoices for a company with pagination.
     *
     * Validates: Requirements 7.2, 7.4
     */
    public function listForCompany(int $companyId, int $perPage = 15): LengthAwarePaginator;
}
