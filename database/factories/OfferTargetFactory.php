<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\OfferTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class OfferTargetFactory extends Factory
{
    protected $model = OfferTarget::class;

    /**
     * Handle custom attribute mappings before creating the model
     */
    public function raw($attributes = [], ?Model $parent = null)
    {
        // Map customer_user_id to target_type/target_id for backward compatibility
        if (isset($attributes['customer_user_id'])) {
            if (!isset($attributes['target_type'])) {
                $attributes['target_type'] = 'customer';
            }
            if (!isset($attributes['target_id'])) {
                $attributes['target_id'] = $attributes['customer_user_id'];
            }
            unset($attributes['customer_user_id']);
        }
        
        return parent::raw($attributes, $parent);
    }

    /**
     * Handle custom attribute mappings before creating the model
     */
    public function make($attributes = [], ?Model $parent = null)
    {
        // Map customer_user_id to target_type/target_id for backward compatibility
        if (isset($attributes['customer_user_id'])) {
            if (!isset($attributes['target_type'])) {
                $attributes['target_type'] = 'customer';
            }
            if (!isset($attributes['target_id'])) {
                $attributes['target_id'] = $attributes['customer_user_id'];
            }
            unset($attributes['customer_user_id']);
        }
        
        return parent::make($attributes, $parent);
    }

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
