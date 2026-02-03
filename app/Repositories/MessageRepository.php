<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

class MessageRepository extends BaseRepository
{
    protected array $defaultWith = [
        'sender:id,first_name,last_name,avatar',
        'attachments',
    ];

    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a message
     *
     * @param array $data
     * @return Message
     */
    public function create(array $data): Message
    {
        /** @var Message $message */
        $message = parent::create($data);
        
        // Load relationships for consistent response
        return $message->load($this->defaultWith);
    }

    /**
     * Count out-of-session messages from sender with pessimistic lock
     * MUST be called within a database transaction
     *
     * Uses lockForUpdate to prevent race conditions when enforcing message limits
     *
     * @param int $conversationId
     * @param int $senderId
     * @return int
     */
    public function countOutOfSessionWithLock(int $conversationId, int $senderId): int
    {
        return $this->model->newQuery()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', $senderId)
            ->where('context', 'out_of_session')
            ->lockForUpdate()
            ->count();
    }

    /**
     * Get paginated messages with cursor pagination
     * Orders by created_at descending (newest first)
     * Eager loads sender and attachments to avoid N+1 queries
     *
     * @param int $conversationId
     * @param int $perPage
     * @param string|null $cursor
     * @return CursorPaginator
     */
    public function paginateMessages(int $conversationId, int $perPage = 50, ?string $cursor = null): CursorPaginator
    {
        return $this->model->newQuery()
            ->where('conversation_id', $conversationId)
            ->with($this->defaultWith)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // Secondary sort for consistency
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }
}

