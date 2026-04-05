<?php

namespace App\Services;

use App\Exceptions\ReturnInvoice\BonusReturnExceededException;
use App\Exceptions\ReturnInvoice\DuplicateReturnException;
use App\Exceptions\ReturnInvoice\ExpiryTooCloseException;
use App\Exceptions\ReturnInvoice\InvoiceNotPaidException;
use App\Exceptions\ReturnInvoice\QuantityExceededException;
use App\Exceptions\ReturnInvoice\ReturnRatioExceededException;
use App\Exceptions\ReturnInvoice\ReturnWindowExpiredException;
use App\Models\Invoice;
use App\Models\ReturnInvoice;
use App\Models\ReturnPolicy;
use Carbon\Carbon;

class ReturnRequestValidator
{
    /**
     * Assert that no return invoice already exists for the given original invoice.
     * Throws DuplicateReturnException (HTTP 409) if a duplicate is found.
     *
     * Validates: Requirements 3.1, 3.2
     */
    public function assertNoDuplicateReturn(int $originalInvoiceId): void
    {
        if (ReturnInvoice::where('original_invoice_id', $originalInvoiceId)->exists()) {
            throw new DuplicateReturnException();
        }
    }

    /**
     * Assert that the invoice is in "paid" status.
     * Throws InvoiceNotPaidException (HTTP 422) if not paid.
     *
     * Validates: Requirements 3.3, 3.4
     */
    public function assertInvoicePaid(Invoice $invoice): void
    {
        if ($invoice->status !== 'paid') {
            throw new InvoiceNotPaidException();
        }
    }

    /**
     * Assert that the return request is within the allowed return window.
     * Throws ReturnWindowExpiredException (HTTP 422) if expired.
     *
     * Validates: Requirements 3.6, 3.7
     */
    public function assertWithinReturnWindow(Invoice $invoice, ReturnPolicy $policy): void
    {
        $elapsedDays = $invoice->issued_at->diffInDays(Carbon::now());

        if ($elapsedDays > $policy->return_window_days) {
            throw new ReturnWindowExpiredException();
        }
    }

    /**
     * Assert that each returned item quantity does not exceed the original quantity.
     * Throws QuantityExceededException (HTTP 422) if exceeded.
     *
     * Validates: Requirements 3.10, 3.11
     */
    public function assertItemQuantities(array $items, Invoice $invoice): void
    {
        // Build a map of invoice_item_id => original qty for fast lookup
        $originalQtyMap = $invoice->items->pluck('qty', 'id');

        foreach ($items as $item) {
            $invoiceItemId = $item['invoice_item_id'];
            $returnedQty   = $item['quantity'];

            $originalQty = $originalQtyMap->get($invoiceItemId);

            if ($originalQty === null || $returnedQty > $originalQty) {
                throw new QuantityExceededException();
            }
        }
    }

    /**
     * Assert that the total return ratio does not exceed the policy maximum.
     * Throws ReturnRatioExceededException (HTTP 422) if exceeded.
     *
     * Return_Ratio = total_returned_quantity ÷ total_original_quantity
     * across ALL items in the original invoice (not just the returned ones).
     *
     * Validates: Requirements 3.12, 3.13, 6.4
     */
    public function assertReturnRatio(array $items, Invoice $invoice, ReturnPolicy $policy): void
    {
        $totalOriginal = $invoice->items->sum('qty');

        if ($totalOriginal <= 0) {
            return;
        }

        $totalReturned = array_sum(array_column($items, 'quantity'));

        $ratio = $totalReturned / $totalOriginal;

        if ($ratio > $policy->max_return_ratio) {
            throw new ReturnRatioExceededException();
        }
    }

    /**
     * Assert that expiry dates of returned items meet the policy minimum days requirement.
     * Skips the check entirely when min_days_before_expiry = 0 (Requirement 1.7).
     * Throws ExpiryTooCloseException (HTTP 422) if days_to_expiry < min_days_before_expiry.
     *
     * Validates: Requirements 1.7, 3.8, 3.9
     */
    public function assertExpiryDates(array $items, ReturnPolicy $policy): void
    {
        // Requirement 1.7: zero means expiry validation is disabled
        if ($policy->min_days_before_expiry === 0) {
            return;
        }

        $today = Carbon::today();

        foreach ($items as $item) {
            $expiryDate = $item['expiry_date'] ?? null;

            // Items without an expiry date are not subject to expiry validation
            if ($expiryDate === null) {
                continue;
            }

            $daysToExpiry = $today->diffInDays(Carbon::parse($expiryDate), false);

            if ($daysToExpiry < $policy->min_days_before_expiry) {
                throw new ExpiryTooCloseException();
            }
        }
    }

    /**
     * Assert that returned bonus quantities do not exceed the allowed bonus return ratio.
     * Skips the check when bonus_return_enabled = false.
     * Throws BonusReturnExceededException (HTTP 422) if exceeded.
     *
     * Validates: Requirements 3.14, 3.15
     */
    public function assertBonusQuantities(array $items, Invoice $invoice, ReturnPolicy $policy): void
    {
        if (! $policy->bonus_return_enabled) {
            return;
        }

        // Build a map of invoice_item_id => original invoice item for fast lookup
        $invoiceItemsMap = $invoice->items->keyBy('id');

        // Calculate original bonus quantity across all invoice items
        $originalBonusQty = $invoice->items
            ->where('is_bonus', true)
            ->sum('qty');

        if ($originalBonusQty <= 0) {
            return;
        }

        // Calculate total returned bonus quantity from the request
        $returnedBonusQty = 0;
        foreach ($items as $item) {
            $invoiceItemId = $item['invoice_item_id'];
            $invoiceItem   = $invoiceItemsMap->get($invoiceItemId);

            if ($invoiceItem && $invoiceItem->is_bonus) {
                $returnedBonusQty += $item['quantity'];
            }
        }

        $allowedBonusQty = $policy->bonus_return_ratio * $originalBonusQty;

        if ($returnedBonusQty > $allowedBonusQty) {
            throw new BonusReturnExceededException();
        }
    }
}
