<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Facades\DB;

class ConversationRepository extends BaseRepository
{
    protected array $defaultWith = [
        'participants',
    ];

    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    /**
     * Find conversation between two users
     * 
     * Uses a subquery approach that works with both MySQL and SQLite.
     * Finds conversations where both users are participants and there are exactly 2 participants.
     *
     * @param int $userId1
     * @param int $userId2
     * @return Conversation|null
     */
    public function findByParticipants(int $userId1, int $userId2): ?Conversation
    {
        // Get conversation IDs that have exactly 2 participants
        $conversationIdsWithTwoParticipants = DB::table('conversation_participants')
            ->select('conversation_id')
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(*) = 2')
            ->pluck('conversation_id');

        return $this->model
            ->whereIn('id', $conversationIdsWithTwoParticipants)
            ->whereHas('participants', function ($query) use ($userId1) {
                $query->where('user_id', $userId1);
            })
            ->whereHas('participants', function ($query) use ($userId2) {
                $query->where('user_id', $userId2);
            })
            ->first();
    }

    /**
     * Create conversation with participants only (no booking)
     * Uses DB transaction to ensure atomicity
     *
     * @param int $userId1
     * @param int $userId2
     * @return Conversation
     */
    public function createWithParticipantsOnly(int $userId1, int $userId2): Conversation
    {
        return DB::transaction(function () use ($userId1, $userId2) {
            // Create the conversation
            /** @var Conversation $conversation */
            $conversation = $this->create([]);

            // Attach both participants
            $conversation->participants()->attach([$userId1, $userId2]);

            // Reload with relationships
            return $conversation->load(['participants']);
        });
    }

    /**
     * Check if user is participant in conversation
     *
     * @param int $conversationId
     * @param int $userId
     * @return bool
     */
    public function isParticipant(int $conversationId, int $userId): bool
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get all conversations for a user with pagination and search
     *
     * @param int $userId
     * @param string|null $search
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserConversations(int $userId, ?string $search = null, int $perPage = 20)
    {
        $query = $this->model
            ->select('conversations.*')
            ->join('conversation_participants', 'conversations.id', '=', 'conversation_participants.conversation_id')
            ->where('conversation_participants.user_id', $userId)
            ->with([
                'participants',
                'messages' => function ($query) {
                    $query->with('sender')->latest()->limit(1);
                }
            ])
            ->orderBy('conversations.updated_at', 'desc');

        // Search in participant names if search term provided
        if ($search) {
            $query->where(function ($q) use ($search, $userId) {
                $q->whereHas('participants', function ($participantQuery) use ($search, $userId) {
                    $participantQuery->where('users.id', '!=', $userId)
                        ->where(function ($nameQuery) use ($search) {
                            $nameQuery->where('users.first_name', 'like', "%{$search}%")
                                ->orWhere('users.last_name', 'like', "%{$search}%")
                                ->orWhere(DB::raw("CONCAT(users.first_name, ' ', users.last_name)"), 'like', "%{$search}%");
                        });
                });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get unread count for a specific conversation and user
     * 
     * Calculates unread messages efficiently using indexed queries.
     * Unread messages are those where:
     * - Sender is not the current user (own messages don't count)
     * - Message ID > last_read_message_id (or all if never read)
     * 
     * Performance:
     * - Uses composite index on messages(conversation_id, id) for efficient range scan
     * - Single query with WHERE clause on indexed columns
     * - O(log n) lookup time due to index usage
     * 
     * @param int $conversationId The conversation ID
     * @param int $userId The user ID to calculate unread count for
     * @return int Number of unread messages (>= 0)
     */
    public function getUnreadCount(int $conversationId, int $userId): int
    {
        $participant = DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first(['last_read_message_id']);

        $lastReadId = $participant?->last_read_message_id ?? 0;

        return DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('id', '>', $lastReadId)
            ->count();
    }

    /**
     * Get conversations for a user with unread counts
     * 
     * Fetches all conversations for a user with their unread message counts
     * in a single efficient query using joins.
     * 
     * Performance Optimization:
     * - Uses LEFT JOIN to include conversations with 0 unread messages
     * - Single query with GROUP BY to avoid N+1 problem
     * - Leverages composite index on messages(conversation_id, id)
     * - Scales linearly with number of conversations (O(n))
     * 
     * Query Strategy:
     * - Joins conversation_participants to filter user's conversations
     * - LEFT JOIN messages where id > last_read_message_id
     * - COUNT DISTINCT to get unread count per conversation
     * - GROUP BY conversation to aggregate results
     * 
     * @param int $userId The user ID to get conversations for
     * @return \Illuminate\Support\Collection Collection of conversation data with unread_count
     */
    public function getConversationsWithUnreadCounts(int $userId): \Illuminate\Support\Collection
    {
        return DB::table('conversations as c')
            ->join('conversation_participants as cp', 'c.id', '=', 'cp.conversation_id')
            ->leftJoin('messages as unread_msg', function ($join) use ($userId) {
                $join->on('c.id', '=', 'unread_msg.conversation_id')
                    ->where('unread_msg.sender_id', '!=', $userId)
                    ->whereRaw('unread_msg.id > COALESCE(cp.last_read_message_id, 0)');
            })
            ->where('cp.user_id', $userId)
            ->whereNull('c.deleted_at')
            ->select([
                'c.id',
                'c.created_at',
                'c.updated_at',
                DB::raw('COUNT(DISTINCT unread_msg.id) as unread_count')
            ])
            ->groupBy('c.id', 'c.created_at', 'c.updated_at')
            ->orderBy('c.updated_at', 'desc')
            ->get();
    }
}
