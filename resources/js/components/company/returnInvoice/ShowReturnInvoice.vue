<template>
  <div class="space-y-6">
    <!-- Header Info -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('returnInvoice.returnInvoiceDetails') || 'تفاصيل فاتورة الاسترجاع' }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <InfoItem :label="t('returnInvoice.original_invoice') || 'الفاتورة الأصلية'" :value="returnInvoice.original_invoice?.invoice_no || `#${returnInvoice.original_invoice_id}`" />
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

    <!-- Change Status (only if pending) -->
    <div v-if="availableTransitions.length > 0" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('invoice.changeStatus') || 'تغيير الحالة' }}</h2>
      </div>
      <div class="p-4 sm:p-6 space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('invoice.newStatus') || 'الحالة الجديدة' }}
            </label>
            <select
              v-model="statusForm.status"
              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
              <option value="" disabled>{{ t('invoice.selectStatus') || 'اختر الحالة' }}</option>
              <option v-for="s in availableTransitions" :key="s" :value="s">
                {{ statusLabel(s) }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ t('invoice.statusNote') || 'ملاحظة (اختياري)' }}
            </label>
            <textarea
              v-model="statusForm.note"
              rows="2"
              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
              :placeholder="t('invoice.statusNotePlaceholder') || 'أضف ملاحظة حول هذا التغيير...'"
            ></textarea>
          </div>
        </div>
        <p v-if="statusForm.error" class="text-sm text-red-600 dark:text-red-400">{{ statusForm.error }}</p>
        <div class="flex justify-end">
          <button
            :disabled="!statusForm.status || statusForm.processing"
            @click="submitStatusChange"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <svg v-if="statusForm.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            {{ t('invoice.confirmStatusChange') || 'تأكيد التغيير' }}
          </button>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link :href="route('company.return-invoices.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
        {{ t('buttons.backToList') || 'العودة للقائمة' }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import Badge from '@/components/ui/Badge.vue'
import InfoItem from '@/components/common/InfoItem.vue'

const { t } = useI18n()
const props = defineProps({ returnInvoice: { type: Object, required: true } })

const returnInvoice = computed(() => props.returnInvoice || {})

// الانتقالات المسموحة: pending → approved أو pending → rejected فقط
const transitionMap = {
  pending:  ['approved', 'rejected'],
  approved: [],
  rejected: [],
}

const availableTransitions = computed(() => transitionMap[returnInvoice.value.status] ?? [])

const statusForm = reactive({
  status:     '',
  note:       '',
  processing: false,
  error:      '',
})

function submitStatusChange() {
  if (!statusForm.status || statusForm.processing) return

  statusForm.processing = true
  statusForm.error      = ''

  const routeName = statusForm.status === 'approved'
    ? 'company.return-invoices.approve'
    : 'company.return-invoices.reject'

  router.patch(
    route(routeName, returnInvoice.value.id),
    { note: statusForm.note },
    {
      preserveScroll: true,
      onSuccess: () => {
        statusForm.status = ''
        statusForm.note   = ''
      },
      onError: (errors) => {
        statusForm.error = errors?.status || errors?.message || t('returnInvoice.actionError') || 'حدث خطأ'
      },
      onFinish: () => {
        statusForm.processing = false
      },
    }
  )
}

function priceLabel(value) {
  if (value == null) return '—'
  return new Intl.NumberFormat('ar-YE', { style: 'decimal', minimumFractionDigits: 2 }).format(value)
}

function statusColor(status) {
  return { pending: 'warning', approved: 'success', rejected: 'error' }[status] || 'light'
}

function statusLabel(status) {
  return {
    pending:  t('returnInvoice.status.pending')  || 'قيد الانتظار',
    approved: t('returnInvoice.status.approved') || 'مقبولة',
    rejected: t('returnInvoice.status.rejected') || 'مرفوضة',
  }[status] || status
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString()
}
</script>
