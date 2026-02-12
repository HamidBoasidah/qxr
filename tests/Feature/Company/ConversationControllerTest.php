<?php

namespace Tests\Feature\Company;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can access conversation list page
     */
    public function test_authenticated_user_can_access_conversation_list(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);

        $response = $this->actingAs($user, 'web')
            ->get(route('company.chat.conversations.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Company/Chat/Index')
            ->has('conversations')
        );
    }

    /**
     * Test that unauthenticated user cannot access conversation list
     */
    public function test_unauthenticated_user_cannot_access_conversation_list(): void
    {
        $response = $this->get(route('company.chat.conversations.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test that participant can view conversation
     */
    public function test_participant_can_view_conversation(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        // Create conversation with participants
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$user->id, $otherUser->id]);

        $response = $this->actingAs($user, 'web')
            ->get(route('company.chat.conversations.show', $conversation));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Company/Chat/Show')
            ->has('conversation')
            ->has('messages')
        );
    }

    /**
     * Test that non-participant cannot view conversation
     */
    public function test_non_participant_cannot_view_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();

        // Create conversation without the user
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$otherUser1->id, $otherUser2->id]);

        $response = $this->actingAs($user, 'web')
            ->get(route('company.chat.conversations.show', $conversation));

        $response->assertForbidden();
    }

    /**
     * Test that user can create new conversation
     */
    public function test_user_can_create_new_conversation(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.store'), [
                'user_id' => $otherUser->id,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'participants',
            ],
        ]);

        // Verify conversation was created in database
        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('conversation_participants', [
            'user_id' => $otherUser->id,
        ]);
    }

    /**
     * Test that creating conversation with same user returns existing conversation
     */
    public function test_creating_conversation_with_same_user_returns_existing(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        // Create first conversation
        $response1 = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.store'), [
                'user_id' => $otherUser->id,
            ]);

        $response1->assertOk();
        $conversationId1 = $response1->json('data.id');

        // Try to create second conversation with same user
        $response2 = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.store'), [
                'user_id' => $otherUser->id,
            ]);

        $response2->assertOk();
        $conversationId2 = $response2->json('data.id');

        // Should return the same conversation
        $this->assertEquals($conversationId1, $conversationId2);
    }

    /**
     * Test that user cannot create conversation with themselves
     */
    public function test_user_cannot_create_conversation_with_themselves(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.store'), [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test that user cannot create conversation with non-existent user
     */
    public function test_user_cannot_create_conversation_with_nonexistent_user(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.store'), [
                'user_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }
}
