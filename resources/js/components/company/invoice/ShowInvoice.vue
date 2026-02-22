<template>
  <div class="space-y-6">
    <!-- Invoice Information -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('invoice.invoiceDetails') || 'تفاصيل الفاتورة' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <InfoItem :label="t('invoice.invoice_no') || 'رقم الفاتورة'" :value="invoice.invoice_no" />
          <InfoItem :label="t('common.status')">
            <Badge :color="statusColor(invoice.status)" variant="light" size="sm">
              {{ statusLabel(invoice.status) }}
            </Badge>
          </InfoItem>
          <InfoItem :label="t('order.order_no') || 'رقم الطلب'" :value="invoice.order_no" />
          <InfoItem :label="t('invoice.issued_at') || 'تاريخ الإصدار'" :value="invoice.issued_at" />
          <InfoItem :label="t('users.company') || 'الشركة'" :value="invoice.company_name" />
          <InfoItem :label="t('users.customer') || 'العميل'" :value="invoice.customer_name" />
        </div>
      </div>
    </div>

    <!-- Related Order -->
    <div v-if="invoice.order" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('invoice.relatedOrder') || 'الطلب المرتبط' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <InfoItem :label="t('order.order_no') || 'رقم الطلب'" :value="invoice.order.order_no" />
          <InfoItem :label="t('common.status')">
            <Badge :color="orderStatusColor(invoice.order.status)" variant="light" size="sm">
              {{ orderStatusLabel(invoice.order.status) }}
            </Badge>
          </InfoItem>
          <InfoItem :label="t('order.submitted_at') || 'تاريخ الطلب'" :value="invoice.order.submitted_at" />
          <InfoItem :label="t('order.approved_at') || 'تاريخ الموافقة'" :value="invoice.order.approved_at || '—'" />
        </div>
      </div>
    </div>

    <!-- Totals -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('order.totals') || 'الإجماليات' }}</h2>
      </div>
      <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <InfoItem :label="t('order.subtotal') || 'الإجمالي قبل الخصم'" :value="priceLabel(invoice.subtotal_snapshot)" />
        <InfoItem :label="t('order.total_discount') || 'إجمالي الخصم'" :value="priceLabel(invoice.discount_total_snapshot)" />
        <InfoItem :label="t('order.final_total') || 'الإجمالي النهائي'" :value="priceLabel(invoice.total_snapshot)" />
      </div>
    </div>

    <!-- Invoice Items -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('invoice.items') || 'عناصر الفاتورة' }}</h2>
      </div>
      <div class="p-4 sm:p-6 overflow-x-auto">
        <table class="w-full min-w-full border-separate border-spacing-0">
          <thead>
            <tr class="bg-gray-50 dark:bg-white/[0.06]">
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('product.name') || 'المنتج' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('common.quantity') || 'الكمية' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('product.base_price') || 'سعر الوحدة' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('order.total') || 'الإجمالي' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, rowIdx) in invoice.items || []"
              :key="item.id || rowIdx"
              class="odd:bg-white even:bg-gray-50 dark:odd:bg-white/[0.03] dark:even:bg-white/[0.06] transition-colors"
            >
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">
                {{ item.product_name || item.description_snapshot || '—' }}
              </td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.qty }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ priceLabel(item.unit_price_snapshot) }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ priceLabel(item.line_total_snapshot) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Bonus Items -->
    <div v-if="(invoice.bonus_items || []).length > 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('offers.bonuses') || 'الهدايا' }}</h2>
      </div>
      <div class="p-4 sm:p-6 overflow-x-auto">
        <table class="w-full min-w-full border-separate border-spacing-0">
          <thead>
            <tr class="bg-gray-50 dark:bg-white/[0.06]">
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('product.name') || 'المنتج' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('common.quantity') || 'الكمية' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('common.note') || 'ملاحظة' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, rowIdx) in invoice.bonus_items"
              :key="item.id || rowIdx"
              class="odd:bg-white even:bg-gray-50 dark:odd:bg-white/[0.03] dark:even:bg-white/[0.06] transition-colors"
            >
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">
                {{ item.product_name || item.note || '—' }}
              </td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.qty }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.note || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import Badge from '@/components/ui/Badge.vue';
import InfoItem from '@/components/common/InfoItem.vue';

const { t } = useI18n();

defineProps({
  invoice: {
    type: Object,
    required: true,
  },
});

function priceLabel(value) {
  if (value == null) return '—';
  return new Intl.NumberFormat('ar-YE', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
}

function statusColor(status) {
  const colors = {
    unpaid: 'warning',
    paid: 'success',
    void: 'dark',
  };
  return colors[status] || 'light';
}

function statusLabel(status) {
  const labels = {
    unpaid: t('invoice.status.unpaid') || 'غير مدفوعة',
    paid: t('invoice.status.paid') || 'مدفوعة',
    void: t('invoice.status.void') || 'ملغاة',
  };
  return labels[status] || status;
}

function orderStatusColor(status) {
  const map = {
    pending: 'warning',
    approved: 'info',
    preparing: 'info',
    shipped: 'info',
    delivered: 'success',
    rejected: 'error',
    cancelled: 'dark',
  };
  return map[status] || 'light';
}

function orderStatusLabel(status) {
  const labels = {
    pending: t('order.status.pending') || 'قيد الانتظار',
    approved: t('order.status.approved') || 'تمت الموافقة',
    preparing: t('order.status.preparing') || 'قيد التحضير',
    shipped: t('order.status.shipped') || 'تم الشحن',
    delivered: t('order.status.delivered') || 'تم التسليم',
    rejected: t('order.status.rejected') || 'مرفوض',
    cancelled: t('order.status.cancelled') || 'ملغي',
  };
  return labels[status] || status;
}
</script>
