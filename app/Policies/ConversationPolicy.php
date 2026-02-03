<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the conversation.
     * 
     * User can view conversation if they are a participant.
     * 
     * @param User $user
     * @param Conversation $conversation
     * @return bool
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user->id);
    }

    /**
     * Determine if the user can send a message in the conversation.
     * 
     * User can send message if they are a participant in the conversation.
     * 
     * @param User $user
     * @param Conversation $conversation
     * @return bool
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user->id);
    }
}
