<?php

namespace Database\Factories;

use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReturnPolicy>
 */
class ReturnPolicyFactory extends Factory
{
    protected $model = ReturnPolicy::class;

    public function definition(): array
    {
        return [
            'company_id'                 => User::factory()->create(['user_type' => 'company'])->id,
            'name'                       => $this->faker->words(3, true) . ' Policy',
            'return_window_days'         => $this->faker->numberBetween(7, 90),
            'max_return_ratio'           => $this->faker->randomFloat(4, 0.01, 1.0),
            'bonus_return_enabled'       => false,
            'bonus_return_ratio'         => null,
            'discount_deduction_enabled' => true,
            'min_days_before_expiry'     => 0,
            'is_default'                 => false,
            'is_active'                  => true,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withBonusReturn(float $ratio = 0.5): static
    {
        return $this->state([
            'bonus_return_enabled' => true,
            'bonus_return_ratio'   => $ratio,
        ]);
    }
}
