<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('returnInvoice.returnInvoiceDetails') || 'تفاصيل فاتورة الاسترجاع' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <InfoItem :label="t('returnInvoice.original_invoice') || 'الفاتورة الأصلية'" :value="returnInvoice.original_invoice?.invoice_no || `#${returnInvoice.original_invoice_id}`" />
          <InfoItem :label="t('users.company') || 'الشركة'" :value="returnInvoice.company ? `${returnInvoice.company.first_name} ${returnInvoice.company.last_name}` : `#${returnInvoice.company_id}`" />
          <InfoItem :label="t('common.status')">
            <Badge :color="statusColor(returnInvoice.status)" variant="light" size="sm">{{ statusLabel(returnInvoice.status) }}</Badge>
          </InfoItem>
          <InfoItem :label="t('returnInvoice.total_refund_amount') || 'مبلغ الاسترجاع الإجمالي'" :value="priceLabel(returnInvoice.total_refund_amount)" />
          <InfoItem :label="t('returnInvoice.return_policy') || 'سياسة الاسترجاع'" :value="returnInvoice.return_policy?.name || `#${returnInvoice.return_policy_id}`" />
          <InfoItem :label="t('common.createdAt')" :value="formatDate(returnInvoice.created_at)" />
          <InfoItem v-if="returnInvoice.notes" :label="t('common.note') || 'ملاحظات'" :value="returnInvoice.notes" />
        </div>
      </div>
    </div>

    <!-- Return Items -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('returnInvoice.items') || 'بنود الاسترجاع' }}</h2>
      </div>
      <div class="p-4 sm:p-6 overflow-x-auto">
        <table class="w-full min-w-full border-separate border-spacing-0">
          <thead>
            <tr class="bg-gray-50 dark:bg-white/[0.06]">
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('returnInvoice.item_id') || 'البند' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('returnInvoice.returned_quantity') || 'الكمية المُرجَعة' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('returnInvoice.unit_price_snapshot') || 'سعر الوحدة' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('returnInvoice.discount') || 'الخصم' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('returnInvoice.refund_amount') || 'مبلغ الاسترجاع' }}</th>
              <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-white/90">{{ t('returnInvoice.is_bonus') || 'بونص' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in returnInvoice.items || []" :key="item.id || idx" class="odd:bg-white even:bg-gray-50 dark:odd:bg-white/[0.03] dark:even:bg-white/[0.06]">
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">#{{ item.original_item_id }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ item.returned_quantity }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">{{ priceLabel(item.unit_price_snapshot) }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200">
                <span v-if="item.discount_type_snapshot">{{ item.discount_type_snapshot === 'percent' ? `${item.discount_value_snapshot}%` : priceLabel(item.discount_value_snapshot) }}</span>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60 text-gray-800 dark:text-gray-200 font-medium">{{ priceLabel(item.refund_amount) }}</td>
              <td class="px-4 py-3 border border-gray-100 dark:border-gray-700/60">
                <Badge :color="item.is_bonus ? 'info' : 'light'" variant="light" size="sm">{{ item.is_bonus ? (t('common.yes') || 'نعم') : (t('common.no') || 'لا') }}</Badge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="flex justify-end">
      <Link :href="route('admin.return-invoices.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
        {{ t('buttons.backToList') || 'العودة للقائمة' }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Badge from '@/components/ui/Badge.vue'
import InfoItem from '@/components/common/InfoItem.vue'

const { t } = useI18n()
defineProps({ returnInvoice: { type: Object, required: true } })

function priceLabel(value) {
  if (value == null) return '—'
  return new Intl.NumberFormat('ar-YE', { style: 'decimal', minimumFractionDigits: 2 }).format(value)
}

function statusColor(status) {
  return { pending: 'warning', approved: 'success', rejected: 'error' }[status] || 'light'
}

function statusLabel(status) {
  return {
    pending: t('returnInvoice.status.pending') || 'قيد الانتظار',
    approved: t('returnInvoice.status.approved') || 'مقبولة',
    rejected: t('returnInvoice.status.rejected') || 'مرفوضة',
  }[status] || status
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString()
}
</script>
