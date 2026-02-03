<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageAttachmentFactory extends Factory
{
    protected $model = MessageAttachment::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'original_name' => $this->faker->word() . '.' . $this->faker->fileExtension(),
            'mime_type' => $this->faker->mimeType(),
            'size_bytes' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'disk' => 'private',
            'path' => 'chat-attachments/' . $this->faker->uuid() . '.' . $this->faker->fileExtension(),
        ];
    }

    /**
     * Set a specific message
     */
    public function forMessage(Message $message): static
    {
        return $this->state(fn (array $attributes) => [
            'message_id' => $message->id,
        ]);
    }

    /**
     * Set as PDF file
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'path' => 'chat-attachments/' . $this->faker->uuid() . '.pdf',
        ]);
    }

    /**
     * Set as image file
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->word() . '.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'chat-attachments/' . $this->faker->uuid() . '.jpg',
        ]);
    }
}
