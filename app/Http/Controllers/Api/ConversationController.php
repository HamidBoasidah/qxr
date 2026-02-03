<?php

namespace App\Http\Controllers\Api;

use App\DTOs\MarkReadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateConversationRequest;
use App\Http\Requests\Api\GetConversationsRequest;
use App\Http\Traits\ExceptionHandler;
use App\Http\Traits\SuccessResponse;
use App\Models\Conversation;
use App\Services\ChatService;
use App\Services\ReadStateService;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
    use ExceptionHandler, SuccessResponse;

    protected ChatService $chatService;
    protected ReadStateService $readStateService;

    public function __construct(ChatService $chatService, ReadStateService $readStateService)
    {
        $this->middleware('auth:sanctum');
        $this->chatService = $chatService;
        $this->readStateService = $readStateService;
    }

    /**
     * Create or get existing conversation with another user
     * POST /api/conversations
     * 
     * @param CreateConversationRequest $request
     * @return JsonResponse
     */
    public function store(CreateConversationRequest $request): JsonResponse
    {
        $conversationDTO = $this->chatService->getOrCreateConversationByUser(
            $request->user()->id,
            $request->input('user_id')
        );

        return $this->createdResponse(
            $conversationDTO->toArray(),
            'تم إنشاء المحادثة بنجاح'
        );
    }

    /**
     * Get user's conversations list with unread counts
     * 
     * Endpoint: GET /api/conversations
     * Authentication: Required (sanctum)
     * 
     * @param GetConversationsRequest $request
     * @return JsonResponse
     */
    public function index(GetConversationsRequest $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);

        $result = $this->chatService->getUserConversations(
            $request->user()->id,
            $search,
            $perPage
        );

        // Check if no conversations found
        if (empty($result['data'])) {
            return $this->successResponse(
                [],
                'لا توجد محادثات بعد',
                200
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب المحادثات بنجاح',
            'status_code' => 200,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 200);
    }

    /**
     * Mark conversation as read
     * 
     * Endpoint: POST /api/conversations/{conversation}/read
     * Authentication: Required (sanctum)
     * 
     * @param Conversation $conversation The conversation to mark as read
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function markAsRead(Conversation $conversation, \Illuminate\Http\Request $request): JsonResponse
    {
        // Authorize: user must be participant in conversation
        $this->authorize('view', $conversation);

        $dto = MarkReadDTO::fromRequest($request, $conversation->id);
        $success = $this->readStateService->markAsRead($dto);

        return response()->json([
            'success' => $success,
            'message' => 'تم تحديث حالة القراءة بنجاح',
            'status_code' => 200,
            'data' => [
                'unread_count' => $this->readStateService->getUnreadCount(
                    $conversation->id,
                    $request->user()->id
                )
            ]
        ]);
    }
}
