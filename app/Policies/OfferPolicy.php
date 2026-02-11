<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Offer;

class OfferPolicy
{
    /**
     * Determine if the user can update the offer.
     */
    public function update(User $user, Offer $offer): bool
    {
        return $user->id === $offer->company_user_id 
            && $user->user_type === 'company';
    }
    
    /**
     * Determine if the user can delete the offer.
     */
    public function delete(User $user, Offer $offer): bool
    {
        return $user->id === $offer->company_user_id 
            && $user->user_type === 'company';
    }
}
