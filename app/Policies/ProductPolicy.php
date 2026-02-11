<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->company_user_id 
            && $user->user_type === 'company';
    }
    
    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->company_user_id 
            && $user->user_type === 'company';
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user && $user->user_type === 'company';
    }

    /**
     * Determine if the user can activate the product.
     */
    public function activate(User $user, Product $product): bool
    {
        return $user->id === $product->company_user_id
            && $user->user_type === 'company';
    }

    /**
     * Determine if the user can deactivate the product.
     */
    public function deactivate(User $user, Product $product): bool
    {
        return $user->id === $product->company_user_id
            && $user->user_type === 'company';
    }
}
