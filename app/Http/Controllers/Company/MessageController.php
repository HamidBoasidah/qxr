<?php

namespace App\Http\Controllers\Company;

use App\DTOs\MarkReadDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SendMessageRequest;
use App\Http\Requests\Company\UploadAttachmentRequest;
use App\Models\Conversation;
use App\Services\AttachmentService;
use App\Services\ChatService;
use App\Services\ReadStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected ChatService $chatService;
    protected AttachmentService $attachmentService;
    protected ReadStateService $readStateService;

    /**
     * Create a new controller instance.
     *
     * @param ChatService $chatService
     * @param AttachmentService $attachmentService
     * @param ReadStateService $readStateService
     */
    public function __construct(
        ChatService $chatService,
        AttachmentService $attachmentService,
        ReadStateService $readStateService
    ) {
        $this->middleware(['auth:web', 'company']);
        $this->chatService = $chatService;
        $this->attachmentService = $attachmentService;
        $this->readStateService = $readStateService;
    }

    /**
     * Send a message in a conversation.
     *
     * @param SendMessageRequest $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        try {
            // Authorize: user must be participant in conversation
            $this->authorize('sendMessage', $conversation);

            // Get validated data
            $body = $request->input('body');
            $files = $request->file('files', []);

            // Send message using ChatService
            $messageDTO = $this->chatService->sendMessage(
                $conversation->id,
                $request->user()->id,
                $body,
                $files
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'status_code' => 200,
                'data' => $messageDTO->toArray(),
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإرسال رسائل في هذه المحادثة',
                'status_code' => 403,
            ], 403);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'status_code' => 422,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Company chat message send error', [
                'user_id' => $request->user()->id,
                'conversation_id' => $conversation->id,
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

    /**
     * Mark conversation as read (optional feature).
     *
     * @param Conversation $conversation
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(Conversation $conversation, Request $request): JsonResponse
    {
        try {
            // Authorize: user must be participant in conversation
            $this->authorize('view', $conversation);

            // Create MarkReadDTO
            $markReadDTO = new MarkReadDTO(
                conversationId: $conversation->id,
                userId: $request->user()->id,
                messageId: null // Will use latest message
            );

            // Mark as read
            $this->readStateService->markAsRead($markReadDTO);

            // Get updated unread count
            $unreadCount = $this->readStateService->getUnreadCount(
                $conversation->id,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة القراءة بنجاح',
                'status_code' => 200,
                'data' => [
                    'unread_count' => $unreadCount,
                ],
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول لهذه المحادثة',
                'status_code' => 403,
            ], 403);
        } catch (\Exception $e) {
            Log::error('Company chat mark as read error', [
                'user_id' => $request->user()->id,
                'conversation_id' => $conversation->id,
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

    /**
     * Upload attachments (optional feature).
     *
     * @param UploadAttachmentRequest $request
     * @return JsonResponse
     */
    public function upload(UploadAttachmentRequest $request): JsonResponse
    {
        try {
            $files = $request->file('files', []);

            // Validate files
            $this->attachmentService->validateFiles($files);

            // Store files temporarily and return file IDs
            // Note: This is a simplified implementation
            // In production, you might want to store files temporarily
            // and associate them with the message later
            $fileIds = [];
            foreach ($files as $index => $file) {
                $fileIds[] = [
                    'index' => $index,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملفات بنجاح',
                'status_code' => 200,
                'data' => [
                    'files' => $fileIds,
                ],
            ], 200);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'status_code' => 422,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Company chat file upload error', [
                'user_id' => $request->user()->id,
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
