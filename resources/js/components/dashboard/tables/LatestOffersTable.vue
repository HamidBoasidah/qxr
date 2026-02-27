<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-full">
      <thead>
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('offers.title') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('users.company') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('offers.scope') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('common.status') }}</th>
          <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">{{ t('common.period') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!offers || offers.length === 0">
          <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ t('common.noData') }}
          </td>
        </tr>
        <tr
          v-for="offer in offers"
          :key="offer.id"
          class="border-b border-gray-100 dark:border-gray-700/50 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
        >
          <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
            {{ offer.title }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
            {{ offer.company }}
          </td>
          <td class="px-4 py-3 text-sm">
            <span :class="getScopeClass(offer.scope)" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
              {{ t(`offers.${offer.scope}`) }}
            </span>
          </td>
          <td class="px-4 py-3 text-sm">
            <span :class="getStatusClass(offer.status)" class="inline-flex rounded-full px-2 py-1 text-xs font-semibold">
              {{ statusLabel(offer.status) }}
            </span>
          </td>
          <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
            {{ offer.start_at }} - {{ offer.end_at }}
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
  offers: {
    type: Array,
    default: () => []
  }
})

const getScopeClass = (scope) => {
  const classes = {
    public: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    private: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
  }
  return classes[scope] || classes.public
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    expired: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
  }
  return classes[status] || classes.active
}

const statusLabel = (status) => {
  // Prefer keys like offers.status_active, fallback to offers.active, then raw status
  const key1 = `offers.status_${status}`
  const key2 = `offers.${status}`
  const translated1 = t(key1)
  if (translated1 !== key1) return translated1
  const translated2 = t(key2)
  if (translated2 !== key2) return translated2
  return status
}
</script>
