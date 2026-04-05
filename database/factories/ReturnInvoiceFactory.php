<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\ReturnInvoice;
use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReturnInvoice>
 */
class ReturnInvoiceFactory extends Factory
{
    protected $model = ReturnInvoice::class;

    public function definition(): array
    {
        return [
            'original_invoice_id' => Invoice::factory(),
            'company_id'          => User::factory()->create(['user_type' => 'company'])->id,
            'return_policy_id'    => ReturnPolicy::factory(),
            'total_refund_amount' => $this->faker->randomFloat(4, 10, 5000),
            'status'              => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'notes'               => $this->faker->optional(0.4)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }
}
