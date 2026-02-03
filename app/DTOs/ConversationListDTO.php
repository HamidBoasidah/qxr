<?php

namespace App\DTOs;

use App\Models\Conversation;

class ConversationListDTO extends BaseDTO
{
    public int $id;
    public array $other_participant;
    public ?array $last_message;
    public int $unread_count;
    public string $created_at;
    public string $updated_at;

    public function __construct(
        int $id,
        array $other_participant,
        ?array $last_message,
        int $unread_count,
        string $created_at,
        string $updated_at
    ) {
        $this->id = $id;
        $this->other_participant = $other_participant;
        $this->last_message = $last_message;
        $this->unread_count = $unread_count;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    /**
     * Create DTO from Conversation model
     */
    public static function fromModel(Conversation $conversation, int $currentUserId, int $unreadCount = 0): self
    {
        // Get the other participant (not the current user)
        $otherParticipant = $conversation->participants->firstWhere('id', '!=', $currentUserId);

        // Get last message
        $lastMessage = null;
        if ($conversation->relationLoaded('messages') && $conversation->messages->isNotEmpty()) {
            $message = $conversation->messages->first();
            $lastMessage = [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type,
                'sender_id' => $message->sender_id,
                'is_from_me' => $message->sender_id === $currentUserId,
                'created_at' => $message->created_at?->toIso8601String(),
            ];
        }

        return new self(
            id: $conversation->id,
            other_participant: [
                'id' => $otherParticipant?->id,
                'full_name' => $otherParticipant ? trim($otherParticipant->first_name . ' ' . $otherParticipant->last_name) : null,
                'avatar' => $otherParticipant?->avatar,
            ],
            last_message: $lastMessage,
            unread_count: $unreadCount,
            created_at: $conversation->created_at?->toIso8601String() ?? '',
            updated_at: $conversation->updated_at?->toIso8601String() ?? ''
        );
    }

    /**
     * Convert DTO to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'other_participant' => $this->other_participant,
            'last_message' => $this->last_message,
            'unread_count' => $this->unread_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
