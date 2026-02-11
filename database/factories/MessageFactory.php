<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            // Arabic sentence for message body
            'body' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['text', 'attachment']),
        ];
    }

    /**
     * Set message as text type
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'body' => $this->faker->sentence(),
        ]);
    }

    /**
     * Set message as attachment type
     */
    public function attachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'attachment',
            'body' => null,
        ]);
    }

    /**
     * Set message as mixed type (removed - not in migration)
     */
    // public function mixed(): static
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'type' => 'mixed',
    //         'body' => $this->faker->sentence(),
    //     ]);
    // }

    /**
     * Set a specific conversation
     */
    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn (array $attributes) => [
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Set a specific sender
     */
    public function fromSender(User $sender): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => $sender->id,
        ]);
    }
}

