# Design Document: Company Chat Integration

## Overview

هذا التصميم يوضح كيفية تفعيل نظام المحادثات داخل لوحة تحكم الشركة من خلال إنشاء طبقة Company Controllers جديدة تستخدم نفس Services/Repositories/DTOs الموجودة حالياً في النظام.

### Design Principles

1. **No Logic Duplication**: استخدام ChatService, ReadStateService, AttachmentService الموجودة بدون تعديل
2. **Separation of Concerns**: فصل واضح بين API routes و Company Dashboard routes
3. **Consistent Authorization**: استخدام ConversationPolicy الموجودة للتحقق من المشاركة
4. **Hybrid Response Strategy**: Inertia للصفحات، JSON للـ actions
5. **Existing Patterns**: اتباع نفس أنماط المشروع الحالية (PSR-12, Type Hints, Dependency Injection)

### Key Architectural Decisions

**Decision 1: Two Separate Controllers**
- `Company\ConversationController`: يتعامل مع عرض المحادثات وقائمة المحادثات
- `Company\MessageController`: يتعامل مع إرسال الرسائل ورفع المرفقات

**Rationale**: فصل المسؤوليات بشكل واضح، مما يسهل الصيانة والتوسع المستقبلي.

**Decision 2: Inertia for Pages, JSON for Actions**
- GET routes (index, show) → Inertia responses
- POST routes (store message, upload, mark read) → JSON responses

**Rationale**: الصفحات تحتاج SSR وتكامل مع Layout، بينما الـ actions تحتاج استجابة سريعة للـ AJAX calls.

**Decision 3: Reuse Existing Services Without Modification**
- لا تعديل على ChatService أو ReadStateService أو AttachmentService
- Company Controllers تستدعي نفس الـ methods المستخدمة في API Controllers

**Rationale**: تجنب تكرار المنطق وضمان consistency في السلوك بين API و Company Dashboard.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Company Dashboard                         │
│                  (Inertia + Vue.js)                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ HTTP Requests
                     │
┌────────────────────▼────────────────────────────────────────┐
│                  Company Routes                              │
│         (web guard + company middleware)                     │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
┌───────▼──────────┐    ┌────────▼─────────┐
│ Conversation     │    │ Message          │
│ Controller       │    │ Controller       │
└───────┬──────────┘    └────────┬─────────┘
        │                        │
        │  ┌─────────────────────┘
        │  │
        │  │  Uses (Dependency Injection)
        │  │
┌───────▼──▼──────────────────────────────────┐
│         Service Layer                        │
│  - ChatService                               │
│  - ReadStateService                          │
│  - AttachmentService                         │
└───────┬──────────────────────────────────────┘
        │
        │  Uses
        │
┌───────▼──────────────────────────────────────┐
│      Repository Layer                        │
│  - ConversationRepository                    │
│  - MessageRepository                         │
└───────┬──────────────────────────────────────┘
        │
        │  Accesses
        │
┌───────▼──────────────────────────────────────┐
│         Database Models                      │
│  - Conversation                              │
│  - Message                                   │
│  - MessageAttachment                         │
│  - ConversationParticipant (read state)     │
└──────────────────────────────────────────────┘
```

### Request Flow

#### Page View Flow (GET requests)

```
User → Browser → GET /company/chat/conversations
                      ↓
              ConversationController@index
                      ↓
              ChatService::getUserConversations()
                      ↓
              ConversationRepository
                      ↓
              Database Query (with eager loading)
                      ↓
              DTOs (ConversationListDTO)
                      ↓
              Inertia::render('Company/Chat/Index')
                      ↓
              Vue Component (ChatSidebar.vue)
                      ↓
              Rendered HTML → Browser
```

#### Action Flow (POST requests)

```
User → Browser → POST /company/chat/conversations/{id}/messages
                      ↓
              MessageController@store
                      ↓
              Validation (SendMessageRequest)
                      ↓
              Authorization (ConversationPolicy)
                      ↓
              ChatService::sendMessage()
                      ↓
              DB Transaction
                      ↓
              MessageRepository::create()
                      ↓
              AttachmentService (if files present)
                      ↓
              MessageDTO
                      ↓
              JSON Response
                      ↓
              Vue Component updates reactively
```

### Authentication & Authorization Flow

```
Request → web guard (Auth::guard('web'))
              ↓
         Authenticated?
              ↓ Yes
         company middleware
              ↓
         Dashboard access granted
              ↓
         Controller Method
              ↓
         ConversationPolicy::view() (participant-based)
              ↓
         Is Participant?
              ↓ Yes
         Execute Business Logic
```

## Components and Interfaces

### Company\ConversationController

**Purpose**: إدارة عرض المحادثات وقائمة المحادثات

**Dependencies**:
- `ChatService`: للوصول لمنطق المحادثات
- `ReadStateService`: لحساب الرسائل غير المقروءة

**Methods**:

```php
public function index(Request $request): Response
```
- **Input**: `search` (optional string), `per_page` (optional int, default 20)
- **Output**: Inertia response with conversations list
- **Logic**:
  1. Get authenticated user ID
  2. Call `ChatService::getUserConversations()`
  3. Return Inertia view with conversations data
- **Authorization**: Authenticated via web guard + company middleware

```php
public function show(Conversation $conversation): Response
```
- **Input**: `conversation` (route model binding), `cursor` (optional string)
- **Output**: Inertia response with conversation and messages
- **Logic**:
  1. Authorize via `ConversationPolicy::view()`
  2. Call `ChatService::getMessagesAndMarkRead()`
  3. Return Inertia view with conversation and messages
- **Authorization**: ConversationPolicy checks participant membership

```php
public function store(CreateConversationRequest $request): JsonResponse
```
- **Input**: `user_id` (int)
- **Output**: JSON response with conversation data
- **Logic**:
  1. Validate user_id
  2. Call `ChatService::getOrCreateConversationByUser()`
  3. Return JSON with ConversationDTO
- **Authorization**: Authenticated via web guard

### Company\MessageController

**Purpose**: إدارة إرسال الرسائل ورفع المرفقات

**Dependencies**:
- `ChatService`: لإرسال الرسائل
- `AttachmentService`: لرفع المرفقات
- `ReadStateService`: لتحديث حالة القراءة

**Methods**:

```php
public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
```
- **Input**: `conversation` (route model binding), `body` (optional string), `attachments` (optional array of files)
- **Output**: JSON response with message data
- **Logic**:
  1. Authorize via `ConversationPolicy::view()`
  2. Validate request (body or attachments required)
  3. Call `ChatService::sendMessage()`
  4. Return JSON with MessageDTO
- **Authorization**: ConversationPolicy checks participant membership

```php
public function upload(UploadAttachmentRequest $request): JsonResponse
```
- **Input**: `files` (array of UploadedFile)
- **Output**: JSON response with temporary file IDs
- **Logic**:
  1. Validate files via `AttachmentService::validateFiles()`
  2. Store files temporarily
  3. Return file IDs for later association with message
- **Authorization**: Authenticated via web guard
- **Note**: This is optional feature, can be implemented later

```php
public function markAsRead(Conversation $conversation, Request $request): JsonResponse
```
- **Input**: `conversation` (route model binding)
- **Output**: JSON response with updated unread count
- **Logic**:
  1. Authorize via `ConversationPolicy::view()`
  2. Create MarkReadDTO
  3. Call `ReadStateService::markAsRead()`
  4. Return JSON with updated unread count
- **Authorization**: ConversationPolicy checks participant membership
- **Note**: This is optional feature, can be implemented later

### Routes Definition

**File**: `routes/company.php` (or add to existing company routes file)

```php
Route::middleware(['auth:web', 'company'])->prefix('company/chat')->name('company.chat.')->group(function () {
    // Page views (Inertia)
    Route::get('/conversations', [ConversationController::class, 'index'])
        ->name('conversations.index');
    
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
        ->name('conversations.show');
    
    // Actions (JSON)
    Route::post('/conversations', [ConversationController::class, 'store'])
        ->name('conversations.store');
    
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->name('messages.store');
    
    // Optional features
    Route::post('/conversations/{conversation}/read', [MessageController::class, 'markAsRead'])
        ->name('conversations.read');
    
    Route::post('/messages/upload', [MessageController::class, 'upload'])
        ->name('messages.upload');
});
```

### Request Validation

**CreateConversationRequest**:
```php
public function rules(): array
{
    return [
        'user_id' => ['required', 'integer', 'exists:users,id', 'different:' . auth()->id()],
    ];
}
```

**SendMessageRequest**:
```php
public function rules(): array
{
    return [
        'body' => ['nullable', 'string', 'max:5000', 'required_without:attachments'],
        'attachments' => ['nullable', 'array', 'max:5', 'required_without:body'],
        'attachments.*' => ['file', 'max:10240'], // 10MB max per file
    ];
}
```

**UploadAttachmentRequest** (Optional):
```php
public function rules(): array
{
    return [
        'files' => ['required', 'array', 'max:5'],
        'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
    ];
}
```

### Frontend Components Integration

**Existing Vue Components** (in `resources/js/components/company/chat/`):
- `ChatSidebar.vue`: القائمة الجانبية للمحادثات
- `ChatList.vue`: قائمة المحادثات
- `ChatListItem.vue`: عنصر واحد في قائمة المحادثات
- `ChatHeader.vue`: رأس المحادثة
- `ChatBox.vue`: صندوق الرسائل

**Required Changes**:
1. استبدال البيانات الوهمية بـ props من Inertia
2. إضافة Axios calls للـ POST actions
3. ربط الـ events (send message, upload file, mark read)
4. إضافة reactive updates عند استلام responses

**Inertia Page Components** (to be created):
- `resources/js/Pages/Company/Chat/Index.vue`: صفحة قائمة المحادثات
- `resources/js/Pages/Company/Chat/Show.vue`: صفحة المحادثة

**Props Structure**:

For `Index.vue`:
```typescript
interface Props {
  conversations: {
    data: ConversationListDTO[];
    pagination: {
      current_page: number;
      last_page: number;
      per_page: number;
      total: number;
    };
  };
  auth: {
    user: {
      id: number;
      name: string;
      avatar: string | null;
    };
  };
}
```

For `Show.vue`:
```typescript
interface Props {
  conversation: ConversationDTO;
  messages: {
    data: MessageDTO[];
    meta: {
      next_cursor: string | null;
      prev_cursor: string | null;
      per_page: number;
      unread_count: number;
    };
  };
  auth: {
    user: {
      id: number;
      name: string;
      avatar: string | null;
    };
  };
}
```

## Data Models

### Existing Models (No Changes Required)

**Note**: The following are descriptions of existing models. All models should be reused as-is without modification.

**Conversation Model**:
- Has many-to-many relationship with User via `conversation_participants` pivot table
- Has many messages
- Provides `isParticipant(int $userId)` method to check membership
- Pivot table includes `last_read_message_id` and `last_read_at` for read tracking

**Message Model**:
- Belongs to Conversation
- Belongs to User (sender)
- Has many attachments via `message_attachments` table
- Has `type` field: 'text', 'attachment', or 'mixed'

**Attachment Model**:
- Belongs to Message
- Stored in `message_attachments` table
- Contains file metadata (name, path, type, size)

**Read State Tracking**:
- Implemented via `conversation_participants` pivot table
- Columns: `last_read_message_id`, `last_read_at`
- Updated by ReadStateService when user views conversation

### DTOs (Existing, No Changes)

**ConversationDTO**: يحتوي على id, participants, last_message, unread_count, timestamps

**ConversationListDTO**: نسخة مبسطة من ConversationDTO للقوائم

**MessageDTO**: يحتوي على id, conversation_id, sender, body, type, attachments, timestamps

**AttachmentDTO**: يحتوي على id, file_name, file_path, file_type, file_size

**MarkReadDTO**: يحتوي على conversation_id, user_id, last_read_message_id (used to update conversation_participants pivot table)

### Database Schema (Existing)

**conversations table**:
- id (primary key)
- created_at
- updated_at

**conversation_participants pivot table**:
- conversation_id (foreign key)
- user_id (foreign key)
- last_read_message_id (foreign key to messages, nullable)
- last_read_at (timestamp, nullable)
- joined_at (timestamp)

**messages table**:
- id (primary key)
- conversation_id (foreign key)
- sender_id (foreign key to users)
- body (text, nullable)
- type (enum: 'text', 'attachment', 'mixed')
- created_at
- updated_at

**message_attachments table**:
- id (primary key)
- message_id (foreign key)
- file_name (string)
- file_path (string)
- file_type (string)
- file_size (integer)
- created_at
- updated_at

**read tracking via conversation_participants**:
- last_read_message_id (foreign key to messages, nullable)
- last_read_at (timestamp, nullable)

The system tracks read state by storing the last read message ID and timestamp in the conversation_participants pivot table. The ReadStateService updates these columns when a user views a conversation.

### Indexes (Existing)

- `messages.conversation_id` (for fast conversation message lookup)
- `messages.sender_id` (for fast sender message lookup)
- `conversation_participants.conversation_id` (for fast participant lookup)
- `conversation_participants.user_id` (for fast user conversations lookup)
- `conversation_participants.conversation_id, conversation_participants.user_id` (unique composite for participant membership)


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: User Conversations Retrieval

*For any* authenticated Company user, when they request their conversation list, the system should return only conversations where they are a participant.

**Validates: Requirements 3.1**

### Property 2: Conversation Participant Information

*For any* conversation in the response, the conversation data should include names of all participants.

**Validates: Requirements 3.2**

### Property 3: Conversation Last Message

*For any* conversation that has messages, the conversation data should include the last message with its complete information (body, sender, timestamp).

**Validates: Requirements 3.3**

### Property 4: Conversation Unread Count

*For any* conversation in the response, the conversation data should include an unread_count field with a non-negative integer value.

**Validates: Requirements 3.4**

### Property 5: Conversation Ordering

*For any* list of conversations returned, they should be ordered by most recent activity (last message timestamp) in descending order.

**Validates: Requirements 3.6**

### Property 6: Conversation Search Filtering

*For any* search query provided, all returned conversations should have at least one participant whose name contains the search term (case-insensitive).

**Validates: Requirements 3.7**

### Property 7: Non-Participant View Authorization

*For any* conversation and any user who is not a participant in that conversation, attempting to view the conversation should result in a 403 Forbidden error.

**Validates: Requirements 4.1, 4.2**

### Property 8: Non-Participant Send Authorization

*For any* conversation and any user who is not a participant in that conversation, attempting to send a message should result in a 403 Forbidden error.

**Validates: Requirements 4.3, 4.4**

### Property 9: Message Chronological Ordering

*For any* conversation, when messages are retrieved, they should be ordered by created_at timestamp in ascending order (oldest first).

**Validates: Requirements 5.1**

### Property 10: Message Sender Information

*For any* message in the response, the message data should include sender information (id, name, avatar).

**Validates: Requirements 5.2**

### Property 11: Message Body Content

*For any* message with a body, the message data should include the body text content.

**Validates: Requirements 5.4**

### Property 12: Message Attachment Information

*For any* message that has attachments, the message data should include attachment information (file_name, file_path, file_type, file_size) for each attachment.

**Validates: Requirements 5.5**

### Property 13: Read State Update on View

*For any* conversation, when a user views it, the system should mark all messages as read for that user and the unread_count should become 0 for that conversation.

**Validates: Requirements 5.8, 5.9**

### Property 14: Empty Message Rejection

*For any* message submission where the body is empty or contains only whitespace and no attachments are provided, the system should reject the message with a validation error.

**Validates: Requirements 6.1, 6.6**

### Property 15: Message Sender Identity

*For any* message created by an authenticated user, the sender_id should match the authenticated user's id.

**Validates: Requirements 6.3**

### Property 16: Invalid File Rejection

*For any* file upload where the file type is not allowed or the file size exceeds the limit, the system should reject the upload with a validation error.

**Validates: Requirements 7.1**

### Property 17: Attachment Message Association

*For any* message created with attachments, all attachments should have their message_id set to the created message's id.

**Validates: Requirements 7.3**

### Property 18: Conversation Creation Idempotence

*For any* two users, creating a conversation between them multiple times should return the same conversation (idempotent operation).

**Validates: Requirements 8.1, 8.2**

### Property 19: Conversation Participant Membership

*For any* newly created conversation between two users, both users should be participants in that conversation.

**Validates: Requirements 8.4**

### Property 20: Unread Count Accuracy

*For any* conversation and user, the unread_count should equal the number of messages in that conversation created after the user's last_read_message_id.

**Validates: Requirements 10.6**

### Property 21: Arabic Validation Messages

*For any* validation error response, the error message should be in Arabic language.

**Validates: Requirements 12.1**

### Property 22: Message Creation Atomicity

*For any* message creation with attachments, either all changes (message + attachments) should be persisted or none should be persisted (atomic transaction).

**Validates: Requirements 14.2**

## Error Handling

### Error Response Format

**For JSON Action Routes** (POST requests):

All error responses should follow a consistent JSON structure:

```json
{
  "success": false,
  "message": "رسالة الخطأ بالعربية",
  "status_code": 400,
  "errors": {
    "field_name": ["رسالة الخطأ التفصيلية"]
  }
}
```

**For Inertia Page Routes** (GET requests):

Validation errors use standard Laravel/Inertia error bags and redirects. Authorization errors (403) and not found errors (404) return appropriate HTTP status codes with Inertia error pages.

### Error Types

**Validation Errors (422)**:
- Empty message body without attachments
- Invalid file type or size
- Missing required fields
- Self-conversation attempt

**Authorization Errors (403)**:
- Non-participant attempting to view conversation
- Non-participant attempting to send message
- Unauthorized access to Company dashboard

**Authentication Errors (401)**:
- Unauthenticated request to protected route
- Invalid or expired session

**Not Found Errors (404)**:
- Conversation does not exist
- Message does not exist
- User does not exist

**Server Errors (500)**:
- Database connection failure
- File storage failure
- Unexpected exceptions

### Error Handling Strategy

1. **Controller Level**: Catch and transform exceptions into appropriate HTTP responses
2. **Service Level**: Throw domain-specific exceptions (ForbiddenException, ValidationException)
3. **Logging**: Log all errors with context (user_id, conversation_id, stack trace)
4. **User Feedback**: Return clear, actionable error messages in Arabic
5. **Rollback**: Use database transactions to ensure data consistency on errors

### Exception Handling Pattern

```php
try {
    $result = $this->chatService->sendMessage(...);
    return response()->json([
        'success' => true,
        'data' => $result->toArray(),
    ]);
} catch (ForbiddenException $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'status_code' => 403,
    ], 403);
} catch (ValidationException $e) {
    return response()->json([
        'success' => false,
        'message' => 'خطأ في البيانات المدخلة',
        'status_code' => 422,
        'errors' => $e->errors(),
    ], 422);
} catch (\Exception $e) {
    Log::error('Chat error', [
        'user_id' => auth()->id(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    return response()->json([
        'success' => false,
        'message' => 'حدث خطأ غير متوقع',
        'status_code' => 500,
    ], 500);
}
```

## Testing Strategy

### Dual Testing Approach

This feature requires both unit tests and property-based tests for comprehensive coverage:

**Unit Tests**: Focus on specific examples, edge cases, and integration points
- Test specific conversation scenarios (empty list, single conversation, multiple conversations)
- Test specific message scenarios (text only, attachments only, mixed)
- Test error conditions (404, 403, 422, 500)
- Test middleware and authorization integration
- Test Inertia response structure
- Test JSON response structure

**Property-Based Tests**: Focus on universal properties across all inputs
- Test properties that hold for all conversations (ordering, participant info, unread counts)
- Test properties that hold for all messages (sender info, chronological order)
- Test authorization properties for all user/conversation combinations
- Test validation properties for all input variations
- Test idempotence properties for conversation creation

### Property-Based Testing Configuration

**Framework**: Use [Pest PHP](https://pestphp.com/) with [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) for property-based testing in Laravel.

**Configuration**:
- Minimum 100 iterations per property test
- Each property test must reference its design document property
- Tag format: `Feature: company-chat-integration, Property {number}: {property_text}`

**Example Property Test**:

```php
use function Pest\Faker\fake;

it('returns only user conversations', function () {
    // Feature: company-chat-integration, Property 1: User Conversations Retrieval
    
    // Generate random test data
    $user = User::factory()->create();
    $otherUsers = User::factory()->count(5)->create();
    
    // Create conversations where user is participant
    $userConversations = collect();
    foreach ($otherUsers->take(3) as $otherUser) {
        $conv = Conversation::factory()->create();
        $conv->participants()->attach([$user->id, $otherUser->id]);
        $userConversations->push($conv);
    }
    
    // Create conversations where user is NOT participant
    $otherConversations = collect();
    foreach ($otherUsers->skip(3) as $otherUser) {
        $conv = Conversation::factory()->create();
        $conv->participants()->attach([$otherUsers[0]->id, $otherUser->id]);
        $otherConversations->push($conv);
    }
    
    // Act
    $response = $this->actingAs($user, 'web')
        ->get(route('company.chat.conversations.index'));
    
    // Assert: only user's conversations are returned
    $response->assertOk();
    $returnedIds = collect($response->json('conversations.data'))->pluck('id');
    
    expect($returnedIds->count())->toBe($userConversations->count());
    expect($returnedIds->diff($userConversations->pluck('id')))->toBeEmpty();
    expect($returnedIds->intersect($otherConversations->pluck('id')))->toBeEmpty();
})->repeat(100);
```

### Unit Test Examples

**Test Conversation List Page**:
```php
it('renders conversation list page with Inertia', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user, 'web')
        ->get(route('company.chat.conversations.index'));
    
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Company/Chat/Index')
        ->has('conversations.data')
        ->has('conversations.pagination')
        ->has('auth.user')
    );
});
```

**Test Unauthorized Access**:
```php
it('returns 403 when non-participant tries to view conversation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach($otherUser->id);
    
    $response = $this->actingAs($user, 'web')
        ->get(route('company.chat.conversations.show', $conversation));
    
    $response->assertForbidden();
});
```

**Test Message Creation**:
```php
it('creates message and returns JSON response', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach($user->id);
    
    $response = $this->actingAs($user, 'web')
        ->postJson(route('company.chat.messages.store', $conversation), [
            'body' => 'Test message',
        ]);
    
    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'data' => [
            'id',
            'conversation_id',
            'sender',
            'body',
            'created_at',
        ],
    ]);
    
    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_id' => $user->id,
        'body' => 'Test message',
    ]);
});
```

### Test Coverage Goals

- **Unit Test Coverage**: 80%+ of controller code
- **Property Test Coverage**: All 22 correctness properties
- **Integration Test Coverage**: All routes and middleware combinations
- **Edge Case Coverage**: Empty states, pagination boundaries, file size limits

### Testing Best Practices

1. **Use Factories**: Create test data with factories for consistency
2. **Isolate Tests**: Each test should be independent and not rely on other tests
3. **Clean Database**: Use database transactions or refresh database between tests
4. **Mock External Services**: Mock file storage and external APIs
5. **Test Both Success and Failure**: Test happy paths and error conditions
6. **Use Descriptive Names**: Test names should clearly describe what is being tested
7. **Avoid Over-Mocking**: Test real service integration where possible
8. **Test Authorization**: Always test both authorized and unauthorized access
9. **Test Validation**: Test all validation rules with valid and invalid data
10. **Property Test Randomization**: Use random data generation for property tests to cover edge cases
