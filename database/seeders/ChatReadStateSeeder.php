<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Consultant;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Chat Read State Seeder
 * 
 * Creates sample conversations with various read state scenarios for testing and demos.
 * 
 * Scenarios Created:
 * 1. Conversation with all messages read
 * 2. Conversation with some unread messages
 * 3. Conversation with no messages
 * 4. Conversation with messages from both participants
 * 5. Conversation with only client messages (consultant has 0 unread)
 */
class ChatReadStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding chat read state scenarios...');

        // Create test users
        $client1 = User::factory()->create([
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'email' => 'client1@example.com',
            'user_type' => 'customer',
        ]);

        $client2 = User::factory()->create([
            'first_name' => 'فاطمة',
            'last_name' => 'علي',
            'email' => 'client2@example.com',
            'user_type' => 'customer',
        ]);

        $consultantUser1 = User::factory()->create([
            'first_name' => 'د. خالد',
            'last_name' => 'السعيد',
            'email' => 'consultant1@example.com',
            'user_type' => 'consultant',
        ]);

        $consultant1 = Consultant::factory()->create([
            'user_id' => $consultantUser1->id,
        ]);

        $consultantUser2 = User::factory()->create([
            'first_name' => 'د. سارة',
            'last_name' => 'الأحمد',
            'email' => 'consultant2@example.com',
            'user_type' => 'consultant',
        ]);

        $consultant2 = Consultant::factory()->create([
            'user_id' => $consultantUser2->id,
        ]);

        // Scenario 1: Conversation with all messages read
        $this->command->info('Creating Scenario 1: All messages read');
        $booking1 = Booking::factory()->create([
            'client_id' => $client1->id,
            'consultant_id' => $consultant1->id,
            'bookable_type' => Consultant::class,
            'bookable_id' => $consultant1->id,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $conversation1 = Conversation::factory()->create([
            'booking_id' => $booking1->id,
        ]);

        $clientParticipant1 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation1->id,
            'user_id' => $client1->id,
        ]);

        $consultantParticipant1 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation1->id,
            'user_id' => $consultantUser1->id,
        ]);

        $messages1 = [];
        $messages1[] = Message::factory()->create([
            'conversation_id' => $conversation1->id,
            'sender_id' => $consultantUser1->id,
            'body' => 'مرحباً، كيف يمكنني مساعدتك؟',
            'type' => 'text',
            'context' => 'in_session',
        ]);

        $messages1[] = Message::factory()->create([
            'conversation_id' => $conversation1->id,
            'sender_id' => $client1->id,
            'body' => 'أحتاج استشارة بخصوص موضوع معين',
            'type' => 'text',
            'context' => 'in_session',
        ]);

        $messages1[] = Message::factory()->create([
            'conversation_id' => $conversation1->id,
            'sender_id' => $consultantUser1->id,
            'body' => 'بالتأكيد، أنا هنا للمساعدة',
            'type' => 'text',
            'context' => 'in_session',
        ]);

        // Both participants have read all messages
        $clientParticipant1->update([
            'last_read_message_id' => $messages1[2]->id,
            'last_read_at' => now(),
        ]);

        $consultantParticipant1->update([
            'last_read_message_id' => $messages1[2]->id,
            'last_read_at' => now(),
        ]);

        // Scenario 2: Conversation with unread messages for client
        $this->command->info('Creating Scenario 2: Client has unread messages');
        $booking2 = Booking::factory()->create([
            'client_id' => $client1->id,
            'consultant_id' => $consultant2->id,
            'bookable_type' => Consultant::class,
            'bookable_id' => $consultant2->id,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $conversation2 = Conversation::factory()->create([
            'booking_id' => $booking2->id,
        ]);

        $clientParticipant2 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation2->id,
            'user_id' => $client1->id,
        ]);

        $consultantParticipant2 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation2->id,
            'user_id' => $consultantUser2->id,
        ]);

        $messages2 = [];
        $messages2[] = Message::factory()->create([
            'conversation_id' => $conversation2->id,
            'sender_id' => $client1->id,
            'body' => 'هل أنت متاح غداً؟',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        $messages2[] = Message::factory()->create([
            'conversation_id' => $conversation2->id,
            'sender_id' => $consultantUser2->id,
            'body' => 'نعم، أنا متاح في الصباح',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        $messages2[] = Message::factory()->create([
            'conversation_id' => $conversation2->id,
            'sender_id' => $consultantUser2->id,
            'body' => 'يمكنك الحجز من الساعة 9 صباحاً',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        $messages2[] = Message::factory()->create([
            'conversation_id' => $conversation2->id,
            'sender_id' => $consultantUser2->id,
            'body' => 'في انتظار ردك',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        // Client has read only first message, has 3 unread
        $clientParticipant2->update([
            'last_read_message_id' => $messages2[0]->id,
            'last_read_at' => now()->subHours(2),
        ]);

        // Consultant has read all messages
        $consultantParticipant2->update([
            'last_read_message_id' => $messages2[3]->id,
            'last_read_at' => now(),
        ]);

        // Scenario 3: Empty conversation (no messages)
        $this->command->info('Creating Scenario 3: Empty conversation');
        $booking3 = Booking::factory()->create([
            'client_id' => $client2->id,
            'consultant_id' => $consultant1->id,
            'bookable_type' => Consultant::class,
            'bookable_id' => $consultant1->id,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $conversation3 = Conversation::factory()->create([
            'booking_id' => $booking3->id,
        ]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation3->id,
            'user_id' => $client2->id,
        ]);

        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation3->id,
            'user_id' => $consultantUser1->id,
        ]);

        // No messages, both participants have null read markers

        // Scenario 4: Conversation with messages from both, consultant has unread
        $this->command->info('Creating Scenario 4: Consultant has unread messages');
        $booking4 = Booking::factory()->create([
            'client_id' => $client2->id,
            'consultant_id' => $consultant2->id,
            'bookable_type' => Consultant::class,
            'bookable_id' => $consultant2->id,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $conversation4 = Conversation::factory()->create([
            'booking_id' => $booking4->id,
        ]);

        $clientParticipant4 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation4->id,
            'user_id' => $client2->id,
        ]);

        $consultantParticipant4 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation4->id,
            'user_id' => $consultantUser2->id,
        ]);

        $messages4 = [];
        $messages4[] = Message::factory()->create([
            'conversation_id' => $conversation4->id,
            'sender_id' => $consultantUser2->id,
            'body' => 'شكراً على الحجز',
            'type' => 'text',
            'context' => 'in_session',
        ]);

        $messages4[] = Message::factory()->create([
            'conversation_id' => $conversation4->id,
            'sender_id' => $client2->id,
            'body' => 'شكراً لك على الوقت',
            'type' => 'text',
            'context' => 'in_session',
        ]);

        $messages4[] = Message::factory()->create([
            'conversation_id' => $conversation4->id,
            'sender_id' => $client2->id,
            'body' => 'كانت الجلسة مفيدة جداً',
            'type' => 'text',
            'context' => 'in_session',
        ]);

        $messages4[] = Message::factory()->create([
            'conversation_id' => $conversation4->id,
            'sender_id' => $client2->id,
            'body' => 'أتطلع للجلسة القادمة',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        // Client has read all messages
        $clientParticipant4->update([
            'last_read_message_id' => $messages4[3]->id,
            'last_read_at' => now(),
        ]);

        // Consultant has read only first message, has 3 unread
        $consultantParticipant4->update([
            'last_read_message_id' => $messages4[0]->id,
            'last_read_at' => now()->subHours(1),
        ]);

        // Scenario 5: Conversation with only client messages (consultant has 0 unread from own messages)
        $this->command->info('Creating Scenario 5: Only client messages');
        $booking5 = Booking::factory()->create([
            'client_id' => $client1->id,
            'consultant_id' => $consultant1->id,
            'bookable_type' => Consultant::class,
            'bookable_id' => $consultant1->id,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $conversation5 = Conversation::factory()->create([
            'booking_id' => $booking5->id,
        ]);

        $clientParticipant5 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation5->id,
            'user_id' => $client1->id,
        ]);

        $consultantParticipant5 = ConversationParticipant::factory()->create([
            'conversation_id' => $conversation5->id,
            'user_id' => $consultantUser1->id,
        ]);

        $messages5 = [];
        $messages5[] = Message::factory()->create([
            'conversation_id' => $conversation5->id,
            'sender_id' => $client1->id,
            'body' => 'هل يمكنني تغيير موعد الجلسة؟',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        $messages5[] = Message::factory()->create([
            'conversation_id' => $conversation5->id,
            'sender_id' => $client1->id,
            'body' => 'أرجو الرد في أقرب وقت',
            'type' => 'text',
            'context' => 'out_of_session',
        ]);

        // Client has read own messages (0 unread for client)
        $clientParticipant5->update([
            'last_read_message_id' => $messages5[1]->id,
            'last_read_at' => now(),
        ]);

        // Consultant hasn't read yet (2 unread for consultant)
        // last_read_message_id remains null

        $this->command->info('✓ Chat read state scenarios seeded successfully!');
        $this->command->newLine();
        $this->command->info('Summary:');
        $this->command->info('- Scenario 1: All messages read (0 unread for both)');
        $this->command->info('- Scenario 2: Client has 3 unread messages');
        $this->command->info('- Scenario 3: Empty conversation (no messages)');
        $this->command->info('- Scenario 4: Consultant has 3 unread messages');
        $this->command->info('- Scenario 5: Consultant has 2 unread messages (only client messages)');
        $this->command->newLine();
        $this->command->info('Test Users:');
        $this->command->info("- Client 1: {$client1->email}");
        $this->command->info("- Client 2: {$client2->email}");
        $this->command->info("- Consultant 1: {$consultantUser1->email}");
        $this->command->info("- Consultant 2: {$consultantUser2->email}");
    }
}
