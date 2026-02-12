# Implementation Plan: Company Chat Integration

## Overview

This implementation plan integrates the existing chat system into the Company Dashboard by creating new controllers that reuse existing services, adding routes, creating Inertia page components, and connecting the frontend Vue components to the backend.

## Tasks

- [x] 1. Create Company Chat Controllers
  - [x] 1.1 Create Company\ConversationController
    - Create `app/Http/Controllers/Company/ConversationController.php`
    - Inject ChatService and ReadStateService via constructor
    - Implement `index()` method to display conversation list (Inertia)
    - Implement `show()` method to display single conversation with messages (Inertia)
    - Implement `store()` method to create new conversation (JSON)
    - Add authorization checks using ConversationPolicy
    - _Requirements: 2.1, 2.3, 2.5, 2.7, 2.8, 3.1, 4.1, 8.1_
  
  - [ ]* 1.2 Write property test for conversation list retrieval
    - **Property 1: User Conversations Retrieval**
    - **Validates: Requirements 3.1**
  
  - [ ]* 1.3 Write property test for non-participant authorization
    - **Property 7: Non-Participant View Authorization**
    - **Validates: Requirements 4.1, 4.2**
  
  - [x] 1.4 Create Company\MessageController
    - Create `app/Http/Controllers/Company/MessageController.php`
    - Inject ChatService, AttachmentService, and ReadStateService via constructor
    - Implement `store()` method to send message (JSON)
    - Implement `markAsRead()` method to update read state (JSON, optional)
    - Implement `upload()` method to handle file uploads (JSON, optional)
    - Add authorization checks using ConversationPolicy
    - _Requirements: 2.2, 2.4, 2.5, 2.6, 2.8, 4.3, 6.1, 6.2, 6.3_
  
  - [ ]* 1.5 Write property test for message sender identity
    - **Property 15: Message Sender Identity**
    - **Validates: Requirements 6.3**
  
  - [ ]* 1.6 Write property test for non-participant send authorization
    - **Property 8: Non-Participant Send Authorization**
    - **Validates: Requirements 4.3, 4.4**

- [x] 2. Create Request Validation Classes
  - [x] 2.1 Create CreateConversationRequest
    - Create `app/Http/Requests/Company/CreateConversationRequest.php`
    - Add validation rules: user_id required, exists, different from auth user
    - Add Arabic error messages
    - _Requirements: 8.1, 8.5, 12.1_
  
  - [x] 2.2 Create SendMessageRequest
    - Create `app/Http/Requests/Company/SendMessageRequest.php`
    - Add validation rules: body or attachments required, max sizes
    - Add Arabic error messages
    - _Requirements: 6.1, 7.1, 12.1_
  
  - [ ]* 2.3 Write property test for empty message rejection
    - **Property 14: Empty Message Rejection**
    - **Validates: Requirements 6.1, 6.6**
  
  - [x] 2.4 Create UploadAttachmentRequest (optional)
    - Create `app/Http/Requests/Company/UploadAttachmentRequest.php`
    - Add validation rules: files array, file types, max sizes
    - Add Arabic error messages
    - _Requirements: 7.1, 12.1_
  
  - [ ]* 2.5 Write property test for invalid file rejection
    - **Property 16: Invalid File Rejection**
    - **Validates: Requirements 7.1**

- [x] 3. Add Company Chat Routes
  - [x] 3.1 Define routes in routes/company.php
    - Add GET /company/chat/conversations → ConversationController@index
    - Add GET /company/chat/conversations/{conversation} → ConversationController@show
    - Add POST /company/chat/conversations → ConversationController@store
    - Add POST /company/chat/conversations/{conversation}/messages → MessageController@store
    - Add POST /company/chat/conversations/{conversation}/read → MessageController@markAsRead (optional)
    - Add POST /company/chat/messages/upload → MessageController@upload (optional)
    - Apply auth:web and company middleware to all routes
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8_
  
  - [ ]* 3.2 Write unit test for unauthenticated access rejection
    - Test that unauthenticated requests return 401/403
    - _Requirements: 1.7_

- [x] 4. Checkpoint - Ensure backend routes and controllers work
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Create Inertia Page Components
  - [x] 5.1 Create Chat Index page component
    - Create `resources/js/Pages/Company/Chat/Index.vue`
    - Define props interface: conversations (data + pagination), auth
    - Import and use ChatSidebar and ChatList components
    - Add search functionality
    - Add pagination controls
    - Handle empty state
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 9.1, 9.2_
  
  - [ ]* 5.2 Write property test for conversation participant information
    - **Property 2: Conversation Participant Information**
    - **Validates: Requirements 3.2**
  
  - [ ]* 5.3 Write property test for conversation last message
    - **Property 3: Conversation Last Message**
    - **Validates: Requirements 3.3**
  
  - [ ]* 5.4 Write property test for conversation unread count
    - **Property 4: Conversation Unread Count**
    - **Validates: Requirements 3.4**
  
  - [ ]* 5.5 Write property test for conversation ordering
    - **Property 5: Conversation Ordering**
    - **Validates: Requirements 3.6**
  
  - [ ]* 5.6 Write property test for search filtering
    - **Property 6: Conversation Search Filtering**
    - **Validates: Requirements 3.7**
  
  - [x] 5.7 Create Chat Show page component
    - Create `resources/js/Pages/Company/Chat/Show.vue`
    - Define props interface: conversation, messages (data + meta), auth
    - Import and use ChatHeader and ChatBox components
    - Add infinite scroll for loading previous messages
    - Add message send form with validation
    - Add file upload functionality (optional)
    - Handle real-time updates
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 9.3, 9.4_
  
  - [ ]* 5.8 Write property test for message chronological ordering
    - **Property 9: Message Chronological Ordering**
    - **Validates: Requirements 5.1**
  
  - [ ]* 5.9 Write property test for message sender information
    - **Property 10: Message Sender Information**
    - **Validates: Requirements 5.2**
  
  - [ ]* 5.10 Write property test for message body content
    - **Property 11: Message Body Content**
    - **Validates: Requirements 5.4**
  
  - [ ]* 5.11 Write property test for message attachment information
    - **Property 12: Message Attachment Information**
    - **Validates: Requirements 5.5**

- [x] 6. Update Existing Vue Components
  - [x] 6.1 Update ChatSidebar.vue
    - Replace dummy data with props from Inertia
    - Connect search input to backend API
    - Add click handlers to navigate to conversation
    - Display unread count badges
    - _Requirements: 3.4, 9.1, 9.5_
  
  - [x] 6.2 Update ChatList.vue
    - Replace dummy data with props from Inertia
    - Pass conversation data to ChatListItem components
    - Handle empty state display
    - _Requirements: 3.8, 9.1_
  
  - [x] 6.3 Update ChatListItem.vue
    - Replace dummy data with props
    - Display participant names, last message, timestamp, unread count
    - Format timestamps in Arabic locale
    - _Requirements: 3.2, 3.3, 3.4, 3.5, 9.8_
  
  - [x] 6.4 Update ChatHeader.vue
    - Replace dummy data with props
    - Display conversation participant names
    - _Requirements: 9.1_
  
  - [x] 6.5 Update ChatBox.vue
    - Replace dummy data with props
    - Add Axios call to send message (POST to MessageController@store)
    - Add file upload handling (optional)
    - Add infinite scroll for loading previous messages
    - Display messages with sender info, timestamp, body, attachments
    - Handle message send errors
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 6.1, 6.5, 7.4, 9.5, 9.6, 12.5_
  
  - [ ]* 6.6 Write property test for read state update on view
    - **Property 13: Read State Update on View**
    - **Validates: Requirements 5.8, 5.9**

- [x] 7. Add Chat Navigation to Company Sidebar
  - [x] 7.1 Update Company layout sidebar
    - Add chat navigation link to sidebar menu
    - Add chat icon
    - Highlight active state when on chat pages
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6_

- [x] 8. Implement Optional Features
  - [x] 8.1 Implement file upload endpoint (optional)
    - Complete MessageController@upload method
    - Store files temporarily
    - Return file IDs for association with message
    - _Requirements: 7.2, 7.3_
  
  - [ ]* 8.2 Write property test for attachment message association
    - **Property 17: Attachment Message Association**
    - **Validates: Requirements 7.3**
  
  - [x] 8.3 Implement mark as read endpoint (optional)
    - Complete MessageController@markAsRead method
    - Update conversation_participants.last_read_message_id
    - Return updated unread count
    - _Requirements: 10.1, 10.2, 10.5_
  
  - [ ]* 8.4 Write property test for unread count accuracy
    - **Property 20: Unread Count Accuracy**
    - **Validates: Requirements 10.6**

- [x] 9. Add Error Handling and Validation
  - [x] 9.1 Implement consistent error handling in controllers
    - Add try-catch blocks for all controller methods
    - Return appropriate HTTP status codes (403, 404, 422, 500)
    - Return error messages in Arabic
    - Log errors with context
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.6_
  
  - [ ]* 9.2 Write property test for Arabic validation messages
    - **Property 21: Arabic Validation Messages**
    - **Validates: Requirements 12.1**
  
  - [ ]* 9.3 Write unit test for 404 error on non-existent conversation
    - Test that requesting non-existent conversation returns 404
    - _Requirements: 12.3_

- [x] 10. Add Property-Based Tests for Core Properties
  - [ ]* 10.1 Write property test for conversation creation idempotence
    - **Property 18: Conversation Creation Idempotence**
    - **Validates: Requirements 8.1, 8.2**
  
  - [ ]* 10.2 Write property test for conversation participant membership
    - **Property 19: Conversation Participant Membership**
    - **Validates: Requirements 8.4**
  
  - [ ]* 10.3 Write property test for message creation atomicity
    - **Property 22: Message Creation Atomicity**
    - **Validates: Requirements 14.2**

- [x] 11. Final Integration and Testing
  - [x] 11.1 Test complete user flow
    - Test viewing conversation list
    - Test opening a conversation
    - Test sending a message
    - Test file upload (if implemented)
    - Test mark as read (if implemented)
    - Test search functionality
    - Test pagination
    - _Requirements: All_
  
  - [ ]* 11.2 Write integration tests for complete flows
    - Test end-to-end conversation creation and messaging
    - Test authorization across different user scenarios
    - _Requirements: All_

- [x] 12. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (minimum 100 iterations each)
- Unit tests validate specific examples and edge cases
- All controllers must reuse existing ChatService, ReadStateService, and AttachmentService
- No modifications to existing services, repositories, or policies
- Follow PSR-12 coding standards and use type hints throughout
- All error messages must be in Arabic
