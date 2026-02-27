<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-full">
      <thead>
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('invoice.invoice_no') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('order.order_no') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('order.total') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('common.status') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('invoice.issued_at') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!invoices || invoices.length === 0">
          <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ t('common.noData') }}
          </td>
        </tr>
        <tr
          v-for="invoice in invoices"
          :key="invoice.id"
          class="border-b border-gray-100 dark:border-gray-700/50 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
        >
          <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
            {{ invoice.invoice_no }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
            {{ invoice.order_no }}
          </td>
          <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
            {{ formatCurrency(invoice.total) }}
          </td>
          <td class="px-4 py-3 text-sm">
            <span :class="getStatusClass(invoice.status)" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
              {{ t(`invoice.status.${invoice.status}`) }}
            </span>
          </td>
          <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
            {{ invoice.issued_at }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  invoices: {
    type: Array,
    default: () => []
  }
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2
  }).format(value)
}

const getStatusClass = (status) => {
  const classes = {
    paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    unpaid: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    void: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
  }
  return classes[status] || classes.unpaid
}
</script>
