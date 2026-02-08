<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\CompanyProfile;
use App\Models\CustomerProfile;
use App\Models\Category;
use Illuminate\Support\Str as SupportStr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'avatar' => null,
            'phone_number' => fake()->numerify('5########'),
            'whatsapp_number' => fake()->numerify('7########'),
            'password' => static::$password ??= Hash::make('password'),
            'user_type' => fake()->randomElement(['customer', 'company']),
            'gender' => fake()->randomElement(['male', 'female']),
            'facebook' => 'https://facebook.com/' . fake()->userName(),
            'x_url' => 'https://x.com/' . fake()->userName(),
            'linkedin' => 'https://linkedin.com/in/' . fake()->userName(),
            'instagram' => 'https://instagram.com/' . fake()->userName(),
            'is_active' => true,
            'locale' => 'ar',
            'created_by' => null,
            'updated_by' => null,
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Create appropriate profile for fake users
            $displayName = $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email;

            if (($user->user_type ?? 'customer') === 'company') {
                // pick or create a company category for the profile
                $category = Category::where('category_type', 'company')->inRandomOrder()->first();
                if (!$category) {
                    $category = Category::create([
                        'name' => 'General Company',
                        'slug' => 'general-company-' . SupportStr::random(5),
                        'category_type' => 'company',
                    ]);
                }

                $user->companyProfile()->create([
                    'company_name' => $displayName,
                    'category_id' => $category->id,
                    'is_active' => true,
                ]);
            } else {
                // pick or create a customer category
                $category = Category::where('category_type', 'customer')->inRandomOrder()->first();
                if (!$category) {
                    $category = Category::create([
                        'name' => 'General Customer',
                        'slug' => 'general-customer-' . SupportStr::random(5),
                        'category_type' => 'customer',
                    ]);
                }

                $user->customerProfile()->create([
                    'business_name' => $displayName,
                    'category_id' => $category->id,
                    'is_active' => true,
                ]);
            }
        });
    }
}
