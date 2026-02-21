<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->user_type === 'customer' || $user->user_type === 'company';
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // العميل يمكنه رؤية طلباته فقط
        if ($user->user_type === 'customer') {
            return $order->customer_user_id === $user->id;
        }
        
        // الشركة يمكنها رؤية الطلبات الموجهة لها فقط
        if ($user->user_type === 'company') {
            return $order->company_user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->user_type === 'customer';
    }

    /**
     * Determine if the user can update the order.
     * Only company can update order (change status, add company notes)
     */
    public function update(User $user, Order $order): bool
    {
        // الشركة فقط يمكنها تعديل الطلبات الموجهة لها (مثل تغيير الحالة)
        return $user->user_type === 'company' && $order->company_user_id === $user->id;
    }

    /**
     * Determine if the user can cancel the order.
     * Only customer can cancel their own pending orders
     */
    public function cancel(User $user, Order $order): bool
    {
        // العميل يمكنه إلغاء طلباته المعلقة فقط
        return $user->user_type === 'customer' && $order->customer_user_id === $user->id;
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // العميل يمكنه حذف طلباته إذا لم تدخل في مراحل المعالجة (قبل الشحن/التسليم)
        if ($user->user_type !== 'customer' || $order->customer_user_id !== $user->id) {
            return false;
        }

        // حالات لا يُسمح بالحذف فيها
        $lockedStatuses = ['approved', 'preparing', 'shipped', 'delivered'];

        return !in_array($order->status, $lockedStatuses, true);
    }
}
