<?php

namespace App\Services;

use App\DTOs\MarkReadDTO;
use App\Repositories\ConversationParticipantRepository;
use App\Repositories\ConversationRepository;

class ReadStateService
{
    public function __construct(
        private ConversationParticipantRepository $participantRepo,
        private ConversationRepository $conversationRepo
    ) {}

    /**
     * Mark conversation as read up to a specific message or latest message
     * 
     * This method implements an optimistic approach to handle race conditions:
     * - Captures the latest message ID at the time of the call
     * - Updates read marker to that ID atomically
     * - If new messages arrive after capturing the ID, they remain unread (correct behavior)
     * - No data corruption possible due to atomic single-row UPDATE
     * 
     * Race Condition Example:
     * 1. User opens conversation, sees messages 1-10
     * 2. markAsRead() captures latest ID = 10
     * 3. New message 11 arrives before UPDATE executes
     * 4. UPDATE sets last_read_message_id = 10 (atomic operation)
     * 5. Message 11 remains unread (correct - user hasn't seen it yet)
     *
     * @param MarkReadDTO $dto Contains conversation ID, user ID, and optional message ID
     * @return bool True if mark as read was successful
     */
    public function markAsRead(MarkReadDTO $dto): bool
    {
        // If no specific message ID provided, get the latest
        // This captures a snapshot of the latest message at this moment
        $messageId = $dto->messageId
            ?? $this->participantRepo->getLatestMessageId($dto->conversationId);

        if ($messageId === null) {
            // No messages in conversation, nothing to mark
            return true;
        }

        // Atomic update - race condition safe
        return $this->participantRepo->updateReadMarker(
            $dto->conversationId,
            $dto->userId,
            $messageId
        );
    }

    /**
     * Get unread count for a conversation
     * 
     * Calculates the number of unread messages for a specific user in a conversation.
     * Unread messages are those where:
     * - Sender is not the current user (own messages don't count as unread)
     * - Message ID > last_read_message_id (or all messages if never read)
     *
     * @param int $conversationId The conversation ID
     * @param int $userId The user ID to get unread count for
     * @return int Number of unread messages (>= 0)
     */
    public function getUnreadCount(int $conversationId, int $userId): int
    {
        return $this->conversationRepo->getUnreadCount($conversationId, $userId);
    }
}
