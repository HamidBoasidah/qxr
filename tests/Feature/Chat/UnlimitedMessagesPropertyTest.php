<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Sending Messages Without Limits
 * 
 * **Validates: Requirements 2.1, 2.2**
 * 
 * Property 3: For any conversation and any participant, sending any number 
 * of messages should succeed without rejection.
 */
class UnlimitedMessagesPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = app(ChatService::class);
    }

    /**
     * Property Test: Any participant can send any number of messages without rejection
     * 
     * **Validates: Requirements 2.1, 2.2**
     * 
     * This test verifies that:
     * 1. A conversation is created between two users
     * 2. A random number of messages (10-50) can be sent
     * 3. All messages are saved successfully
     * 4. No message limit errors are thrown
     */
    #[Test]
    public function participant_can_send_unlimited_messages_without_rejection(): void
    {
        // Generate random number of test iterations
        $iterations = fake()->numberBetween(3, 8);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two users and a conversation between them
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $conversationId = $conversationDTO->id;

            // Random number of messages to send (10-50)
            $messageCount = fake()->numberBetween(10, 50);
            $sentMessageIds = [];

            // Send all messages from user1
            for ($m = 0; $m < $messageCount; $m++) {
                $messageBody = fake()->sentence(fake()->numberBetween(3, 20));

                // Act: Send message - should not throw any exception
                $messageDTO = $this->chatService->sendMessage(
                    $conversationId,
                    $user1->id,
                    $messageBody,
                    [] // No attachments
                );

                // Assert: Message was created successfully
                $this->assertNotNull($messageDTO, "Message #{$m} should be created");
                $this->assertIsInt($messageDTO->id, 'Message ID should be an integer');
                $this->assertGreaterThan(0, $messageDTO->id, 'Message ID should be positive');
                $this->assertEquals($conversationId, $messageDTO->conversation_id, 'Message should belong to correct conversation');
                $this->assertEquals($user1->id, $messageDTO->sender_id, 'Message should have correct sender');
                $this->assertEquals($messageBody, $messageDTO->body, 'Message body should match');

                $sentMessageIds[] = $messageDTO->id;
            }

            // Verify all messages exist in database
            $savedMessagesCount = Message::where('conversation_id', $conversationId)
                ->where('sender_id', $user1->id)
                ->count();

            $this->assertEquals(
                $messageCount,
                $savedMessagesCount,
                "All {$messageCount} messages should be saved in database, found: {$savedMessagesCount}"
            );

            // Verify all message IDs are unique
            $this->assertCount(
                $messageCount,
                array_unique($sentMessageIds),
                'All message IDs should be unique'
            );
        }
    }

    /**
     * Property Test: Both participants can send unlimited messages
     * 
     * **Validates: Requirements 2.1, 2.2**
     * 
     * This test verifies that both participants in a conversation can send
     * any number of messages without any limits.
     */
    #[Test]
    public function both_participants_can_send_unlimited_messages(): void
    {
        $iterations = fake()->numberBetween(3, 6);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two users and a conversation between them
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $conversationId = $conversationDTO->id;

            // Random number of messages for each user (10-30)
            $user1MessageCount = fake()->numberBetween(10, 30);
            $user2MessageCount = fake()->numberBetween(10, 30);

            $user1MessageIds = [];
            $user2MessageIds = [];

            // Send messages from user1
            for ($m = 0; $m < $user1MessageCount; $m++) {
                $messageDTO = $this->chatService->sendMessage(
                    $conversationId,
                    $user1->id,
                    fake()->sentence(),
                    []
                );

                $this->assertNotNull($messageDTO, "User1 message #{$m} should be created");
                $user1MessageIds[] = $messageDTO->id;
            }

            // Send messages from user2
            for ($m = 0; $m < $user2MessageCount; $m++) {
                $messageDTO = $this->chatService->sendMessage(
                    $conversationId,
                    $user2->id,
                    fake()->sentence(),
                    []
                );

                $this->assertNotNull($messageDTO, "User2 message #{$m} should be created");
                $user2MessageIds[] = $messageDTO->id;
            }

            // Verify user1's messages in database
            $user1SavedCount = Message::where('conversation_id', $conversationId)
                ->where('sender_id', $user1->id)
                ->count();

            $this->assertEquals(
                $user1MessageCount,
                $user1SavedCount,
                "User1 should have {$user1MessageCount} messages, found: {$user1SavedCount}"
            );

            // Verify user2's messages in database
            $user2SavedCount = Message::where('conversation_id', $conversationId)
                ->where('sender_id', $user2->id)
                ->count();

            $this->assertEquals(
                $user2MessageCount,
                $user2SavedCount,
                "User2 should have {$user2MessageCount} messages, found: {$user2SavedCount}"
            );

            // Verify total messages in conversation
            $totalMessages = Message::where('conversation_id', $conversationId)->count();
            $expectedTotal = $user1MessageCount + $user2MessageCount;

            $this->assertEquals(
                $expectedTotal,
                $totalMessages,
                "Conversation should have {$expectedTotal} total messages, found: {$totalMessages}"
            );
        }
    }

    /**
     * Property Test: Interleaved messages from both participants succeed
     * 
     * **Validates: Requirements 2.1, 2.2**
     * 
     * This test simulates a real conversation where messages are sent
     * alternately by both participants.
     */
    #[Test]
    public function interleaved_messages_from_both_participants_succeed(): void
    {
        $iterations = fake()->numberBetween(3, 6);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two users and a conversation between them
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $conversationId = $conversationDTO->id;

            // Random total number of messages (20-50)
            $totalMessages = fake()->numberBetween(20, 50);
            $allMessageIds = [];
            $user1Count = 0;
            $user2Count = 0;

            // Send messages alternating randomly between users
            for ($m = 0; $m < $totalMessages; $m++) {
                // Randomly choose sender
                $senderId = fake()->boolean() ? $user1->id : $user2->id;
                $messageBody = fake()->sentence(fake()->numberBetween(2, 15));

                $messageDTO = $this->chatService->sendMessage(
                    $conversationId,
                    $senderId,
                    $messageBody,
                    []
                );

                $this->assertNotNull($messageDTO, "Interleaved message #{$m} should be created");
                $this->assertEquals($senderId, $messageDTO->sender_id, 'Sender should match');
                $this->assertEquals($messageBody, $messageDTO->body, 'Body should match');

                $allMessageIds[] = $messageDTO->id;

                if ($senderId === $user1->id) {
                    $user1Count++;
                } else {
                    $user2Count++;
                }
            }

            // Verify all messages are unique
            $this->assertCount(
                $totalMessages,
                array_unique($allMessageIds),
                'All message IDs should be unique'
            );

            // Verify total count in database
            $dbTotalCount = Message::where('conversation_id', $conversationId)->count();
            $this->assertEquals(
                $totalMessages,
                $dbTotalCount,
                "Database should have {$totalMessages} messages, found: {$dbTotalCount}"
            );

            // Verify per-user counts
            $dbUser1Count = Message::where('conversation_id', $conversationId)
                ->where('sender_id', $user1->id)
                ->count();
            $dbUser2Count = Message::where('conversation_id', $conversationId)
                ->where('sender_id', $user2->id)
                ->count();

            $this->assertEquals($user1Count, $dbUser1Count, "User1 message count should match");
            $this->assertEquals($user2Count, $dbUser2Count, "User2 message count should match");
        }
    }

    /**
     * Property Test: Messages with various content lengths succeed
     * 
     * **Validates: Requirements 2.1, 2.2**
     * 
     * This test verifies that messages of various lengths can be sent
     * without any restrictions.
     */
    #[Test]
    public function messages_with_various_content_lengths_succeed(): void
    {
        $iterations = fake()->numberBetween(3, 6);

        for ($i = 0; $i < $iterations; $i++) {
            // Create two users and a conversation between them
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $conversationId = $conversationDTO->id;

            // Test various message lengths
            $messageLengths = [
                1,    // Very short
                5,    // Short
                20,   // Medium
                50,   // Long
                100,  // Very long
                fake()->numberBetween(1, 200), // Random length
            ];

            foreach ($messageLengths as $wordCount) {
                $messageBody = fake()->sentence($wordCount);

                $messageDTO = $this->chatService->sendMessage(
                    $conversationId,
                    $user1->id,
                    $messageBody,
                    []
                );

                $this->assertNotNull($messageDTO, "Message with {$wordCount} words should be created");
                $this->assertEquals($messageBody, $messageDTO->body, 'Message body should match');
            }

            // Verify all messages were saved
            $savedCount = Message::where('conversation_id', $conversationId)->count();
            $this->assertEquals(
                count($messageLengths),
                $savedCount,
                "All " . count($messageLengths) . " messages should be saved"
            );
        }
    }

    /**
     * Property Test: No message limit errors are thrown for high volume
     * 
     * **Validates: Requirements 2.1, 2.2**
     * 
     * This test specifically verifies that the old message limit restrictions
     * have been removed by sending a large number of messages.
     */
    #[Test]
    public function no_message_limit_errors_for_high_volume(): void
    {
        // Create two users and a conversation between them
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversationDTO = $this->chatService->getOrCreateConversationByUser(
            $user1->id,
            $user2->id
        );

        $conversationId = $conversationDTO->id;

        // Send a high number of messages (more than any previous limit)
        // The old system had a limit of 2 messages for clients outside session
        $highMessageCount = fake()->numberBetween(50, 100);
        $successCount = 0;

        for ($m = 0; $m < $highMessageCount; $m++) {
            try {
                $messageDTO = $this->chatService->sendMessage(
                    $conversationId,
                    $user1->id,
                    fake()->sentence(),
                    []
                );

                if ($messageDTO !== null && $messageDTO->id > 0) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                // If any exception is thrown related to message limits, fail the test
                $this->fail(
                    "Message #{$m} failed with exception: " . $e->getMessage() . 
                    ". No message limit errors should occur."
                );
            }
        }

        // All messages should have succeeded
        $this->assertEquals(
            $highMessageCount,
            $successCount,
            "All {$highMessageCount} messages should succeed without limit errors"
        );

        // Verify in database
        $dbCount = Message::where('conversation_id', $conversationId)->count();
        $this->assertEquals(
            $highMessageCount,
            $dbCount,
            "Database should contain all {$highMessageCount} messages"
        );
    }
}
