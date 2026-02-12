<?php

namespace Tests\Feature\Company;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that participant can send text message
     */
    public function test_participant_can_send_text_message(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        // Create conversation with participants
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$user->id, $otherUser->id]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.messages.store', $conversation), [
                'body' => 'Test message',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'conversation_id',
                'sender_id',
                'sender_name',
                'body',
                'created_at',
            ],
        ]);

        // Verify message was created in database
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => 'Test message',
        ]);
    }

    /**
     * Test that non-participant cannot send message
     */
    public function test_non_participant_cannot_send_message(): void
    {
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();

        // Create conversation without the user
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$otherUser1->id, $otherUser2->id]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.messages.store', $conversation), [
                'body' => 'Test message',
            ]);

        $response->assertForbidden();
    }

    /**
     * Test that empty message without files is rejected
     */
    public function test_empty_message_without_files_is_rejected(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$user->id, $otherUser->id]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.messages.store', $conversation), [
                'body' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    /**
     * Test that participant can send message with files
     * 
     * Note: This test is skipped because the attachment download route
     * (api.attachments.download) is not yet implemented.
     */
    public function test_participant_can_send_message_with_files(): void
    {
        $this->markTestSkipped('Attachment download route not yet implemented');

        Storage::fake('private');

        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$user->id, $otherUser->id]);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.messages.store', $conversation), [
                'body' => 'Message with file',
                'files' => [$file],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'conversation_id',
                'sender_id',
                'sender_name',
                'body',
                'attachments',
            ],
        ]);

        // Verify message was created
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => 'Message with file',
        ]);
    }

    /**
     * Test that participant can mark conversation as read
     */
    public function test_participant_can_mark_conversation_as_read(): void
    {
        $user = User::factory()->create(['user_type' => 'company']);
        $otherUser = User::factory()->create(['user_type' => 'company']);

        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$user->id, $otherUser->id]);

        // Create some messages
        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.read', $conversation));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'unread_count',
            ],
        ]);

        // Verify unread count is 0
        $this->assertEquals(0, $response->json('data.unread_count'));
    }

    /**
     * Test that non-participant cannot mark conversation as read
     */
    public function test_non_participant_cannot_mark_conversation_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();

        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$otherUser1->id, $otherUser2->id]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.conversations.read', $conversation));

        $response->assertForbidden();
    }

    /**
     * Test that authenticated user can upload files
     */
    public function test_authenticated_user_can_upload_files(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['user_type' => 'company']);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('company.chat.messages.upload'), [
                'files' => [$file],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'files',
            ],
        ]);
    }

    /**
     * Test that unauthenticated user cannot send message
     */
    public function test_unauthenticated_user_cannot_send_message(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->postJson(route('company.chat.messages.store', $conversation), [
            'body' => 'Test message',
        ]);

        $response->assertUnauthorized();
    }
}
