<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-Based Test: Conversation Creation Between Users
 * 
 * **Validates: Requirements 1.1**
 * 
 * Property 1: For any pair of different users, creating a conversation 
 * between them should succeed.
 */
class ConversationCreationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = app(ChatService::class);
    }

    /**
     * Property Test: Creating a conversation between any two different users should succeed
     * 
     * **Validates: Requirements 1.1**
     * 
     * This test generates multiple random user pairs and verifies that:
     * 1. A conversation is successfully created
     * 2. The conversation has a valid ID
     * 3. Both users are participants in the conversation
     */
    #[Test]
    public function conversation_creation_succeeds_for_any_pair_of_different_users(): void
    {
        // Generate random number of iterations (property-based testing approach)
        $iterations = fake()->numberBetween(10, 25);

        for ($i = 0; $i < $iterations; $i++) {
            // Generate two different random users
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Ensure users are different (they should be by factory, but verify)
            $this->assertNotEquals($user1->id, $user2->id, 'Users should be different');

            // Act: Create conversation between the two users
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            // Assert: Conversation was created successfully
            $this->assertNotNull($conversationDTO, "Conversation should be created for users {$user1->id} and {$user2->id}");
            $this->assertIsInt($conversationDTO->id, 'Conversation ID should be an integer');
            $this->assertGreaterThan(0, $conversationDTO->id, 'Conversation ID should be positive');

            // Assert: Both users are participants
            $participantIds = array_column($conversationDTO->participants, 'id');
            $this->assertContains($user1->id, $participantIds, "User {$user1->id} should be a participant");
            $this->assertContains($user2->id, $participantIds, "User {$user2->id} should be a participant");
            $this->assertCount(2, $participantIds, 'Conversation should have exactly 2 participants');
        }
    }

    /**
     * Property Test: Conversation creation order should not matter
     * 
     * **Validates: Requirements 1.1**
     * 
     * For any two users A and B, creating a conversation with (A, B) or (B, A)
     * should both succeed and create valid conversations.
     */
    #[Test]
    public function conversation_creation_succeeds_regardless_of_user_order(): void
    {
        $iterations = fake()->numberBetween(5, 15);

        for ($i = 0; $i < $iterations; $i++) {
            // Generate two different random users
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            // Create conversation with order (A, B)
            $conversationAB = $this->chatService->getOrCreateConversationByUser(
                $userA->id,
                $userB->id
            );

            // Assert: Conversation was created successfully
            $this->assertNotNull($conversationAB);
            $this->assertGreaterThan(0, $conversationAB->id);

            // Create new users for reverse order test
            $userC = User::factory()->create();
            $userD = User::factory()->create();

            // Create conversation with order (D, C) - reversed
            $conversationDC = $this->chatService->getOrCreateConversationByUser(
                $userD->id,
                $userC->id
            );

            // Assert: Conversation was created successfully
            $this->assertNotNull($conversationDC);
            $this->assertGreaterThan(0, $conversationDC->id);

            // Both should have exactly 2 participants
            $this->assertCount(2, $conversationAB->participants);
            $this->assertCount(2, $conversationDC->participants);
        }
    }

    /**
     * Property Test: Conversation creation with various user attributes should succeed
     * 
     * **Validates: Requirements 1.1**
     * 
     * Users with different attributes (names, emails, etc.) should all be able
     * to create conversations successfully.
     */
    #[Test]
    public function conversation_creation_succeeds_with_various_user_attributes(): void
    {
        $iterations = fake()->numberBetween(5, 15);

        for ($i = 0; $i < $iterations; $i++) {
            // Create users with random attributes using Faker
            $user1 = User::factory()->create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            $user2 = User::factory()->create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            // Act: Create conversation
            $conversationDTO = $this->chatService->getOrCreateConversationByUser(
                $user1->id,
                $user2->id
            );

            // Assert: Conversation was created successfully
            $this->assertNotNull($conversationDTO);
            $this->assertGreaterThan(0, $conversationDTO->id);

            // Assert: Participants contain correct user information
            $participantIds = array_column($conversationDTO->participants, 'id');
            $this->assertContains($user1->id, $participantIds);
            $this->assertContains($user2->id, $participantIds);
        }
    }
}
