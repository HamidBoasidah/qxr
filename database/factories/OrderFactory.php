<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $arabic = \Faker\Factory::create('ar_SA');

        $companyId = User::where('user_type', 'company')->inRandomOrder()->value('id')
            ?? User::factory()->create(['user_type' => 'company'])->id;

        $customerId = User::where('user_type', 'customer')->inRandomOrder()->value('id')
            ?? User::factory()->create(['user_type' => 'customer'])->id;

        $status = $this->faker->randomElement([
            'pending',
            'approved',
            'preparing',
            'shipped',
            'delivered',
            'rejected',
            'cancelled',
        ]);

        $submittedAt = Carbon::now()->subDays(rand(1, 60))->subHours(rand(0, 23));
        $approvedAt = null;
        $deliveredAt = null;
        $approvedBy = null;

        if (in_array($status, ['approved', 'preparing', 'shipped', 'delivered'])) {
            $approvedAt = (clone $submittedAt)->addHours(rand(2, 48));
            $approvedBy = $companyId;
        }

        if ($status === 'delivered') {
            $deliveredAt = (clone ($approvedAt ?? $submittedAt))->addHours(rand(6, 72));
        }

        // attempt to pick an existing address for the customer or create one
        $deliveryAddressId = Address::where('user_id', $customerId)->inRandomOrder()->value('id')
            ?? Address::factory()->create(['user_id' => $customerId])->id;

        return [
            'order_no' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'company_user_id' => $companyId,
            'customer_user_id' => $customerId,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
            'approved_by_user_id' => $approvedBy,
            'delivered_at' => $deliveredAt,
            'notes_customer' => $arabic->sentence(),
            'notes_company' => $arabic->sentence(),
            'delivery_address_id' => $deliveryAddressId,
        ];
    }
}
