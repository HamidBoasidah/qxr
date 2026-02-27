<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800/50">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-3">
          <div :class="iconBgClass" class="flex h-12 w-12 items-center justify-center rounded-xl text-2xl shadow-sm">
            {{ icon }}
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ title }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ displayValue }}</p>
          </div>
        </div>

        <!-- Trend Indicator -->
        <div v-if="trend" class="mt-3 flex items-center gap-1 text-sm">
          <span v-if="trend.direction === 'up'" class="flex items-center gap-1 font-semibold text-green-600 dark:text-green-400">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            {{ trend.percentage }}%
          </span>
          <span v-else-if="trend.direction === 'down'" class="flex items-center gap-1 font-semibold text-red-600 dark:text-red-400">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            {{ trend.percentage }}%
          </span>
          <span v-else class="flex items-center gap-1 font-semibold text-gray-600 dark:text-gray-300">
            →  {{ trend.percentage }}%
          </span>
          <span class="text-gray-500 dark:text-gray-400 text-xs">{{ t('dashboard.vs_previous_period') }}</span>
        </div>

        <!-- Details Slot -->
        <slot name="details"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: String,
    default: '📊'
  },
  color: {
    type: String,
    default: 'blue'
  },
  trend: {
    type: Object,
    default: null
  }
})

const displayValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString()
  }
  return props.value
})

const iconBgClass = computed(() => {
  const colors = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400',
    indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-500/20 dark:text-yellow-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400',
    pink: 'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400',
  }
  return colors[props.color] || colors.blue
})
</script>
