<?php

namespace App\Services;

use App\DTOs\ConversationDTO;
use App\DTOs\ConversationListDTO;
use App\DTOs\MessageDTO;
use App\Exceptions\ForbiddenException;
use App\Models\Conversation;
use App\Repositories\ConversationRepository;
use App\Repositories\MessageRepository;
use Illuminate\Support\Facades\DB;

class ChatService
{
    protected ConversationRepository $conversations;
    protected MessageRepository $messages;
    protected AttachmentService $attachments;
    protected ReadStateService $readStateService;

    public function __construct(
        ConversationRepository $conversations,
        MessageRepository $messages,
        AttachmentService $attachments,
        ReadStateService $readStateService
    ) {
        $this->conversations = $conversations;
        $this->messages = $messages;
        $this->attachments = $attachments;
        $this->readStateService = $readStateService;
    }

    /**
     * Get or create conversation between two users
     * Returns existing conversation if one exists, otherwise creates new one
     * 
     * @param int $userId The current user
     * @param int $otherUserId The other user
     * @return ConversationDTO
     * @throws ForbiddenException If user tries to start conversation with themselves
     */
    public function getOrCreateConversationByUser(int $userId, int $otherUserId): ConversationDTO
    {
        if ($userId === $otherUserId) {
            throw new ForbiddenException('لا يمكنك بدء محادثة مع نفسك');
        }

        $conversation = $this->conversations->findByParticipants($userId, $otherUserId);

        if ($conversation) {
            return ConversationDTO::fromModel($conversation);
        }

        $conversation = $this->conversations->createWithParticipantsOnly($userId, $otherUserId);

        return ConversationDTO::fromModel($conversation);
    }

    /**
     * Send a message with optional attachments
     * Simplified - only checks participant membership
     * Uses DB transaction + locking for race condition prevention
     * 
     * @param int $conversationId
     * @param int $senderId
     * @param string|null $body
     * @param array $uploadedFiles Array of UploadedFile instances
     * @return MessageDTO
     * @throws ForbiddenException
     */
    public function sendMessage(
        int $conversationId,
        int $senderId,
        ?string $body,
        array $uploadedFiles
    ): MessageDTO {
        return DB::transaction(function () use ($conversationId, $senderId, $body, $uploadedFiles) {
            // Lock conversation to prevent race conditions
            $conversation = Conversation::with(['participants'])
                ->lockForUpdate()
                ->findOrFail($conversationId);

            // Verify user is participant
            if (!$conversation->isParticipant($senderId)) {
                throw new ForbiddenException('أنت لست مشاركاً في هذه المحادثة');
            }

            // Validate files if present
            if (!empty($uploadedFiles)) {
                $this->attachments->validateFiles($uploadedFiles);
            }

            // Determine message type
            $type = $this->determineMessageType($body, $uploadedFiles);

            // Create message (without context)
            $message = $this->messages->create([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'body' => $body,
                'type' => $type,
            ]);

            // Store attachments if present
            if (!empty($uploadedFiles)) {
                $this->attachments->storeAttachments($message->id, $uploadedFiles);
                // Reload message with attachments and sender
                $message = $message->fresh(['sender', 'attachments']);
            } else {
                // Reload message with sender
                $message = $message->fresh(['sender']);
            }

            return MessageDTO::fromModel($message);
        });
    }

    /**
     * Determine message type based on content
     * 
     * @param string|null $body
     * @param array $uploadedFiles
     * @return string 'text', 'attachment', or 'mixed'
     */
    protected function determineMessageType(?string $body, array $uploadedFiles): string
    {
        $hasBody = !empty($body);
        $hasFiles = !empty($uploadedFiles);

        if ($hasBody && $hasFiles) {
            return 'mixed';
        }

        if ($hasFiles) {
            return 'attachment';
        }

        return 'text';
    }

    /**
     * Get user's conversations list with search
     * Returns DTOs instead of models
     *
     * @param int $userId
     * @param string|null $search
     * @param int $perPage
     * @return array{data: array, pagination: array}
     */
    public function getUserConversations(int $userId, ?string $search = null, int $perPage = 20): array
    {
        $conversations = $this->conversations->getUserConversations($userId, $search, $perPage);

        $dtos = collect($conversations->items())->map(function ($conversation) use ($userId) {
            $unreadCount = $this->readStateService->getUnreadCount($conversation->id, $userId);
            return ConversationListDTO::fromModel($conversation, $userId, $unreadCount);
        });

        return [
            'data' => $dtos->map(fn($dto) => $dto->toArray())->values()->all(),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ];
    }

    /**
     * Get messages for a conversation and mark as read
     * Fetches paginated messages and automatically updates read marker
     * Returns DTOs instead of models
     *
     * @param int $conversationId
     * @param int $userId
     * @param int $perPage
     * @param string|null $cursor
     * @return array{data: array, meta: array}
     */
    public function getMessagesAndMarkRead(
        int $conversationId,
        int $userId,
        int $perPage = 50,
        ?string $cursor = null
    ): array {
        // Fetch messages (existing logic)
        $messages = $this->messages->paginateMessages(
            $conversationId,
            $perPage,
            $cursor
        );

        // Mark as read (new logic)
        $messageItems = collect($messages->items());
        if ($messageItems->isNotEmpty()) {
            $latestMessageId = $messageItems->max('id');
            $this->readStateService->markAsRead(
                new \App\DTOs\MarkReadDTO($conversationId, $userId, $latestMessageId)
            );
        }

        // Get updated unread count
        $unreadCount = $this->readStateService->getUnreadCount($conversationId, $userId);

        // Convert to DTOs
        $dtos = $messageItems->map(function ($message) {
            return MessageDTO::fromModel($message);
        });

        return [
            'data' => $dtos->map(fn($dto) => $dto->toArray())->values()->all(),
            'meta' => [
                'next_cursor' => $messages->nextCursor()?->encode(),
                'prev_cursor' => $messages->previousCursor()?->encode(),
                'per_page' => $messages->perPage(),
                'unread_count' => $unreadCount,
            ],
        ];
    }
}
