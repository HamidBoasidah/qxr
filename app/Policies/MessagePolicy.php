<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the message.
     * 
     * User can view message if they are a participant in the conversation.
     * 
     * @param User $user
     * @param Message $message
     * @return bool
     */
    public function view(User $user, Message $message): bool
    {
        return $message->conversation->isParticipant($user->id);
    }
}
