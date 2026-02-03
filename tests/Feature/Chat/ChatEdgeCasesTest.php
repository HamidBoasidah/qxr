<?php

namespace Tests\Feature\Chat;

use App\Exceptions\ForbiddenException;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Tests for Chat Edge Cases
 * 
 * Tests edge cases for the chat system:
 * 1. Rejecting self-conversation (Requirements 1.1)
 * 2. Rejecting message from non-participant (Requirements 3.3)
 */
class ChatEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = app(ChatService::class);
    }

    /**
     * Test: User cannot start a conversation with themselves
     * 
     * **Validates: Requirements 1.1**
     * 
     * When a user tries to start a conversation with themselves,
     * the system should throw a ForbiddenException.
     */
    #[Test]
    public function user_cannot_start_conversation_with_themselves(): void
    {
        // Arrange: Create a user
        $user = User::factory()->create();

        // Assert: Expect ForbiddenException
        $this->expectException(ForbiddenException::class);

        // Act: Try to create a conversation with self
        $this->chatService->getOrCreateConversationByUser(
            $user->id,
            $user->id
        );
    }

    /**
     * Test: Self-conversation rejection with correct error message
     * 
     * **Validates: Requirements 1.1**
     * 
     * Verifies that the ForbiddenException contains the correct Arabic error message.
     */
    #[Test]
    public function self_conversation_rejection_has_correct_error_message(): void
    {
        // Arrange: Create a user
        $user = User::factory()->create();

        try {
            // Act: Try to create a conversation with self
            $this->chatService->getOrCreateConversationByUser(
                $user->id,
                $user->id
            );

            // If we reach here, the test should fail
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException $e) {
            // Assert: Verify the error message
            $this->assertEquals(
                'لا يمكنك بدء محادثة مع نفسك',
                $e->getMessage(),
                'Error message should indicate self-conversation is not allowed'
            );
        }
    }

    /**
     * Test: Non-participant cannot send a message to a conversation
     * 
     * **Validates: Requirements 3.3**
     * 
     * When a user who is not a participant in a conversation tries to send
     * a message, the system should throw a ForbiddenException.
     */
    #[Test]
    public function non_participant_cannot_send_message(): void
    {
        // Arrange: Create two users for the conversation
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        // Create a third user who is NOT a participant
        $nonParticipant = User::factory()->create();

        // Create a conversation between participant1 and participant2
        $conversationDTO = $this->chatService->getOrCreateConversationByUser(
            $participant1->id,
            $participant2->id
        );

        // Assert: Expect ForbiddenException
        $this->expectException(ForbiddenException::class);

        // Act: Non-participant tries to send a message
        $this->chatService->sendMessage(
            $conversationDTO->id,
            $nonParticipant->id,
            'This message should not be sent',
            []
        );
    }

    /**
     * Test: Non-participant message rejection has correct error message
     * 
     * **Validates: Requirements 3.3**
     * 
     * Verifies that the ForbiddenException contains the correct Arabic error message
     * when a non-participant tries to send a message.
     */
    #[Test]
    public function non_participant_message_rejection_has_correct_error_message(): void
    {
        // Arrange: Create participants and non-participant
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();
        $nonParticipant = User::factory()->create();

        // Create a conversation
        $conversationDTO = $this->chatService->getOrCreateConversationByUser(
            $participant1->id,
            $participant2->id
        );

        try {
            // Act: Non-participant tries to send a message
            $this->chatService->sendMessage(
                $conversationDTO->id,
                $nonParticipant->id,
                'This message should not be sent',
                []
            );

            // If we reach here, the test should fail
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException $e) {
            // Assert: Verify the error message
            $this->assertEquals(
                'أنت لست مشاركاً في هذه المحادثة',
                $e->getMessage(),
                'Error message should indicate user is not a participant'
            );
        }
    }

    /**
     * Test: Multiple different users all fail self-conversation
     * 
     * **Validates: Requirements 1.1**
     * 
     * Verifies that the self-conversation restriction applies to all users,
     * not just specific ones.
     */
    #[Test]
    public function multiple_users_all_fail_self_conversation(): void
    {
        // Create multiple users and verify each fails self-conversation
        $userCount = 5;
        $failedAttempts = 0;

        for ($i = 0; $i < $userCount; $i++) {
            $user = User::factory()->create();

            try {
                $this->chatService->getOrCreateConversationByUser(
                    $user->id,
                    $user->id
                );

                // If we reach here, the test should fail
                $this->fail("User {$user->id} should not be able to start conversation with themselves");
            } catch (ForbiddenException $e) {
                // Expected behavior
                $failedAttempts++;
            }
        }

        // Assert: All users were rejected
        $this->assertEquals(
            $userCount,
            $failedAttempts,
            "All {$userCount} users should be rejected when trying self-conversation"
        );
    }

    /**
     * Test: Multiple non-participants all fail to send messages
     * 
     * **Validates: Requirements 3.3**
     * 
     * Verifies that multiple different non-participants are all correctly
     * rejected when trying to send messages to a conversation.
     */
    #[Test]
    public function multiple_non_participants_all_fail_to_send_messages(): void
    {
        // Arrange: Create a conversation between two participants
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $conversationDTO = $this->chatService->getOrCreateConversationByUser(
            $participant1->id,
            $participant2->id
        );

        // Create multiple non-participants
        $nonParticipantCount = 5;
        $failedAttempts = 0;

        for ($i = 0; $i < $nonParticipantCount; $i++) {
            $nonParticipant = User::factory()->create();

            try {
                $this->chatService->sendMessage(
                    $conversationDTO->id,
                    $nonParticipant->id,
                    'This message should not be sent',
                    []
                );

                // If we reach here, the test should fail
                $this->fail("Non-participant {$nonParticipant->id} should not be able to send message");
            } catch (ForbiddenException $e) {
                // Expected behavior
                $failedAttempts++;
            }
        }

        // Assert: All non-participants were rejected
        $this->assertEquals(
            $nonParticipantCount,
            $failedAttempts,
            "All {$nonParticipantCount} non-participants should be rejected"
        );
    }

    /**
     * Test: Participant can send message while non-participant cannot
     * 
     * **Validates: Requirements 3.3**
     * 
     * Verifies the contrast: participants succeed, non-participants fail.
     */
    #[Test]
    public function participant_succeeds_while_non_participant_fails(): void
    {
        // Arrange: Create participants and non-participant
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();
        $nonParticipant = User::factory()->create();

        // Create a conversation
        $conversationDTO = $this->chatService->getOrCreateConversationByUser(
            $participant1->id,
            $participant2->id
        );

        // Act & Assert: Participant1 CAN send message
        $message = $this->chatService->sendMessage(
            $conversationDTO->id,
            $participant1->id,
            'Message from participant',
            []
        );

        $this->assertNotNull($message, 'Participant should be able to send message');
        $this->assertGreaterThan(0, $message->id, 'Message should have valid ID');

        // Act & Assert: Non-participant CANNOT send message
        $this->expectException(ForbiddenException::class);

        $this->chatService->sendMessage(
            $conversationDTO->id,
            $nonParticipant->id,
            'Message from non-participant',
            []
        );
    }
}
