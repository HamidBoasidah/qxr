<?php

namespace App\DTOs;

use App\Models\Message;

class MessageDTO extends BaseDTO
{
    public int $id;
    public int $conversation_id;
    public array $sender;
    public ?string $body;
    public string $type;
    public array $attachments;
    public string $created_at;

    public function __construct(
        int $id,
        int $conversation_id,
        array $sender,
        ?string $body,
        string $type,
        array $attachments,
        string $created_at
    ) {
        $this->id = $id;
        $this->conversation_id = $conversation_id;
        $this->sender = $sender;
        $this->body = $body;
        $this->type = $type;
        $this->attachments = $attachments;
        $this->created_at = $created_at;
    }

    /**
     * Create DTO from Message model
     */
    public static function fromModel(Message $message): self
    {
        // Map attachments using AttachmentDTO
        $attachments = $message->attachments->map(function ($attachment) {
            return AttachmentDTO::fromModel($attachment);
        })->values()->all();

        // Get sender information
        $sender = $message->sender;
        $senderName = '';
        if ($sender) {
            $senderName = trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));
        }

        return new self(
            id: $message->id,
            conversation_id: $message->conversation_id,
            sender: [
                'id' => $message->sender_id,
                'name' => $senderName,
                'avatar' => $sender?->avatar,
            ],
            body: $message->body,
            type: $message->type,
            attachments: $attachments,
            created_at: $message->created_at?->format('Y-m-d\TH:i:s') ?? ''
        );
    }

    /**
     * Convert DTO to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => $this->sender,
            'body' => $this->body,
            'type' => $this->type,
            'attachments' => array_map(fn($att) => $att->toArray(), $this->attachments),
            'created_at' => $this->created_at,
        ];
    }
}
