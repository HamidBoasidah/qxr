# Requirements Document

## Introduction

هذه الوثيقة تحدد متطلبات تفعيل نظام المحادثات داخل لوحة تحكم الشركة. النظام الحالي يحتوي على API محادثات كامل وقالب Frontend جاهز، والهدف هو ربطهما معاً داخل لوحة الشركة مع الحفاظ على معايير الأمان والصلاحيات.

## Glossary

- **Company_User**: مستخدم مصادق عليه عبر guard 'web' يصل إلى واجهة المحادثات من خلال لوحة تحكم الشركة. هذا لا يعني أن المشاركة في المحادثات مقتصرة على نوع معين من المستخدمين
- **Chat_System**: نظام المحادثات الموجود حالياً والذي يعمل عبر API
- **Conversation**: محادثة بين مستخدمين أو أكثر، تحتوي على رسائل
- **Message**: رسالة نصية أو مرفق داخل محادثة
- **Attachment**: ملف مرفق مع رسالة
- **Unread_Count**: عدد الرسائل غير المقروءة في محادثة
- **Read_State**: حالة قراءة الرسائل لكل مستخدم في المحادثة
- **ChatService**: Service layer موجود يحتوي على منطق المحادثات
- **ConversationRepository**: Repository layer للوصول لبيانات المحادثات
- **MessageRepository**: Repository layer للوصول لبيانات الرسائل
- **ConversationPolicy**: Policy للتحقق من صلاحيات الوصول للمحادثات
- **Frontend_Template**: قالب Vue.js جاهز في resources/js/components/company/chat/
- **Inertia**: إطار عمل يربط Laravel بـ Vue.js بدون الحاجة لـ API منفصل

## Requirements

### Requirement 1: Company Chat Routes

**User Story:** كمطور، أريد routes محددة للشركات للوصول لنظام المحادثات، حتى تكون المحادثات متاحة داخل لوحة الشركة.

#### Acceptance Criteria

1. THE System SHALL provide route GET /company/chat/conversations لعرض صفحة قائمة المحادثات (Inertia)
2. THE System SHALL provide route GET /company/chat/conversations/{id} لعرض صفحة محادثة محددة مع رسائلها (Inertia)
3. THE System SHALL provide route POST /company/chat/conversations/{id}/messages لإرسال رسالة جديدة (JSON response)
4. THE System SHALL provide route POST /company/chat/conversations لإنشاء محادثة جديدة (JSON response)
5. WHERE file upload is enabled, THE System SHALL provide route POST /company/chat/messages/upload لرفع المرفقات (JSON response)
6. WHERE read tracking is enabled, THE System SHALL provide route POST /company/chat/conversations/{id}/read لتحديث حالة القراءة (JSON response)
7. WHEN accessing any chat route, THE System SHALL require authentication via 'web' guard
8. WHEN accessing any chat route, THE System SHALL apply 'company' middleware
9. THE company middleware SHALL be applied solely to protect Company Dashboard routes
10. THE System SHALL authorize chat participation exclusively via ConversationPolicy based on conversation membership, not user type

### Requirement 2: Company Chat Controllers

**User Story:** كمطور، أريد controllers مخصصة للشركات تستخدم نفس الـ Services الموجودة، حتى لا يتم تكرار منطق المحادثات.

#### Acceptance Criteria

1. THE System SHALL create Company\ConversationController that extends base Controller
2. THE System SHALL create Company\MessageController that extends base Controller
3. WHEN handling conversation requests, THE ConversationController SHALL use existing ChatService
4. WHEN handling message requests, THE MessageController SHALL use existing ChatService
5. WHEN handling read state requests, THE Controllers SHALL use existing ReadStateService
6. WHEN handling file uploads, THE MessageController SHALL use existing AttachmentService
7. WHEN returning page views, THE Controllers SHALL return Inertia responses
8. WHEN returning action responses, THE Controllers SHALL return JSON responses
9. WHEN an error occurs, THE Controllers SHALL handle exceptions consistently with other Company controllers
10. THE Controllers SHALL inject required services via constructor dependency injection

### Requirement 3: Conversation List Display

**User Story:** كمستخدم شركة، أريد رؤية قائمة محادثاتي، حتى أستطيع اختيار المحادثة التي أريد فتحها.

#### Acceptance Criteria

1. WHEN Company_User accesses /company/chat/conversations, THE System SHALL display list of their conversations
2. WHEN displaying conversations, THE System SHALL show participant names for each conversation
3. WHEN displaying conversations, THE System SHALL show last message preview for each conversation
4. WHEN displaying conversations, THE System SHALL show unread count for each conversation
5. WHEN displaying conversations, THE System SHALL show timestamp of last message
6. WHEN displaying conversations, THE System SHALL order conversations by most recent activity first
7. WHERE search parameter is provided, THE System SHALL filter conversations by participant name
8. WHEN conversation list is empty, THE System SHALL display appropriate empty state message
9. THE System SHALL paginate conversation list with 20 items per page

### Requirement 4: Conversation Access Authorization

**User Story:** كمستخدم شركة، أريد الوصول فقط للمحادثات التي أنا مشارك فيها، حتى تكون بياناتي آمنة.

#### Acceptance Criteria

1. WHEN Company_User attempts to view a conversation, THE System SHALL verify user is participant using ConversationPolicy
2. IF Company_User is not participant in conversation, THEN THE System SHALL return 403 Forbidden error
3. WHEN Company_User attempts to send message, THE System SHALL verify user is participant using ConversationPolicy
4. IF Company_User is not participant when sending message, THEN THE System SHALL return 403 Forbidden error
5. THE System SHALL authorize viewing conversations and sending messages using existing ConversationPolicy (participant-based authorization)
6. THE System SHALL not introduce new policy methods for Company chat authorization

### Requirement 5: Message Display and Pagination

**User Story:** كمستخدم شركة، أريد رؤية رسائل المحادثة مع إمكانية تحميل الرسائل القديمة، حتى أستطيع متابعة المحادثة كاملة.

#### Acceptance Criteria

1. WHEN Company_User opens a conversation, THE System SHALL display messages in chronological order
2. WHEN displaying messages, THE System SHALL show sender name for each message
3. WHEN displaying messages, THE System SHALL show message timestamp
4. WHEN displaying messages, THE System SHALL show message body text
5. WHERE message has attachments, THE System SHALL display attachment information
6. THE System SHALL paginate messages with 50 messages per page using cursor pagination
7. WHEN Company_User scrolls to top, THE System SHALL load previous messages automatically
8. WHEN opening conversation, THE System SHALL mark messages as read automatically
9. WHEN marking as read, THE System SHALL update unread count for that conversation

### Requirement 6: Send Text Messages

**User Story:** كمستخدم شركة، أريد إرسال رسائل نصية في المحادثة، حتى أستطيع التواصل مع المشاركين الآخرين.

#### Acceptance Criteria

1. WHEN Company_User submits message form, THE System SHALL validate message body is not empty
2. WHEN Company_User submits valid message, THE System SHALL create message using ChatService
3. WHEN creating message, THE System SHALL set sender_id to authenticated Company_User id
4. WHEN creating message, THE System SHALL set conversation_id to current conversation
5. WHEN message is created successfully, THE System SHALL return JSON response with message data
6. IF message body is empty or only whitespace, THEN THE System SHALL reject message and return validation error
7. WHEN sending message, THE System SHALL use existing ChatService.sendMessage method

### Requirement 7: File Attachments (Optional Feature)

**User Story:** كمستخدم شركة، أريد رفع ملفات مع الرسائل، حتى أستطيع مشاركة مستندات أو صور.

#### Acceptance Criteria

1. WHERE file upload is enabled, WHEN Company_User selects files, THE System SHALL validate file types and sizes
2. WHERE file upload is enabled, WHEN Company_User sends message with files, THE System SHALL store files using AttachmentService
3. WHERE file upload is enabled, WHEN storing files, THE System SHALL associate files with message_id
4. WHERE file upload is enabled, WHEN displaying message with attachments, THE System SHALL show attachment names and download links
5. WHERE file upload is enabled, IF file validation fails, THEN THE System SHALL return validation error with specific reason
6. WHERE file upload is enabled, THE System SHALL use existing AttachmentService without modification

### Requirement 8: Create New Conversation

**User Story:** كمستخدم شركة، أريد بدء محادثة جديدة مع مستخدم آخر، حتى أستطيع التواصل معه.

#### Acceptance Criteria

1. WHEN Company_User initiates new conversation with user_id, THE System SHALL check if conversation already exists
2. IF conversation exists between users, THEN THE System SHALL return existing conversation as JSON
3. IF conversation does not exist, THEN THE System SHALL create new conversation using ChatService
4. WHEN creating conversation, THE System SHALL add both users as participants
5. IF Company_User attempts to create conversation with themselves, THEN THE System SHALL return validation error
6. WHEN conversation is created or retrieved, THE System SHALL return JSON response with conversation data

### Requirement 9: Frontend Template Integration

**User Story:** كمطور، أريد ربط قالب Vue.js الموجود مع الباك-إند، حتى يعمل نظام المحادثات بشكل كامل.

#### Acceptance Criteria

1. THE System SHALL use existing Vue components in resources/js/components/company/chat/
2. WHEN rendering chat interface, THE System SHALL pass conversation data as Inertia props
3. WHEN rendering chat interface, THE System SHALL pass messages data as Inertia props
4. WHEN rendering chat interface, THE System SHALL pass authenticated user data as Inertia props
5. WHEN user interacts with chat UI, THE Frontend SHALL send requests to Company chat routes
6. WHEN Frontend receives responses, THE System SHALL update UI reactively using Vue
7. THE Frontend SHALL replace dummy data with actual API calls to backend routes
8. WHEN displaying timestamps, THE Frontend SHALL format dates in Arabic locale

### Requirement 10: Read State Tracking (Optional Feature)

**User Story:** كمستخدم شركة، أريد معرفة الرسائل غير المقروءة، حتى أعرف المحادثات التي تحتاج انتباهي.

#### Acceptance Criteria

1. WHERE read tracking is enabled, WHEN Company_User opens conversation, THE System SHALL mark all messages as read
2. WHERE read tracking is enabled, WHEN marking as read, THE System SHALL use ReadStateService
3. WHERE read tracking is enabled, WHEN displaying conversation list, THE System SHALL show unread count badge
4. WHERE read tracking is enabled, WHEN unread count is zero, THE System SHALL not display badge
5. WHERE read tracking is enabled, WHEN message is marked as read, THE System SHALL update read_states table
6. WHERE read tracking is enabled, THE System SHALL calculate unread count as messages after user's last read message

### Requirement 11: UI Layout Integration

**User Story:** كمستخدم شركة، أريد الوصول للمحادثات من قائمة لوحة التحكم، حتى يكون النظام سهل الاستخدام.

#### Acceptance Criteria

1. THE System SHALL add chat navigation link to Company sidebar menu
2. WHEN Company_User clicks chat link, THE System SHALL navigate to /company/chat/conversations
3. THE System SHALL display chat icon in sidebar menu
4. WHEN on chat page, THE System SHALL highlight chat menu item as active
5. THE System SHALL maintain consistent styling with other Company dashboard pages
6. THE System SHALL use existing Company layout component for chat pages

### Requirement 12: Error Handling and Validation

**User Story:** كمستخدم شركة، أريد رسائل خطأ واضحة عند حدوث مشاكل، حتى أعرف كيف أصلح المشكلة.

#### Acceptance Criteria

1. WHEN validation error occurs, THE System SHALL return error message in Arabic
2. WHEN authorization fails, THE System SHALL return 403 error with clear message
3. WHEN conversation not found, THE System SHALL return 404 error with clear message
4. WHEN server error occurs, THE System SHALL log error details and return generic error message
5. WHEN Frontend receives error, THE System SHALL display error notification to user
6. THE System SHALL use consistent error handling pattern with other Company controllers

### Requirement 13: Performance and Optimization

**User Story:** كمطور، أريد أن يكون نظام المحادثات سريع وفعال، حتى لا يؤثر على تجربة المستخدم.

#### Acceptance Criteria

1. WHEN loading conversation list, THE System SHALL eager load participants and last message
2. WHEN loading messages, THE System SHALL eager load sender and attachments
3. THE System SHALL use cursor pagination for messages to handle large conversations efficiently
4. THE System SHALL use database indexes on conversation_id and sender_id for fast queries
5. WHEN calculating unread counts, THE System SHALL use optimized query from ReadStateService

### Requirement 14: Data Consistency

**User Story:** كمطور، أريد ضمان تناسق البيانات عند إرسال الرسائل، حتى لا تحدث مشاكل في حالة الطلبات المتزامنة.

#### Acceptance Criteria

1. WHEN sending message, THE System SHALL use database transaction
2. IF transaction fails, THEN THE System SHALL rollback all changes
3. WHEN storing attachments, THE System SHALL include attachment storage in same transaction
4. THE System SHALL use existing ChatService transaction logic without modification

### Requirement 15: Code Organization and Maintainability

**User Story:** كمطور، أريد أن يكون الكود منظم ويتبع معايير المشروع، حتى يسهل صيانته مستقبلاً.

#### Acceptance Criteria

1. THE System SHALL follow existing project structure with Services/Repositories/DTOs pattern
2. THE System SHALL not duplicate chat logic between API and Company controllers
3. THE System SHALL reuse existing ChatService, ReadStateService, and AttachmentService
4. THE System SHALL reuse existing ConversationPolicy without modification
5. THE System SHALL use dependency injection for all services
6. THE System SHALL follow PSR-12 coding standards
7. THE System SHALL include PHPDoc comments for all public methods
8. THE System SHALL use type hints for all method parameters and return types
