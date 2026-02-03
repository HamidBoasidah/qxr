<?php

namespace App\DTOs;

use App\Models\Conversation;

class ConversationDTO extends BaseDTO
{
    public int $id;
    public array $participants;
    public ?MessageDTO $last_message;
    public int $unread_count;
    public string $created_at;
    public string $updated_at;

    public function __construct(
        int $id,
        array $participants,
        ?MessageDTO $last_message,
        int $unread_count,
        string $created_at,
        string $updated_at
    ) {
        $this->id = $id;
        $this->participants = $participants;
        $this->last_message = $last_message;
        $this->unread_count = $unread_count;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    /**
     * Create DTO from Conversation model
     */
    public static function fromModel(Conversation $conversation, int $unreadCount = 0): self
    {
        // Map participants to array with user details
        $participants = $conversation->participants->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? null,
                'avatar' => $user->avatar ?? null,
            ];
        })->values()->all();

        // Get last message if exists
        $lastMessage = null;
        if ($conversation->relationLoaded('messages') && $conversation->messages->isNotEmpty()) {
            $lastMessage = MessageDTO::fromModel($conversation->messages->last());
        }

        return new self(
            id: $conversation->id,
            participants: $participants,
            last_message: $lastMessage,
            unread_count: $unreadCount,
            created_at: $conversation->created_at?->format('Y-m-d\TH:i:s') ?? '',
            updated_at: $conversation->updated_at?->format('Y-m-d\TH:i:s') ?? ''
        );
    }

    /**
     * Convert DTO to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'participants' => $this->participants,
            'last_message' => $this->last_message?->toArray(),
            'unread_count' => $this->unread_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
