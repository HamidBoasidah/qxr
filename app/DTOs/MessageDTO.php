<?php

namespace App\DTOs;

use App\Models\Message;

class MessageDTO extends BaseDTO
{
    public int $id;
    public int $conversation_id;
    public int $sender_id;
    public string $sender_name;
    public ?string $body;
    public string $type;
    public array $attachments;
    public string $created_at;

    public function __construct(
        int $id,
        int $conversation_id,
        int $sender_id,
        string $sender_name,
        ?string $body,
        string $type,
        array $attachments,
        string $created_at
    ) {
        $this->id = $id;
        $this->conversation_id = $conversation_id;
        $this->sender_id = $sender_id;
        $this->sender_name = $sender_name;
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

        return new self(
            id: $message->id,
            conversation_id: $message->conversation_id,
            sender_id: $message->sender_id,
            sender_name: $message->sender->name ?? '',
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
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender_name,
            'body' => $this->body,
            'type' => $this->type,
            'attachments' => array_map(fn($att) => $att->toArray(), $this->attachments),
            'created_at' => $this->created_at,
        ];
    }
}
