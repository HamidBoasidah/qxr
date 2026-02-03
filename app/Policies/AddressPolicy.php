<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Address;

class AddressPolicy
{
    /**
     * Determine if the user can view any addresses.
     */
    public function viewAny(User $user): bool
    {
        return true; // Any authenticated user can view their own addresses
    }

    /**
     * Determine if the user can create addresses.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create addresses
    }

    /**
     * Determine if the user can view the address.
     */
    public function view(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }

    /**
     * Determine if the user can update the address.
     */
    public function update(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the address.
     */
    public function delete(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }

    /**
     * Determine if the user can activate the address.
     */
    public function activate(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }

    /**
     * Determine if the user can deactivate the address.
     */
    public function deactivate(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }

    /**
     * Determine if the user can set the address as default.
     */
    public function setAsDefault(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }
}
