<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-full">
      <thead>
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('product.name') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('users.company') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('product.base_price') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('common.status') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!products || products.length === 0">
          <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ t('common.noData') }}
          </td>
        </tr>
        <tr
          v-for="product in products"
          :key="product.id"
          class="border-b border-gray-100 dark:border-gray-700/50 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
        >
          <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
            {{ product.name }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
            {{ product.company }}
          </td>
          <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
            {{ formatCurrency(product.base_price) }}
          </td>
          <td class="px-4 py-3 text-sm">
            <span :class="getStatusClass(product.is_active)" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
              {{ product.is_active ? t('common.active') : t('common.inactive') }}
            </span>
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
  products: {
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

const getStatusClass = (isActive) => {
  return isActive
    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}
</script>
