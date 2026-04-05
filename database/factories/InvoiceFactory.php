<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        // Create a company user and a customer user for the order
        $company  = User::factory()->create(['user_type' => 'company']);
        $customer = User::factory()->create(['user_type' => 'customer']);

        // Create a return policy for the company
        $returnPolicy = ReturnPolicy::factory()->create(['company_id' => $company->id]);

        // Create an order for the invoice (invoices require an order)
        $order = Order::factory()->create([
            'company_user_id'  => $company->id,
            'customer_user_id' => $customer->id,
        ]);

        return [
            'invoice_no'               => 'INV-' . strtoupper(Str::random(8)),
            'order_id'                 => $order->id,
            'subtotal_snapshot'        => $this->faker->randomFloat(2, 100, 10000),
            'discount_total_snapshot'  => 0,
            'total_snapshot'           => $this->faker->randomFloat(2, 100, 10000),
            'issued_at'                => now(),
            'status'                   => 'draft',
            'note'                     => null,
            'return_policy_id'         => $returnPolicy->id,
        ];
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function forCompany(User $company): static
    {
        return $this->state(function () use ($company) {
            $customer     = User::factory()->create(['user_type' => 'customer']);
            $returnPolicy = ReturnPolicy::factory()->create(['company_id' => $company->id]);
            $order        = Order::factory()->create([
                'company_user_id'  => $company->id,
                'customer_user_id' => $customer->id,
            ]);

            return [
                'order_id'         => $order->id,
                'return_policy_id' => $returnPolicy->id,
            ];
        });
    }
}
