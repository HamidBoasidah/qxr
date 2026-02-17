<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition()
    {
        $companyUserId = User::where('user_type', 'company')->inRandomOrder()->value('id')
            ?: User::factory()->create(['user_type' => 'company'])->id;

        return [
            'company_user_id' => $companyUserId,
            'scope' => 'public',
            'status' => 'active',
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_at' => null, // Already started
            'end_at' => null,   // Never expires
        ];
    }

    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'scope' => 'private',
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'start_at' => now()->subDays(10),
                'end_at' => now()->subDay(),
            ];
        });
    }

    public function notStarted()
    {
        return $this->state(function (array $attributes) {
            return [
                'start_at' => now()->addDay(),
                'end_at' => now()->addDays(10),
            ];
        });
    }
}
