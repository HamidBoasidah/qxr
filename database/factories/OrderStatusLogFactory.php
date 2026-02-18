<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class OrderStatusLogFactory extends Factory
{
    protected $model = OrderStatusLog::class;

    public function definition(): array
    {
        $order = Order::factory();
        $statuses = ['pending', 'approved', 'preparing', 'shipped', 'delivered', 'rejected', 'cancelled'];
        $toStatus = $this->faker->randomElement($statuses);
        $fromStatus = $this->faker->boolean(60)
            ? $this->faker->randomElement(array_diff($statuses, [$toStatus]))
            : null;

        $changedAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

        return [
            'order_id' => $order,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => User::inRandomOrder()->value('id'),
            'note' => \Faker\Factory::create('ar_SA')->sentence(),
            'changed_at' => $changedAt,
        ];
    }
}
