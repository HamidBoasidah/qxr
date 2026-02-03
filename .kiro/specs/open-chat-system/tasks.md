# خطة التنفيذ: نظام المحادثات المفتوح

## نظرة عامة

إزالة كل ما يتعلق بالحجوزات (booking) من نظام المحادثات وتحويله لنظام دردشة مفتوح بين المستخدمين.

## المهام

- [x] 1. تعديل قاعدة البيانات
  - [x] 1.1 إنشاء migration لإزالة عمود booking_id من جدول conversations
    - حذف العمود والـ foreign key constraint
    - _Requirements: 1.3_
  - [x] 1.2 إنشاء migration لإزالة عمود context من جدول messages
    - حذف العمود والـ index المرتبط به
    - _Requirements: 2.4_

- [x] 2. تعديل Model Layer
  - [x] 2.1 إزالة علاقة booking من Conversation Model
    - حذف دالة booking()
    - حذف getClientId() و getConsultantId()
    - إزالة booking_id من fillable
    - _Requirements: 1.3_

- [x] 3. تعديل Repository Layer
  - [x] 3.1 تعديل ConversationRepository
    - حذف دالة findByBooking()
    - تعديل createWithParticipants() لإزالة bookingId
    - إضافة دالة findByParticipants() للبحث عن محادثة بين مستخدمين
    - إضافة دالة createWithParticipantsOnly() لإنشاء محادثة بدون حجز
    - إزالة booking من defaultWith
    - تعديل getUserConversations() لإزالة booking relations
    - تعديل getConversationsWithUnreadCounts() لإزالة booking_id
    - _Requirements: 1.1, 1.2_

- [x] 4. تعديل Service Layer
  - [x] 4.1 إعادة كتابة ChatService
    - حذف دالة getOrCreateConversation() (المرتبطة بالحجز)
    - إضافة دالة getOrCreateConversationByUser() للمحادثة بين مستخدمين
    - تبسيط sendMessage() - إزالة كل فحوصات الحجز والجلسة والحدود
    - حذف isInSession()
    - حذف countClientOutOfSessionMessages()
    - حذف canSendMessage()
    - إزالة context من إنشاء الرسائل
    - _Requirements: 1.1, 2.1, 2.2, 3.1_

- [x] 5. تعديل Policy Layer
  - [x] 5.1 تبسيط ConversationPolicy
    - إزالة use Booking
    - تبسيط sendMessage() للتحقق من المشاركة فقط
    - _Requirements: 3.2, 3.3_

- [x] 6. تعديل Controller Layer
  - [x] 6.1 تعديل ConversationController
    - إضافة دالة store() لإنشاء محادثة مع user_id
    - _Requirements: 1.1_
  - [x] 6.2 إنشاء CreateConversationRequest
    - التحقق من وجود user_id وأنه مستخدم صالح
    - _Requirements: 1.1_

- [x] 7. تعديل DTOs
  - [x] 7.1 تعديل ConversationDTO لإزالة booking_id
    - _Requirements: 1.3_
  - [x] 7.2 تعديل MessageDTO لإزالة context
    - _Requirements: 2.4_

- [x] 8. تعديل Resources
  - [x] 8.1 تعديل ConversationResource لإزالة booking
    - _Requirements: 1.3_
  - [x] 8.2 تعديل ConversationListResource لإزالة booking
    - _Requirements: 1.3_
  - [x] 8.3 تعديل MessageResource لإزالة context
    - _Requirements: 2.4_

- [x] 9. تعديل Factories
  - [x] 9.1 تعديل ConversationFactory لإزالة booking_id
    - _Requirements: 1.3_
  - [x] 9.2 تعديل MessageFactory لإزالة context
    - _Requirements: 2.4_

- [x] 10. نقطة تفتيش - التحقق من عمل النظام
  - تشغيل الاختبارات الموجودة والتأكد من عدم كسرها
  - اسأل المستخدم إذا كانت هناك أسئلة

- [x] 11. كتابة الاختبارات
  - [x] 11.1 كتابة اختبار خاصية: إنشاء محادثة بين مستخدمين
    - **Property 1: إنشاء محادثة بين مستخدمين**
    - **Validates: Requirements 1.1**
  - [x] 11.2 كتابة اختبار خاصية: تكرار إنشاء المحادثة (Idempotence)
    - **Property 2: تكرار إنشاء المحادثة يُرجع نفس المحادثة**
    - **Validates: Requirements 1.2**
  - [x] 11.3 كتابة اختبار خاصية: إرسال رسائل بدون حدود
    - **Property 3: إرسال رسائل بدون حدود**
    - **Validates: Requirements 2.1, 2.2**
  - [x] 11.4 كتابة اختبار خاصية: التحقق من المشاركة فقط
    - **Property 4: التحقق من المشاركة فقط**
    - **Validates: Requirements 3.3**
  - [x] 11.5 كتابة اختبارات وحدة للحالات الحدية
    - اختبار رفض محادثة المستخدم مع نفسه
    - اختبار رفض رسالة من غير مشارك
    - _Requirements: 1.1, 3.3_

- [x] 12. نقطة تفتيش نهائية
  - تشغيل جميع الاختبارات والتأكد من نجاحها
  - اسأل المستخدم إذا كانت هناك أسئلة

## ملاحظات

- الهدف هو إزالة كاملة لكل ما يتعلق بـ booking و context و session
- النظام الجديد يعتمد فقط على المشاركين (participants)
- لا توجد حدود على عدد الرسائل
