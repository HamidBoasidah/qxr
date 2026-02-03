# وثيقة التصميم

## نظرة عامة

إزالة كاملة لكل ما يتعلق بالحجوزات (booking) من نظام المحادثات. التعديلات تشمل:
- إزالة عمود `booking_id` من جدول conversations
- إزالة عمود `context` من جدول messages
- إعادة كتابة `ChatService` لإزالة كل منطق الحجز والجلسات
- تبسيط `ConversationPolicy` للتحقق من المشاركة فقط
- تعديل `ConversationRepository` لدعم إنشاء محادثات بين مستخدمين
- تعديل DTOs و Resources لإزالة الحقول المحذوفة

## الهندسة المعمارية

### المكونات المتأثرة

```
┌─────────────────────────────────────────────────────────────┐
│                     API Layer                                │
│  ┌─────────────────────┐  ┌─────────────────────────────┐   │
│  │ConversationController│  │    MessageController        │   │
│  │  (إضافة store)      │  │    (بدون تغيير)            │   │
│  └─────────────────────┘  └─────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │                   ChatService                        │    │
│  │  - حذف getOrCreateConversation()                    │    │
│  │  - حذف isInSession()                                │    │
│  │  - حذف canSendMessage()                             │    │
│  │  - حذف countClientOutOfSessionMessages()            │    │
│  │  - تبسيط sendMessage()                              │    │
│  │  - إضافة getOrCreateConversationByUser()           │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  Repository Layer                            │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              ConversationRepository                  │    │
│  │  - حذف findByBooking()                              │    │
│  │  - تعديل createWithParticipants()                   │    │
│  │  - إضافة findByParticipants()                       │    │
│  │  - إضافة createWithParticipantsOnly()              │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Policy Layer                               │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              ConversationPolicy                      │    │
│  │  - إزالة use Booking                                │    │
│  │  - تبسيط sendMessage()                              │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Data Layer                                 │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              Database Migration                      │    │
│  │  - حذف booking_id من conversations                  │    │
│  │  - حذف context من messages                          │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

## المكونات والواجهات

### 1. ChatService - إعادة الكتابة

#### الدوال المحذوفة:
- `getOrCreateConversation(int $bookingId, int $userId)` - مرتبطة بالحجز
- `isInSession(Booking $booking): bool` - مرتبطة بالجلسة
- `countClientOutOfSessionMessages(int $conversationId, int $clientId): int` - مرتبطة بالحدود
- `canSendMessage(Conversation $conversation, int $senderId, bool $isInSession): bool` - مرتبطة بالحجز

#### الدوال الجديدة/المُعدّلة:

```php
/**
 * إنشاء أو جلب محادثة بين مستخدمين
 * 
 * @param int $userId المستخدم الحالي
 * @param int $otherUserId المستخدم الآخر
 * @return ConversationDTO
 * @throws ForbiddenException إذا حاول المستخدم محادثة نفسه
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
 * إرسال رسالة (مُبسّط - بدون قيود)
 */
public function sendMessage(
    int $conversationId,
    int $senderId,
    ?string $body,
    array $uploadedFiles
): MessageDTO
{
    return DB::transaction(function () use ($conversationId, $senderId, $body, $uploadedFiles) {
        $conversation = Conversation::with(['participants'])
            ->lockForUpdate()
            ->findOrFail($conversationId);

        if (!$conversation->isParticipant($senderId)) {
            throw new ForbiddenException('أنت لست مشاركاً في هذه المحادثة');
        }

        if (!empty($uploadedFiles)) {
            $this->attachments->validateFiles($uploadedFiles);
        }

        $type = $this->determineMessageType($body, $uploadedFiles);

        $message = $this->messages->create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
            'type' => $type,
        ]);

        if (!empty($uploadedFiles)) {
            $this->attachments->storeAttachments($message->id, $uploadedFiles);
            $message = $message->fresh(['sender', 'attachments']);
        }

        return MessageDTO::fromModel($message);
    });
}
```

### 2. ConversationRepository - التعديلات

```php
// حذف defaultWith booking
protected array $defaultWith = [
    'participants',
];

// حذف findByBooking()

/**
 * البحث عن محادثة بين مستخدمين
 */
public function findByParticipants(int $userId1, int $userId2): ?Conversation
{
    return $this->model
        ->whereHas('participants', function ($query) use ($userId1) {
            $query->where('user_id', $userId1);
        })
        ->whereHas('participants', function ($query) use ($userId2) {
            $query->where('user_id', $userId2);
        })
        ->withCount('participants')
        ->having('participants_count', '=', 2)
        ->first();
}

/**
 * إنشاء محادثة مع مشاركين فقط
 */
public function createWithParticipantsOnly(int $userId1, int $userId2): Conversation
{
    return DB::transaction(function () use ($userId1, $userId2) {
        $conversation = $this->create([]);

        $conversation->participants()->attach([$userId1, $userId2]);

        return $conversation->load(['participants']);
    });
}
```

### 3. ConversationPolicy - التبسيط

```php
<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user->id);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user->id);
    }
}
```

### 4. Conversation Model - التعديلات

```php
// إزالة booking_id من fillable
protected $fillable = [];

// حذف booking() relationship
// حذف getClientId()
// حذف getConsultantId()
```

## نماذج البيانات

### تعديلات قاعدة البيانات

#### Migration: حذف booking_id من conversations

```php
Schema::table('conversations', function (Blueprint $table) {
    $table->dropForeign(['booking_id']);
    $table->dropColumn('booking_id');
});
```

#### Migration: حذف context من messages

```php
Schema::table('messages', function (Blueprint $table) {
    $table->dropIndex(['conversation_id', 'sender_id', 'context']);
    $table->dropColumn('context');
});
```

### تعديلات DTOs

#### ConversationDTO

```php
class ConversationDTO extends BaseDTO
{
    public int $id;
    // حذف booking_id
    public array $participants;
    public ?MessageDTO $last_message;
    public int $unread_count;
    public string $created_at;
    public string $updated_at;
}
```

#### MessageDTO

```php
class MessageDTO extends BaseDTO
{
    public int $id;
    public int $conversation_id;
    public int $sender_id;
    public string $sender_name;
    public ?string $body;
    public string $type;
    // حذف context
    public array $attachments;
    public string $created_at;
}
```

## خصائص الصحة (Correctness Properties)

### Property 1: إنشاء محادثة بين مستخدمين
*For any* زوج من المستخدمين المختلفين، يجب أن ينجح إنشاء محادثة بينهما.

**Validates: Requirements 1.1**

### Property 2: تكرار إنشاء المحادثة يُرجع نفس المحادثة (Idempotence)
*For any* زوج من المستخدمين لديهما محادثة موجودة، استدعاء `getOrCreateConversationByUser` عدة مرات يجب أن يُرجع نفس المحادثة (نفس الـ ID) في كل مرة.

**Validates: Requirements 1.2**

### Property 3: إرسال رسائل بدون حدود
*For any* محادثة وأي مشارك فيها، يجب أن ينجح إرسال أي عدد من الرسائل بدون رفض.

**Validates: Requirements 2.1, 2.2**

### Property 4: التحقق من المشاركة فقط
*For any* محادثة ومستخدم، يجب أن ينجح إرسال الرسالة إذا وفقط إذا كان المستخدم مشاركاً في المحادثة.

**Validates: Requirements 3.1**

## معالجة الأخطاء

### أخطاء إنشاء المحادثة
| الحالة | رسالة الخطأ | كود HTTP |
|--------|-------------|----------|
| محادثة مع النفس | لا يمكنك بدء محادثة مع نفسك | 403 |
| مستخدم غير موجود | المستخدم غير موجود | 404 |

### أخطاء إرسال الرسائل
| الحالة | رسالة الخطأ | كود HTTP |
|--------|-------------|----------|
| غير مشارك | أنت لست مشاركاً في هذه المحادثة | 403 |
| رسالة فارغة | يجب إدخال نص أو مرفق | 422 |

## استراتيجية الاختبار

### مكتبة اختبار الخصائص
سنستخدم **PHPUnit** مع **Faker** لتوليد البيانات العشوائية.

### اختبارات الوحدة المطلوبة
1. اختبار إنشاء محادثة بين مستخدمين جدد
2. اختبار رفض محادثة المستخدم مع نفسه
3. اختبار إرسال رسالة من مشارك
4. اختبار رفض رسالة من غير مشارك

### اختبارات الخصائص المطلوبة
1. **Property 1**: توليد أزواج مستخدمين عشوائية والتحقق من إنشاء محادثات
2. **Property 2**: توليد محادثات واستدعاء getOrCreate عدة مرات والتحقق من ثبات ID
3. **Property 3**: توليد محادثات وإرسال عدد عشوائي من الرسائل والتحقق من نجاحها جميعاً
4. **Property 4**: توليد مستخدمين (مشاركين وغير مشاركين) والتحقق من صحة التفويز
