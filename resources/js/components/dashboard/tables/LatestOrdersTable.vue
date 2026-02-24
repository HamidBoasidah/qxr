<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-full">
      <thead>
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('order.order_no') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('users.company') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('users.customer') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('common.status') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('common.date') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!orders || orders.length === 0">
          <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ t('common.noData') }}
          </td>
        </tr>
        <tr
          v-for="order in orders"
          :key="order.id"
          class="border-b border-gray-100 dark:border-gray-800 transition-colors hover:bg-gray-50 dark:hover:bg-white/[0.03]"
        >
          <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
            {{ order.order_no }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
            {{ order.company }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
            {{ order.customer }}
          </td>
          <td class="px-4 py-3 text-sm">
            <span :class="getStatusClass(order.status)" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
              {{ t(`order.status.${order.status}`) }}
            </span>
          </td>
          <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
            {{ order.created_at }}
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
  orders: {
    type: Array,
    default: () => []
  }
})

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    preparing: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    shipped: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
  }
  return classes[status] || classes.pending
}
</script>
