<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use App\Models\ReturnInvoice;
use App\Models\ReturnInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReturnInvoiceItem>
 */
class ReturnInvoiceItemFactory extends Factory
{
    protected $model = ReturnInvoiceItem::class;

    public function definition(): array
    {
        $discountType  = $this->faker->optional(0.5)->randomElement(['percent', 'fixed']);
        $discountValue = $discountType ? $this->faker->randomFloat(4, 1, 30) : null;
        $unitPrice     = $this->faker->randomFloat(4, 10, 500);
        $returnedQty   = $this->faker->numberBetween(1, 5);

        // Simple refund calculation for seeding purposes
        $refundAmount = $unitPrice * $returnedQty;
        if ($discountType === 'percent' && $discountValue) {
            $refundAmount = $unitPrice * (1 - $discountValue / 100) * $returnedQty;
        }

        return [
            'return_invoice_id'       => ReturnInvoice::factory(),
            'original_item_id'        => InvoiceItem::factory(),
            'returned_quantity'       => $returnedQty,
            'unit_price_snapshot'     => $unitPrice,
            'discount_type_snapshot'  => $discountType,
            'discount_value_snapshot' => $discountValue,
            'expiry_date_snapshot'    => $this->faker->optional(0.6)->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
            'is_bonus'                => false,
            'refund_amount'           => round($refundAmount, 4, PHP_ROUND_HALF_UP),
        ];
    }
}
