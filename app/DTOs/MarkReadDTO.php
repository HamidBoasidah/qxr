<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class MarkReadDTO extends BaseDTO
{
    public function __construct(
        public readonly int $conversationId,
        public readonly int $userId,
        public readonly ?int $messageId = null // null = mark all as read
    ) {}

    /**
     * Create DTO from HTTP request
     *
     * @param Request $request
     * @param int $conversationId
     * @return self
     */
    public static function fromRequest(Request $request, int $conversationId): self
    {
        return new self(
            conversationId: $conversationId,
            userId: $request->user()->id,
            messageId: $request->input('message_id')
        );
    }
}
