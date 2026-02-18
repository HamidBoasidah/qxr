<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemBonusFactory extends Factory
{
    protected $model = OrderItemBonus::class;

    public function definition(): array
    {
        $orderItem = OrderItem::factory();
        $bonusProduct = Product::factory();

        return [
            'order_item_id' => $orderItem,
            'offer_id' => null,
            'bonus_product_id' => $bonusProduct,
            'bonus_qty' => $this->faker->numberBetween(1, 4),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (OrderItemBonus $bonus) {
            $orderItem = $bonus->orderItem ?? OrderItem::find($bonus->order_item_id);
            if ($orderItem) {
                $bonusProduct = Product::where('company_user_id', $orderItem->order->company_user_id)
                    ->inRandomOrder()
                    ->first();

                if (!$bonusProduct) {
                    $bonusProduct = Product::factory()->create([
                        'company_user_id' => $orderItem->order->company_user_id,
                    ]);
                }

                $bonus->bonus_product_id = $bonusProduct->id;
            }
        })->afterCreating(function (OrderItemBonus $bonus) {
            $orderItem = $bonus->orderItem;
            if (!$orderItem) {
                return;
            }

            $offerId = Offer::where('company_user_id', $orderItem->order->company_user_id)
                ->inRandomOrder()
                ->value('id');

            if ($offerId && $this->faker->boolean(50)) {
                $bonus->update(['offer_id' => $offerId]);
            }
        });
    }
}
