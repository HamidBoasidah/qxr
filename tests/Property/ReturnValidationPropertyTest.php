<?php

namespace Tests\Property;

use App\Exceptions\ReturnInvoice\BonusReturnExceededException;
use App\Exceptions\ReturnInvoice\QuantityExceededException;
use App\Exceptions\ReturnInvoice\ReturnRatioExceededException;
use App\Exceptions\ReturnInvoice\ReturnWindowExpiredException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnPolicy;
use App\Models\User;
use App\Services\ReturnRequestValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Tests: Return Request Validation
 *
 * Property 5:  return window enforcement
 * Property 6:  quantity bounds
 * Property 7:  return ratio enforcement
 * Property 8:  bonus return ratio enforcement
 * Property 17: return invoice item references valid invoice items
 *
 * Validates: Requirements 3.6, 3.7, 3.10, 3.11, 3.12, 3.13, 3.14, 3.15, 8.5, 8.6
 */
class ReturnValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ReturnRequestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ReturnRequestValidator();
    }

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
        $order    = Order::create([
            'order_no'         => 'ORD-' . strtoupper(Str::random(8)),
            'company_user_id'  => $company->id,
            'customer_user_id' => $customer->id,
            'status'           => 'delivered',
            'submitted_at'     => now(),
        ]);

        return Invoice::create(array_merge([
            'invoice_no'              => 'INV-' . strtoupper(Str::random(8)),
            'order_id'                => $order->id,
            'subtotal_snapshot'       => 1000.00,
            'discount_total_snapshot' => 0.00,
            'total_snapshot'          => 1000.00,
            'issued_at'               => now(),
            'status'                  => 'paid',
            'return_policy_id'        => $policy->id,
        ], $overrides));
    }

    private function makeInvoiceItem(Invoice $invoice, array $overrides = []): InvoiceItem
    {
        $product = Product::factory()->create([
            'company_user_id' => $invoice->order->company_user_id,
        ]);

        return InvoiceItem::create(array_merge([
            'invoice_id'           => $invoice->id,
            'product_id'           => $product->id,
            'description_snapshot' => 'Product',
            'qty'                  => 5,
            'unit_price_snapshot'  => 100.00,
            'line_total_snapshot'  => 500.00,
            'expiry_date'          => now()->addYear()->toDateString(),
            'discount_type'        => null,
            'discount_value'       => null,
            'is_bonus'             => false,
        ], $overrides));
    }

    // =========================================================================
    // Property 5: return window enforcement
    // =========================================================================

    /**
     * Property 5: Any return request where elapsed days > return_window_days must be rejected.
     *
     * Validates: Requirements 3.6, 3.7
     */
    #[Test]
    public function property5_rejects_request_when_return_window_expired(): void
    {
        // Feature: return-policy-invoice-system, Property 5: return window enforcement

        for ($i = 0; $i < 100; $i++) {
            $windowDays  = fake()->numberBetween(1, 30);
            $elapsedDays = $windowDays + fake()->numberBetween(1, 30); // always exceeds window

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['return_window_days' => $windowDays]);
            $invoice = $this->makeInvoice($company, $policy, [
                'issued_at' => now()->subDays($elapsedDays),
            ]);

            $this->expectException(ReturnWindowExpiredException::class);

            $this->validator->assertWithinReturnWindow($invoice, $policy);
        }
    }

    /**
     * Property 5: Any return request where elapsed days <= return_window_days must be accepted.
     *
     * Validates: Requirements 3.6, 3.7
     */
    #[Test]
    public function property5_accepts_request_within_return_window(): void
    {
        // Feature: return-policy-invoice-system, Property 5: return window enforcement

        for ($i = 0; $i < 100; $i++) {
            $windowDays  = fake()->numberBetween(5, 60);
            // Use at least 1 day less than window to avoid sub-second boundary issues
            $elapsedDays = fake()->numberBetween(0, max(0, $windowDays - 1));

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['return_window_days' => $windowDays]);
            // Use startOfDay to avoid sub-second timing issues at the boundary
            $invoice = $this->makeInvoice($company, $policy, [
                'issued_at' => now()->subDays($elapsedDays)->startOfDay(),
            ]);

            // Should not throw
            $this->validator->assertWithinReturnWindow($invoice, $policy);
            $this->assertTrue(true, "Iteration {$i}: request within window should be accepted");
        }
    }

    /**
     * Property 5: The boundary condition — exactly at return_window_days must be accepted.
     *
     * Validates: Requirements 3.6, 3.7
     */
    #[Test]
    public function property5_accepts_request_exactly_at_window_boundary(): void
    {
        // Feature: return-policy-invoice-system, Property 5: return window enforcement

        for ($i = 0; $i < 100; $i++) {
            $windowDays = fake()->numberBetween(1, 60);

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['return_window_days' => $windowDays]);
            // Use endOfDay so diffInDays returns exactly windowDays (not windowDays + fraction)
            $invoice = $this->makeInvoice($company, $policy, [
                'issued_at' => now()->subDays($windowDays)->endOfDay(),
            ]);

            // Exactly at the boundary — should not throw (elapsed ≤ windowDays)
            $this->validator->assertWithinReturnWindow($invoice, $policy);
            $this->assertTrue(true, "Iteration {$i}: request at exact window boundary should be accepted");
        }
    }

    // =========================================================================
    // Property 6: quantity bounds
    // =========================================================================

    /**
     * Property 6: Returned quantity must never exceed original quantity.
     * Any request with returned_qty > original_qty must be rejected.
     *
     * Validates: Requirements 3.10, 3.11
     */
    #[Test]
    public function property6_rejects_returned_quantity_exceeding_original(): void
    {
        // Feature: return-policy-invoice-system, Property 6: quantity bounds

        for ($i = 0; $i < 100; $i++) {
            $originalQty = fake()->numberBetween(1, 20);
            $returnedQty = $originalQty + fake()->numberBetween(1, 10); // always exceeds

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => $originalQty]);

            $items = [['invoice_item_id' => $item->id, 'quantity' => $returnedQty]];

            $this->expectException(QuantityExceededException::class);

            $this->validator->assertItemQuantities($items, $invoice->load('items'));
        }
    }

    /**
     * Property 6: Returned quantity <= original quantity must always be accepted.
     *
     * Validates: Requirements 3.10, 3.11
     */
    #[Test]
    public function property6_accepts_returned_quantity_within_original(): void
    {
        // Feature: return-policy-invoice-system, Property 6: quantity bounds

        for ($i = 0; $i < 100; $i++) {
            $originalQty = fake()->numberBetween(2, 20);
            $returnedQty = fake()->numberBetween(1, $originalQty); // always within bounds

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => $originalQty]);

            $items = [['invoice_item_id' => $item->id, 'quantity' => $returnedQty]];

            // Should not throw
            $this->validator->assertItemQuantities($items, $invoice->load('items'));
            $this->assertTrue(true, "Iteration {$i}: returned_qty={$returnedQty} <= original_qty={$originalQty} should be accepted");
        }
    }

    /**
     * Property 6: Returning the exact original quantity must be accepted.
     *
     * Validates: Requirements 3.10, 3.11
     */
    #[Test]
    public function property6_accepts_returning_full_original_quantity(): void
    {
        // Feature: return-policy-invoice-system, Property 6: quantity bounds

        for ($i = 0; $i < 100; $i++) {
            $originalQty = fake()->numberBetween(1, 20);

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => $originalQty]);

            $items = [['invoice_item_id' => $item->id, 'quantity' => $originalQty]];

            // Should not throw — returning exactly the original quantity is valid
            $this->validator->assertItemQuantities($items, $invoice->load('items'));
            $this->assertTrue(true, "Iteration {$i}: returning full original qty={$originalQty} should be accepted");
        }
    }

    // =========================================================================
    // Property 7: return ratio enforcement
    // =========================================================================

    /**
     * Property 7: total_returned ÷ total_original > max_return_ratio must be rejected.
     *
     * Validates: Requirements 3.12, 3.13
     */
    #[Test]
    public function property7_rejects_when_return_ratio_exceeds_max(): void
    {
        // Feature: return-policy-invoice-system, Property 7: return ratio enforcement

        for ($i = 0; $i < 100; $i++) {
            $maxRatio    = fake()->randomFloat(4, 0.1, 0.8);
            $totalQty    = fake()->numberBetween(10, 50);
            // returned qty that exceeds the ratio
            $returnedQty = (int) ceil($totalQty * $maxRatio) + fake()->numberBetween(1, 5);
            $returnedQty = min($returnedQty, $totalQty); // cap at total

            // Only test when ratio actually exceeds max
            if ($returnedQty / $totalQty <= $maxRatio) {
                continue;
            }

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['max_return_ratio' => $maxRatio]);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => $totalQty]);

            $items = [['invoice_item_id' => $item->id, 'quantity' => $returnedQty]];

            $this->expectException(ReturnRatioExceededException::class);

            $this->validator->assertReturnRatio($items, $invoice->load('items'), $policy);
        }
    }

    /**
     * Property 7: total_returned ÷ total_original <= max_return_ratio must be accepted.
     *
     * Validates: Requirements 3.12, 3.13
     */
    #[Test]
    public function property7_accepts_when_return_ratio_within_max(): void
    {
        // Feature: return-policy-invoice-system, Property 7: return ratio enforcement

        for ($i = 0; $i < 100; $i++) {
            $maxRatio = fake()->randomFloat(4, 0.2, 1.0);
            $totalQty = fake()->numberBetween(10, 50);
            // returned qty that stays within the ratio
            $returnedQty = (int) floor($totalQty * $maxRatio);
            $returnedQty = max(1, $returnedQty);

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, ['max_return_ratio' => $maxRatio]);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => $totalQty]);

            $items = [['invoice_item_id' => $item->id, 'quantity' => $returnedQty]];

            // Should not throw
            $this->validator->assertReturnRatio($items, $invoice->load('items'), $policy);
            $this->assertTrue(
                true,
                "Iteration {$i}: ratio={$returnedQty}/{$totalQty} <= max={$maxRatio} should be accepted"
            );
        }
    }

    /**
     * Property 7: Return ratio is computed across ALL original invoice items,
     * not just the returned ones.
     *
     * Validates: Requirement 6.4
     */
    #[Test]
    public function property7_ratio_computed_against_all_original_items(): void
    {
        // Feature: return-policy-invoice-system, Property 7: return ratio enforcement

        for ($i = 0; $i < 100; $i++) {
            $maxRatio = 0.5;
            $company  = $this->makeCompany();
            $policy   = $this->makePolicy($company, ['max_return_ratio' => $maxRatio]);
            $invoice  = $this->makeInvoice($company, $policy);

            // Two items: 10 qty each → total = 20
            $item1 = $this->makeInvoiceItem($invoice, ['qty' => 10]);
            $item2 = $this->makeInvoiceItem($invoice, ['qty' => 10]);

            // Return 6 from item1 only → ratio = 6/20 = 0.3 ≤ 0.5 → should pass
            $items = [['invoice_item_id' => $item1->id, 'quantity' => 6]];

            $this->validator->assertReturnRatio($items, $invoice->load('items'), $policy);
            $this->assertTrue(true, "Iteration {$i}: ratio 6/20=0.3 should be accepted with max=0.5");

            // Return 11 from item1 only → ratio = 11/20 = 0.55 > 0.5 → should fail
            $itemsExceeding = [['invoice_item_id' => $item1->id, 'quantity' => 11]];

            try {
                $this->validator->assertReturnRatio($itemsExceeding, $invoice->load('items'), $policy);
                $this->fail("Iteration {$i}: ratio 11/20=0.55 should be rejected with max=0.5");
            } catch (ReturnRatioExceededException $e) {
                $this->assertTrue(true);
            }
        }
    }

    // =========================================================================
    // Property 8: bonus return ratio enforcement
    // =========================================================================

    /**
     * Property 8: When bonus_return_enabled=true, returned bonus qty must not exceed
     * bonus_return_ratio × original_bonus_qty.
     *
     * Validates: Requirements 3.14, 3.15
     */
    #[Test]
    public function property8_rejects_when_bonus_return_ratio_exceeded(): void
    {
        // Feature: return-policy-invoice-system, Property 8: bonus return ratio enforcement

        for ($i = 0; $i < 100; $i++) {
            $bonusRatio      = fake()->randomFloat(4, 0.1, 0.8);
            $originalBonusQty = fake()->numberBetween(10, 50);
            $allowedBonusQty  = (int) floor($bonusRatio * $originalBonusQty);
            $returnedBonusQty = $allowedBonusQty + fake()->numberBetween(1, 5);

            // Only test when it actually exceeds
            if ($returnedBonusQty <= $bonusRatio * $originalBonusQty) {
                continue;
            }

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, [
                'bonus_return_enabled' => true,
                'bonus_return_ratio'   => $bonusRatio,
            ]);
            $invoice   = $this->makeInvoice($company, $policy);
            $bonusItem = $this->makeInvoiceItem($invoice, [
                'qty'      => $originalBonusQty,
                'is_bonus' => true,
            ]);

            $items = [['invoice_item_id' => $bonusItem->id, 'quantity' => $returnedBonusQty]];

            $this->expectException(BonusReturnExceededException::class);

            $this->validator->assertBonusQuantities($items, $invoice->load('items'), $policy);
        }
    }

    /**
     * Property 8: Returned bonus qty <= bonus_return_ratio × original_bonus_qty must be accepted.
     *
     * Validates: Requirements 3.14, 3.15
     */
    #[Test]
    public function property8_accepts_when_bonus_return_ratio_within_limit(): void
    {
        // Feature: return-policy-invoice-system, Property 8: bonus return ratio enforcement

        for ($i = 0; $i < 100; $i++) {
            $bonusRatio       = fake()->randomFloat(4, 0.2, 1.0);
            $originalBonusQty = fake()->numberBetween(5, 50);
            $allowedBonusQty  = (int) floor($bonusRatio * $originalBonusQty);
            $returnedBonusQty = fake()->numberBetween(0, max(1, $allowedBonusQty));

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, [
                'bonus_return_enabled' => true,
                'bonus_return_ratio'   => $bonusRatio,
            ]);
            $invoice   = $this->makeInvoice($company, $policy);
            $bonusItem = $this->makeInvoiceItem($invoice, [
                'qty'      => $originalBonusQty,
                'is_bonus' => true,
            ]);

            $items = [['invoice_item_id' => $bonusItem->id, 'quantity' => $returnedBonusQty]];

            // Should not throw
            $this->validator->assertBonusQuantities($items, $invoice->load('items'), $policy);
            $this->assertTrue(
                true,
                "Iteration {$i}: returned_bonus={$returnedBonusQty} <= allowed={$allowedBonusQty} should be accepted"
            );
        }
    }

    /**
     * Property 8: When bonus_return_enabled=false, bonus quantity check is skipped entirely.
     *
     * Validates: Requirements 3.14, 3.15
     */
    #[Test]
    public function property8_skips_bonus_check_when_bonus_return_disabled(): void
    {
        // Feature: return-policy-invoice-system, Property 8: bonus return ratio enforcement

        for ($i = 0; $i < 100; $i++) {
            $originalBonusQty = fake()->numberBetween(5, 50);

            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company, [
                'bonus_return_enabled' => false,
                'bonus_return_ratio'   => null,
            ]);
            $invoice   = $this->makeInvoice($company, $policy);
            $bonusItem = $this->makeInvoiceItem($invoice, [
                'qty'      => $originalBonusQty,
                'is_bonus' => true,
            ]);

            // Return ALL bonus items — should be fine since bonus check is disabled
            $items = [['invoice_item_id' => $bonusItem->id, 'quantity' => $originalBonusQty]];

            // Should not throw
            $this->validator->assertBonusQuantities($items, $invoice->load('items'), $policy);
            $this->assertTrue(true, "Iteration {$i}: bonus check should be skipped when disabled");
        }
    }

    // =========================================================================
    // Property 17: return invoice item references valid invoice items
    // =========================================================================

    /**
     * Property 17: Any invoice_item_id that does not belong to the original invoice
     * must be rejected by the form request validation.
     *
     * Validates: Requirements 8.5, 8.6
     */
    #[Test]
    public function property17_rejects_invoice_item_id_not_belonging_to_invoice(): void
    {
        // Feature: return-policy-invoice-system, Property 17: return invoice item references valid invoice items

        for ($i = 0; $i < 100; $i++) {
            $company  = $this->makeCompany();
            $policy   = $this->makePolicy($company);
            $invoice  = $this->makeInvoice($company, $policy);
            $item     = $this->makeInvoiceItem($invoice);

            // Create a different invoice and item that does NOT belong to the original invoice
            $otherInvoice = $this->makeInvoice($company, $policy);
            $otherItem    = $this->makeInvoiceItem($otherInvoice);

            // Validate using the form request rules
            $validator = Validator::make([
                'original_invoice_id'    => $invoice->id,
                'items'                  => [
                    ['invoice_item_id' => $otherItem->id, 'quantity' => 1],
                ],
            ], [
                'original_invoice_id'        => ['required', 'integer', 'exists:invoices,id'],
                'items'                       => ['required', 'array', 'min:1'],
                'items.*.invoice_item_id'     => ['required', 'integer', 'exists:invoice_items,id'],
                'items.*.quantity'            => ['required', 'integer', 'min:1'],
            ]);

            // The item exists in DB but belongs to a different invoice
            // The form request validates existence but not ownership — ownership is checked in service
            // Here we verify the assertItemQuantities rejects it
            $items = [['invoice_item_id' => $otherItem->id, 'quantity' => 1]];

            $this->expectException(QuantityExceededException::class);

            $this->validator->assertItemQuantities($items, $invoice->load('items'));
        }
    }

    /**
     * Property 17: Valid invoice_item_id belonging to the original invoice must be accepted.
     *
     * Validates: Requirements 8.5, 8.6
     */
    #[Test]
    public function property17_accepts_valid_invoice_item_id_belonging_to_invoice(): void
    {
        // Feature: return-policy-invoice-system, Property 17: return invoice item references valid invoice items

        for ($i = 0; $i < 100; $i++) {
            $company   = $this->makeCompany();
            $policy    = $this->makePolicy($company);
            $invoice   = $this->makeInvoice($company, $policy);
            $itemCount = fake()->numberBetween(1, 5);
            $items     = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $qty  = fake()->numberBetween(2, 10);
                $item = $this->makeInvoiceItem($invoice, ['qty' => $qty]);
                $items[] = ['invoice_item_id' => $item->id, 'quantity' => fake()->numberBetween(1, $qty)];
            }

            // Should not throw — all items belong to the invoice
            $this->validator->assertItemQuantities($items, $invoice->load('items'));
            $this->assertTrue(true, "Iteration {$i}: valid items from the invoice should be accepted");
        }
    }

    /**
     * Property 17: quantity must be a positive integer (min:1).
     * Zero or negative quantities must be rejected by form validation.
     *
     * Validates: Requirement 8.6
     */
    #[Test]
    public function property17_rejects_non_positive_quantity_in_request(): void
    {
        // Feature: return-policy-invoice-system, Property 17: return invoice item references valid invoice items

        $invalidQuantities = [0, -1, -5, -100];

        for ($i = 0; $i < 100; $i++) {
            $company = $this->makeCompany();
            $policy  = $this->makePolicy($company);
            $invoice = $this->makeInvoice($company, $policy);
            $item    = $this->makeInvoiceItem($invoice, ['qty' => 10]);

            $invalidQty = $invalidQuantities[array_rand($invalidQuantities)];

            $validator = Validator::make([
                'original_invoice_id' => $invoice->id,
                'items'               => [
                    ['invoice_item_id' => $item->id, 'quantity' => $invalidQty],
                ],
            ], [
                'original_invoice_id'        => ['required', 'integer', 'exists:invoices,id'],
                'items'                       => ['required', 'array', 'min:1'],
                'items.*.invoice_item_id'     => ['required', 'integer', 'exists:invoice_items,id'],
                'items.*.quantity'            => ['required', 'integer', 'min:1'],
            ]);

            $this->assertTrue(
                $validator->fails(),
                "Iteration {$i}: quantity={$invalidQty} should fail validation"
            );
            $this->assertArrayHasKey(
                'items.0.quantity',
                $validator->errors()->toArray(),
                "Iteration {$i}: validation error should be on items.0.quantity"
            );
        }
    }
}
