<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * يجب أن يكون المستخدم شركة حتى ينشئ منتجًا.
     */
    public function create(?User $user): bool
    {
        return $user?->user_type === 'company';
    }

    /**
     * التعديل مسموح فقط لصاحب المنتج (الشركة التي أنشأته).
     */
    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * الحذف مسموح فقط لصاحب المنتج.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * التفعيل مسموح لصاحب المنتج.
     */
    public function activate(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * التعطيل مسموح لصاحب المنتج.
     */
    public function deactivate(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    protected function ownsProduct(User $user, Product $product): bool
    {
        return $user->user_type === 'company' && (int) $product->company_user_id === (int) $user->id;
    }
}
