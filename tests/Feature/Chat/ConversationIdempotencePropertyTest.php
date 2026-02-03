<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Conversation Creation Idempotence
 * 
 * **Validates: Requirements 1.2**
 * 
 * Property 2: For any pair of users with an existing conversation, 
 * calling getOrCreateConversationByUser multiple times should return 
 * the same conversation (same ID) every time.
 */
class ConversationIdempotencePropertyTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = app(ChatService::class);
    }

    /**
     * Property Test: Calling getOrCreateConversationByUser multiple times returns same conversation
     * 
     * **Validates: Requirements 1.2**
     * 
     * This test verifies that for any pair of users:
     * 1. First call creates a conversation
     * 2. Subsequent calls return the same conversation ID
     * 3. The number of calls doesn't affect the result
     */
    #[Test]
    public function get_or_create_conversation_is_idempotent(): void
    {
        // Generate random number of user pairs to test
        $userPairs = fake()->numberBetween(5, 15);

        for ($pair = 0; $pair < $userPairs; $pair++) {
            // Generate two different random users
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // First call - creates the conversation
            $firstConversation = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $this->assertNotNull($firstConversation, 'First conversation should be created');
            $originalId = $firstConversation->id;

            // Random number of subsequent calls (2-10 times)
            $additionalCalls = fake()->numberBetween(2, 10);

            for ($call = 0; $call < $additionalCalls; $call++) {
                $subsequentConversation = $this->chatService->getOrCreateConversationByUser(
                    $user1->id,
                    $user2->id
                );

                // Assert: Same conversation ID is returned every time
                $this->assertEquals(
                    $originalId,
                    $subsequentConversation->id,
                    "Call #{$call} should return same conversation ID. Expected: {$originalId}, Got: {$subsequentConversation->id}"
                );

                // Assert: Participants remain the same
                $participantIds = array_column($subsequentConversation->participants, 'id');
                $this->assertContains($user1->id, $participantIds);
                $this->assertContains($user2->id, $participantIds);
                $this->assertCount(2, $participantIds, 'Should still have exactly 2 participants');
            }
        }
    }

    /**
     * Property Test: Idempotence works with reversed user order
     * 
     * **Validates: Requirements 1.2**
     * 
     * For any two users A and B with an existing conversation:
     * - getOrCreateConversationByUser(A, B) returns the same conversation
     * - getOrCreateConversationByUser(B, A) also returns the same conversation
     */
    #[Test]
    public function get_or_create_conversation_is_idempotent_with_reversed_order(): void
    {
        $iterations = fake()->numberBetween(5, 15);

        for ($i = 0; $i < $iterations; $i++) {
            // Generate two different random users
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            // First call with order (A, B)
            $conversationAB = $this->chatService->getOrCreateConversationByUser(
                $userA->id,
                $userB->id
            );

            $originalId = $conversationAB->id;

            // Random number of calls alternating between (A,B) and (B,A)
            $totalCalls = fake()->numberBetween(4, 12);

            for ($call = 0; $call < $totalCalls; $call++) {
                // Alternate between (A,B) and (B,A) order
                if ($call % 2 === 0) {
                    $conversation = $this->chatService->getOrCreateConversationByUser(
                        $userA->id,
                        $userB->id
                    );
                } else {
                    $conversation = $this->chatService->getOrCreateConversationByUser(
                        $userB->id,
                        $userA->id
                    );
                }

                // Assert: Same conversation ID regardless of order
                $this->assertEquals(
                    $originalId,
                    $conversation->id,
                    "Call #{$call} with " . ($call % 2 === 0 ? "(A,B)" : "(B,A)") . 
                    " order should return same conversation ID. Expected: {$originalId}, Got: {$conversation->id}"
                );
            }
        }
    }

    /**
     * Property Test: Idempotence preserves conversation data integrity
     * 
     * **Validates: Requirements 1.2**
     * 
     * Multiple calls should not corrupt or change the conversation data.
     */
    #[Test]
    public function idempotent_calls_preserve_conversation_data(): void
    {
        $iterations = fake()->numberBetween(5, 10);

        for ($i = 0; $i < $iterations; $i++) {
            // Generate two different random users
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // First call - capture all data
            $firstConversation = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            $originalData = [
                'id' => $firstConversation->id,
                'participant_count' => count($firstConversation->participants),
                'participant_ids' => array_column($firstConversation->participants, 'id'),
            ];

            sort($originalData['participant_ids']);

            // Multiple subsequent calls
            $calls = fake()->numberBetween(3, 8);

            for ($call = 0; $call < $calls; $call++) {
                // Randomly choose order
                $useReversedOrder = fake()->boolean();

                if ($useReversedOrder) {
                    $conversation = $this->chatService->getOrCreateConversationByUser(
                        $user2->id,
                        $user1->id
                    );
                } else {
                    $conversation = $this->chatService->getOrCreateConversationByUser(
                        $user1->id,
                        $user2->id
                    );
                }

                // Verify all data remains consistent
                $this->assertEquals($originalData['id'], $conversation->id, 'ID should remain the same');
                $this->assertEquals(
                    $originalData['participant_count'],
                    count($conversation->participants),
                    'Participant count should remain the same'
                );

                $currentParticipantIds = array_column($conversation->participants, 'id');
                sort($currentParticipantIds);

                $this->assertEquals(
                    $originalData['participant_ids'],
                    $currentParticipantIds,
                    'Participant IDs should remain the same'
                );
            }
        }
    }

    /**
     * Property Test: Database has only one conversation per user pair
     * 
     * **Validates: Requirements 1.2**
     * 
     * After multiple getOrCreateConversationByUser calls, there should be
     * exactly one conversation in the database for each user pair.
     */
    #[Test]
    public function only_one_conversation_exists_per_user_pair_after_multiple_calls(): void
    {
        $iterations = fake()->numberBetween(3, 8);

        for ($i = 0; $i < $iterations; $i++) {
            // Generate two different random users
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Make multiple calls with random order
            $calls = fake()->numberBetween(5, 15);

            for ($call = 0; $call < $calls; $call++) {
                $useReversedOrder = fake()->boolean();

                if ($useReversedOrder) {
                    $this->chatService->getOrCreateConversationByUser($user2->id, $user1->id);
                } else {
                    $this->chatService->getOrCreateConversationByUser($user1->id, $user2->id);
                }
            }

            // Query database directly to count conversations between these users
            $conversationCount = \App\Models\Conversation::query()
                ->whereHas('participants', function ($query) use ($user1) {
                    $query->where('user_id', $user1->id);
                })
                ->whereHas('participants', function ($query) use ($user2) {
                    $query->where('user_id', $user2->id);
                })
                ->count();

            $this->assertEquals(
                1,
                $conversationCount,
                "After {$calls} calls, there should be exactly 1 conversation between users {$user1->id} and {$user2->id}, found: {$conversationCount}"
            );
        }
    }
}
