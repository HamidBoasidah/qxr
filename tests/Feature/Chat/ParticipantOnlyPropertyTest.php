<?php

namespace Tests\Feature\Chat;

use App\Exceptions\ForbiddenException;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Participant-Only Verification
 * 
 * **Validates: Requirements 3.3**
 * 
 * Property 4: For any conversation and user, sending a message should succeed
 * if and only if the user is a participant in the conversation.
 */
class ParticipantOnlyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = app(ChatService::class);
    }

    /**
     * Property Test: Participants CAN send messages to their conversations
     * 
     * **Validates: Requirements 3.3**
     * 
     * This test verifies that any user who is a participant in a conversation
     * can successfully send messages to that conversation.
     */
    #[Test]
    public function participants_can_send_messages_to_their_conversations(): void
    {
        // Generate random number of iterations
        $iterations = fake()->numberBetween(10, 25);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two random users
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Create a conversation between them
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $conversationId = $conversationDTO->id;

            // Randomly choose which participant sends the message
            $sender = fake()->boolean() ? $user1 : $user2;
            $messageBody = fake()->sentence(fake()->numberBetween(3, 15));

            // Act: Participant sends message - should succeed
            $messageDTO = $this->chatService->sendMessage(
                $conversationId,
                $sender->id,
                $messageBody,
                []
            );

            // Assert: Message was created successfully
            $this->assertNotNull($messageDTO, "Participant {$sender->id} should be able to send message");
            $this->assertIsInt($messageDTO->id, 'Message ID should be an integer');
            $this->assertGreaterThan(0, $messageDTO->id, 'Message ID should be positive');
            $this->assertEquals($conversationId, $messageDTO->conversation_id, 'Message should belong to correct conversation');
            $this->assertEquals($sender->id, $messageDTO->sender_id, 'Message should have correct sender');
            $this->assertEquals($messageBody, $messageDTO->body, 'Message body should match');
        }
    }

    /**
     * Property Test: Non-participants CANNOT send messages to conversations
     * 
     * **Validates: Requirements 3.3**
     * 
     * This test verifies that any user who is NOT a participant in a conversation
     * will receive a ForbiddenException when trying to send a message.
     */
    #[Test]
    public function non_participants_cannot_send_messages_to_conversations(): void
    {
        // Generate random number of iterations
        $iterations = fake()->numberBetween(10, 25);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two users for the conversation
            $participant1 = User::factory()->create();
            $participant2 = User::factory()->create();

            // Create a third user who is NOT a participant
            $nonParticipant = User::factory()->create();

            // Create a conversation between participant1 and participant2
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $participant1->id,
                $participant2->id
            );

            $conversationId = $conversationDTO->id;
            $messageBody = fake()->sentence(fake()->numberBetween(3, 15));

            // Act & Assert: Non-participant tries to send message - should throw ForbiddenException
            $this->expectException(ForbiddenException::class);

            $this->chatService->sendMessage(
                $conversationId,
                $nonParticipant->id,
                $messageBody,
                []
            );
        }
    }

    /**
     * Property Test: Multiple non-participants all fail to send messages
     * 
     * **Validates: Requirements 3.3**
     * 
     * This test creates multiple random non-participants and verifies that
     * ALL of them fail to send messages to a conversation they're not part of.
     */
    #[Test]
    public function multiple_non_participants_all_fail_to_send_messages(): void
    {
        // Generate random number of iterations
        $iterations = fake()->numberBetween(5, 10);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two users for the conversation
            $participant1 = User::factory()->create();
            $participant2 = User::factory()->create();

            // Create a conversation between them
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $participant1->id,
                $participant2->id
            );

            $conversationId = $conversationDTO->id;

            // Create random number of non-participants (3-10)
            $nonParticipantCount = fake()->numberBetween(3, 10);
            $failedAttempts = 0;

            for ($j = 0; $j < $nonParticipantCount; $j++) {
                $nonParticipant = User::factory()->create();
                $messageBody = fake()->sentence();

                try {
                    $this->chatService->sendMessage(
                        $conversationId,
                        $nonParticipant->id,
                        $messageBody,
                        []
                    );

                    // If we reach here, the test should fail
                    $this->fail(
                        "Non-participant {$nonParticipant->id} should NOT be able to send message to conversation {$conversationId}"
                    );
                } catch (ForbiddenException $e) {
                    // Expected behavior - non-participant was correctly rejected
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
    }

    /**
     * Property Test: Participant status is the ONLY condition for sending messages
     * 
     * **Validates: Requirements 3.3**
     * 
     * This test verifies the bi-conditional property:
     * - If user IS participant → CAN send message
     * - If user is NOT participant → CANNOT send message
     * 
     * Tests both conditions in the same iteration to ensure the property holds universally.
     */
    #[Test]
    public function participant_status_is_the_only_condition_for_sending_messages(): void
    {
        // Generate random number of iterations
        $iterations = fake()->numberBetween(10, 20);

        for ($i = 0; $i < $iterations; $i++) {
            // Create participants
            $participant1 = User::factory()->create();
            $participant2 = User::factory()->create();

            // Create non-participant
            $nonParticipant = User::factory()->create();

            // Create conversation
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $participant1->id,
                $participant2->id
            );

            $conversationId = $conversationDTO->id;

            // Test 1: Participant1 CAN send message
            $message1 = $this->chatService->sendMessage(
                $conversationId,
                $participant1->id,
                fake()->sentence(),
                []
            );
            $this->assertNotNull($message1, "Participant1 should be able to send message");
            $this->assertGreaterThan(0, $message1->id);

            // Test 2: Participant2 CAN send message
            $message2 = $this->chatService->sendMessage(
                $conversationId,
                $participant2->id,
                fake()->sentence(),
                []
            );
            $this->assertNotNull($message2, "Participant2 should be able to send message");
            $this->assertGreaterThan(0, $message2->id);

            // Test 3: Non-participant CANNOT send message
            $exceptionThrown = false;
            try {
                $this->chatService->sendMessage(
                    $conversationId,
                    $nonParticipant->id,
                    fake()->sentence(),
                    []
                );
            } catch (ForbiddenException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue(
                $exceptionThrown,
                "Non-participant {$nonParticipant->id} should NOT be able to send message"
            );
        }
    }

    /**
     * Property Test: Random users are correctly classified as participants or non-participants
     * 
     * **Validates: Requirements 3.3**
     * 
     * This test generates a pool of random users and verifies that the system
     * correctly allows/denies message sending based on participant status.
     */
    #[Test]
    public function random_users_are_correctly_classified_for_message_sending(): void
    {
        // Generate random number of iterations
        $iterations = fake()->numberBetween(5, 10);

        for ($i = 0; $i < $iterations; $i++) {
            // Create a pool of random users (5-15)
            $userCount = fake()->numberBetween(5, 15);
            $users = [];
            for ($j = 0; $j < $userCount; $j++) {
                $users[] = User::factory()->create();
            }

            // Randomly select 2 users to be participants
            $participantIndices = array_rand($users, 2);
            $participant1 = $users[$participantIndices[0]];
            $participant2 = $users[$participantIndices[1]];

            // Create conversation between the two participants
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $participant1->id,
                $participant2->id
            );

            $conversationId = $conversationDTO->id;

            // Test each user in the pool
            foreach ($users as $index => $user) {
                $isParticipant = ($index === $participantIndices[0] || $index === $participantIndices[1]);
                $messageBody = fake()->sentence();

                if ($isParticipant) {
                    // Participant should succeed
                    $messageDTO = $this->chatService->sendMessage(
                        $conversationId,
                        $user->id,
                        $messageBody,
                        []
                    );

                    $this->assertNotNull(
                        $messageDTO,
                        "User {$user->id} is a participant and should be able to send message"
                    );
                    $this->assertGreaterThan(0, $messageDTO->id);
                } else {
                    // Non-participant should fail
                    $exceptionThrown = false;
                    try {
                        $this->chatService->sendMessage(
                            $conversationId,
                            $user->id,
                            $messageBody,
                            []
                        );
                    } catch (ForbiddenException $e) {
                        $exceptionThrown = true;
                    }

                    $this->assertTrue(
                        $exceptionThrown,
                        "User {$user->id} is NOT a participant and should NOT be able to send message"
                    );
                }
            }
        }
    }
}
