<?php

namespace App\Services;

use App\Contracts\ReturnInvoiceServiceInterface;
use App\Exceptions\ReturnInvoice\DuplicateReturnException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ReturnInvoice;
use App\Models\ReturnInvoiceItem;
use App\Models\ReturnPolicy;
use App\Repositories\ReturnInvoiceRepository;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReturnInvoiceService implements ReturnInvoiceServiceInterface
{
    public function __construct(
        private readonly ReturnRequestValidator $validator,
        private readonly ReturnRefundCalculator $calculator,
        private readonly ReturnInvoiceRepository $repository,
    ) {}

    /**
     * Validate a return request against all business rules.
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
    public function validate(Invoice $invoice, array $items, ReturnPolicy $policy): void
    {
        $this->validator->assertInvoicePaid($invoice);
        $this->validator->assertNoDuplicateReturn($invoice->id);
        $this->validator->assertWithinReturnWindow($invoice, $policy);
        $this->validator->assertItemQuantities($items, $invoice);
        $this->validator->assertExpiryDates($items, $policy);
        $this->validator->assertReturnRatio($items, $invoice, $policy);
        $this->validator->assertBonusQuantities($items, $invoice, $policy);
    }

    /**
     * Calculate refund amounts for each returned item.
     *
     * Returns an array keyed by invoice_item_id => refund_amount.
     *
     * For fixed-discount items the proportional distribution is applied across
     * all invoice items so that the discount is spread correctly.
     *
     * Validates: Requirements 4.1–4.7
     */
    public function calculateRefunds(array $items, ReturnPolicy $policy): array
    {
        $refunds = [];

        foreach ($items as $item) {
            $invoiceItemId = $item['invoice_item_id'];
            $returnedQty   = (int) $item['quantity'];

            /** @var InvoiceItem $invoiceItem */
            $invoiceItem = InvoiceItem::findOrFail($invoiceItemId);

            // For fixed discounts we need all sibling items to distribute proportionally
            if (
                $policy->discount_deduction_enabled
                && $invoiceItem->discount_type === 'fixed'
            ) {
                $allItems = $invoiceItem->invoice->items;

                $refundAmount = $this->calculator->calculateItemRefundWithDistribution(
                    $invoiceItem,
                    $returnedQty,
                    $policy,
                    $allItems,
                );
            } else {
                $refundAmount = $this->calculator->calculateItemRefund(
                    $invoiceItem,
                    $returnedQty,
                    $policy,
                );
            }

            $refunds[$invoiceItemId] = $refundAmount;
        }

        return $refunds;
    }

    /**
     * Create a return invoice after all validations have passed.
     *
     * Uses a DB transaction with pessimistic locking on the original invoice
     * to prevent concurrent duplicate returns. A second check inside the
     * transaction guards against race conditions that slip past the initial
     * assertNoDuplicateReturn validation.
     *
     * Validates: Requirements 3.1, 3.2, 4.5, 4.6, 4.7, 5.1, 5.2, 5.3, 5.5
     */
    public function create(Invoice $invoice, array $items, ReturnPolicy $policy): ReturnInvoice
    {
        try {
            return DB::transaction(function () use ($invoice, $items, $policy) {
                // Lock the original invoice row to prevent concurrent return creation
                Invoice::lockForUpdate()->findOrFail($invoice->id);

                // Re-check for duplicate inside the transaction (race condition guard)
                if (ReturnInvoice::where('original_invoice_id', $invoice->id)->exists()) {
                    throw new DuplicateReturnException();
                }

                // Calculate refund amounts keyed by invoice_item_id
                $refunds = $this->calculateRefunds($items, $policy);

                // Sum all refund amounts for total_refund_amount
                $totalRefund = round(array_sum($refunds), 4, PHP_ROUND_HALF_UP);

                // Resolve company_id via the invoice's order
                $companyId = $invoice->order->company_user_id;

                // Create the return_invoices record
                $returnInvoice = ReturnInvoice::create([
                    'original_invoice_id' => $invoice->id,
                    'company_id'          => $companyId,
                    'return_policy_id'    => $policy->id,
                    'total_refund_amount' => $totalRefund,
                    'status'              => 'pending',
                ]);

                // Create a return_invoice_items record for each returned item
                foreach ($items as $item) {
                    $invoiceItemId = $item['invoice_item_id'];
                    $returnedQty   = (int) $item['quantity'];

                    /** @var InvoiceItem $invoiceItem */
                    $invoiceItem = InvoiceItem::findOrFail($invoiceItemId);

                    ReturnInvoiceItem::create([
                        'return_invoice_id'       => $returnInvoice->id,
                        'original_item_id'        => $invoiceItemId,
                        'returned_quantity'       => $returnedQty,
                        'unit_price_snapshot'     => $invoiceItem->unit_price_snapshot,
                        'discount_type_snapshot'  => $invoiceItem->discount_type,
                        'discount_value_snapshot' => $invoiceItem->discount_value,
                        'expiry_date_snapshot'    => $invoiceItem->expiry_date,
                        'is_bonus'                => $invoiceItem->is_bonus,
                        'refund_amount'           => $refunds[$invoiceItemId],
                    ]);
                }

                return $returnInvoice;
            });
        } catch (QueryException $e) {
            // MySQL error 1062 = Duplicate entry (unique constraint violation)
            if ($e->errorInfo[1] === 1062) {
                throw new DuplicateReturnException();
            }
            throw $e;
        }
    }

    /**
     * Find a return invoice by ID, scoped to the given company.
     *
     * Eager-loads `items`. Throws ModelNotFoundException if the record does
     * not exist or belongs to a different company (Requirement 7.4).
     *
     * Validates: Requirements 7.1, 7.4
     */
    public function findById(int $id, int $companyId): ReturnInvoice
    {
        return $this->repository->findForCompany($id, $companyId);
    }

    /**
     * List return invoices for a company with pagination.
     *
     * Eager-loads `items` and `originalInvoice`, ordered by newest first.
     *
     * Validates: Requirements 7.2, 7.4
     */
    public function listForCompany(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateForCompany($companyId, $perPage);
    }
}
