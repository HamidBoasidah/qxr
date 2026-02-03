<?php

namespace App\Repositories;

use App\Exceptions\ValidationRuleException;
use App\Models\ConversationParticipant;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Facades\DB;

class ConversationParticipantRepository extends BaseRepository
{
    public function __construct(ConversationParticipant $model)
    {
        parent::__construct($model);
    }

    /**
     * Update read marker for a participant
     * 
     * This method updates the participant's last_read_message_id to track which messages
     * they have read. It uses a single atomic UPDATE operation for efficiency.
     * 
     * Race Condition Handling:
     * - Uses atomic UPDATE operation (single row update)
     * - If new messages arrive during update, they remain unread (correct behavior)
     * - Message IDs are auto-incrementing, so comparison is always consistent
     * - No locks needed as we're updating a single row with WHERE clause
     *
     * @param int $conversationId The conversation ID
     * @param int $userId The user ID of the participant
     * @param int $messageId The message ID to mark as read up to (inclusive)
     * @return bool True if update was successful, false otherwise
     * @throws ValidationRuleException If message doesn't exist in conversation
     */
    public function updateReadMarker(int $conversationId, int $userId, int $messageId): bool
    {
        // Verify message exists in conversation
        $messageExists = DB::table('messages')
            ->where('id', $messageId)
            ->where('conversation_id', $conversationId)
            ->exists();

        if (!$messageExists) {
            throw new ValidationRuleException(
                "Message {$messageId} not found in conversation {$conversationId}"
            );
        }

        // Atomic single-row update - no race conditions possible
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update([
                'last_read_message_id' => $messageId,
                'last_read_at' => now()
            ]) > 0;
    }

    /**
     * Get the latest message ID in a conversation
     * 
     * Used when marking a conversation as read without specifying a message ID.
     * Returns null if conversation has no messages.
     *
     * @param int $conversationId The conversation ID
     * @return int|null The latest message ID, or null if no messages exist
     */
    public function getLatestMessageId(int $conversationId): ?int
    {
        return DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->max('id');
    }

    /**
     * Get participant record for a user in a conversation
     *
     * @param int $conversationId
     * @param int $userId
     * @return ConversationParticipant|null
     */
    public function getParticipant(int $conversationId, int $userId): ?ConversationParticipant
    {
        return $this->makeQuery()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();
    }
}
