<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $order = Order::factory();
        $product = Product::factory();

        $qty = $this->faker->numberBetween(1, 6);
        $unitPrice = $this->faker->randomFloat(2, 5, 500);
        $discount = $this->faker->boolean(35) ? $this->faker->randomFloat(2, 0.5, $unitPrice * 0.3) : 0;
        $finalTotal = ($unitPrice - $discount) * $qty;

        return [
            'order_id' => $order,
            'product_id' => $product,
            'qty' => $qty,
            'unit_price_snapshot' => $unitPrice,
            'discount_amount_snapshot' => $discount,
            'final_line_total_snapshot' => $finalTotal,
            'selected_offer_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (OrderItem $item) {
            $order = $item->order ?? Order::find($item->order_id);
            if ($order) {
                $product = Product::where('company_user_id', $order->company_user_id)
                    ->inRandomOrder()
                    ->first();

                if (!$product) {
                    $product = Product::factory()->create([
                        'company_user_id' => $order->company_user_id,
                    ]);
                }

                $item->product_id = $product->id;

                $price = $product->base_price ?: $this->faker->randomFloat(2, 5, 500);
                $item->unit_price_snapshot = $price;
                $item->discount_amount_snapshot = $this->faker->boolean(35)
                    ? $this->faker->randomFloat(2, 0.5, $price * 0.25)
                    : 0;
                $item->final_line_total_snapshot = ($item->unit_price_snapshot - $item->discount_amount_snapshot) * $item->qty;
            }
        })->afterCreating(function (OrderItem $item) {
            if ($item->selected_offer_id) {
                return;
            }

            $offerId = Offer::where('company_user_id', $item->order->company_user_id)
                ->inRandomOrder()
                ->value('id');

            if ($offerId && $this->faker->boolean(45)) {
                $item->update(['selected_offer_id' => $offerId]);
            }
        });
    }
}
