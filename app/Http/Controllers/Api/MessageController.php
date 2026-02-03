<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetMessagesRequest;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Traits\ExceptionHandler;
use App\Http\Traits\SuccessResponse;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    use ExceptionHandler, SuccessResponse;

    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->middleware('auth:sanctum');
        $this->chatService = $chatService;
    }

    /**
     * List messages in a conversation with cursor pagination
     * 
     * Endpoint: GET /api/conversations/{conversation}/messages
     * Authentication: Required (sanctum)
     * 
     * @param Conversation $conversation
     * @param GetMessagesRequest $request
     * @return JsonResponse
     */
    public function index(Conversation $conversation, GetMessagesRequest $request): JsonResponse
    {
        // Authorize: user must be participant in conversation
        $this->authorize('view', $conversation);

        // Get pagination parameters
        $perPage = $request->input('per_page', config('chat.pagination.messages_per_page', 50));
        $cursor = $request->input('cursor');

        // Get messages and mark as read (returns DTOs)
        $result = $this->chatService->getMessagesAndMarkRead(
            conversationId: $conversation->id,
            userId: $request->user()->id,
            perPage: $perPage,
            cursor: $cursor
        );

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الرسائل بنجاح',
            'status_code' => 200,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ], 200);
    }

    /**
     * Send a message with optional attachments
     * POST /api/conversations/{conversation}/messages
     * 
     * @param Conversation $conversation
     * @param SendMessageRequest $request
     * @return JsonResponse
     */
    public function store(Conversation $conversation, SendMessageRequest $request): JsonResponse
    {
        // Authorize: user must be able to send message in this conversation
        $this->authorize('sendMessage', $conversation);

        // Get validated data
        $body = $request->input('body');
        $files = $request->file('files', []);

        // Delegate to ChatService to send message (returns DTO)
        $messageDTO = $this->chatService->sendMessage(
            $conversation->id,
            $request->user()->id,
            $body,
            $files
        );

        return $this->createdResponse(
            $messageDTO->toArray(),
            'تم إرسال الرسالة بنجاح'
        );
    }
}
