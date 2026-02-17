<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\OfferTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferTargetFactory extends Factory
{
    protected $model = OfferTarget::class;

    public function definition()
    {
        $customerId = User::where('user_type', 'customer')->inRandomOrder()->value('id')
            ?: User::factory()->create(['user_type' => 'customer'])->id;

        return [
            'offer_id' => Offer::factory(),
            'target_type' => 'customer',
            'target_id' => $customerId,
        ];
    }

    public function forCustomer(int $customerId)
    {
        return $this->state(function (array $attributes) use ($customerId) {
            return [
                'target_type' => 'customer',
                'target_id' => $customerId,
            ];
        });
    }
}
