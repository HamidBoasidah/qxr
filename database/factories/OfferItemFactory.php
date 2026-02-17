<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferItemFactory extends Factory
{
    protected $model = OfferItem::class;

    public function definition()
    {
        return [
            'offer_id' => Offer::factory(),
            'product_id' => Product::factory(),
            'min_qty' => $this->faker->numberBetween(10, 100),
            'reward_type' => 'discount_percent',
            'discount_percent' => 10.00,
            'discount_fixed' => null,
            'bonus_product_id' => null,
            'bonus_qty' => null,
        ];
    }

    public function percentageDiscount(float $percent = 10.00)
    {
        return $this->state(function (array $attributes) use ($percent) {
            return [
                'reward_type' => 'discount_percent',
                'discount_percent' => $percent,
                'discount_fixed' => null,
                'bonus_product_id' => null,
                'bonus_qty' => null,
            ];
        });
    }

    public function fixedDiscount(float $amount = 50.00)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'reward_type' => 'discount_fixed',
                'discount_percent' => null,
                'discount_fixed' => $amount,
                'bonus_product_id' => null,
                'bonus_qty' => null,
            ];
        });
    }

    public function bonusQty(int $bonusQty = 10, ?int $bonusProductId = null)
    {
        return $this->state(function (array $attributes) use ($bonusQty, $bonusProductId) {
            return [
                'reward_type' => 'bonus_qty',
                'discount_percent' => null,
                'discount_fixed' => null,
                'bonus_product_id' => $bonusProductId ?? $attributes['product_id'],
                'bonus_qty' => $bonusQty,
            ];
        });
    }
}
