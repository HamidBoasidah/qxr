<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty       = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(4, 10, 500);

        return [
            'invoice_id'           => Invoice::factory(),
            'product_id'           => null,
            'description_snapshot' => $this->faker->words(3, true),
            'qty'                  => $qty,
            'unit_price_snapshot'  => $unitPrice,
            'line_total_snapshot'  => round($unitPrice * $qty, 4),
            'expiry_date'          => $this->faker->optional(0.6)->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
            'discount_type'        => null,
            'discount_value'       => null,
            'is_bonus'             => false,
        ];
    }
}
