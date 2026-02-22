<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        // العملاء والشركات يمكنهم رؤية الفواتير
        return in_array($user->user_type, ['customer', 'company']);
    }

    /**
     * Determine if the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // التحقق من أن الفاتورة تنتمي للمستخدم
        if (!$invoice->relationLoaded('order')) {
            $invoice->load('order');
        }

        if (!$invoice->order) {
            return false;
        }

        // العميل يرى فواتيره فقط
        if ($user->user_type === 'customer') {
            return $invoice->order->customer_user_id === $user->id;
        }

        // الشركة ترى فواتير الطلبات الموجهة لها فقط
        if ($user->user_type === 'company') {
            return $invoice->order->company_user_id === $user->id;
        }

        return false;
    }
}
