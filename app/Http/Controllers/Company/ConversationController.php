<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CreateConversationRequest;
use App\Models\Conversation;
use App\Services\ChatService;
use App\Services\ReadStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    protected ChatService $chatService;
    protected ReadStateService $readStateService;

    public function __construct(ChatService $chatService, ReadStateService $readStateService)
    {
        $this->chatService = $chatService;
        $this->readStateService = $readStateService;
        
        // Ensure only authenticated (web) users can access company chat routes
        $this->middleware('auth:web');
    }

    /**
     * Display a listing of the user's conversations.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $userId = Auth::guard('web')->id();
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);

        $conversations = $this->chatService->getUserConversations($userId, $search, $perPage);

        return Inertia::render('Company/Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Display the specified conversation with its messages.
     *
     * @param Conversation $conversation
     * @param Request $request
     * @return Response
     */
    public function show(Conversation $conversation, Request $request): Response
    {
        // Authorize via ConversationPolicy
        $this->authorize('view', $conversation);

        $userId = Auth::guard('web')->id();
        $cursor = $request->input('cursor');
        $perPage = $request->input('per_page', 50);

        $messages = $this->chatService->getMessagesAndMarkRead(
            $conversation->id,
            $userId,
            $perPage,
            $cursor
        );

        // Load participants with necessary fields
        $conversation->load(['participants:id,first_name,last_name,avatar']);

        // Get user's conversations list for sidebar
        $conversations = $this->chatService->getUserConversations($userId, null, 20);

        return Inertia::render('Company/Chat/Show', [
            'conversation' => $conversation->toArray(),
            'messages' => $messages,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Create a new conversation or return existing one.
     *
     * @param CreateConversationRequest $request
     * @return JsonResponse
     */
    public function store(CreateConversationRequest $request): JsonResponse
    {
        try {
            $userId = Auth::guard('web')->id();
            $otherUserId = $request->validated()['user_id'];

            $conversation = $this->chatService->getOrCreateConversationByUser($userId, $otherUserId);

            return response()->json([
                'success' => true,
                'data' => $conversation->toArray(),
            ]);
        } catch (\App\Exceptions\ForbiddenException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 403,
            ], 403);
        } catch (\Exception $e) {
            Log::error('Chat error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع',
                'status_code' => 500,
            ], 500);
        }
    }
}
