<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-3">
          <div :class="iconBgClass" class="flex h-12 w-12 items-center justify-center rounded-lg text-2xl">
            {{ icon }}
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ title }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ displayValue }}</p>
          </div>
        </div>

        <!-- Trend Indicator -->
        <div v-if="trend" class="mt-3 flex items-center gap-1 text-sm">
          <span v-if="trend.direction === 'up'" class="text-green-600 dark:text-green-400">
            ↑ {{ trend.percentage }}%
          </span>
          <span v-else-if="trend.direction === 'down'" class="text-red-600 dark:text-red-400">
            ↓ {{ trend.percentage }}%
          </span>
          <span v-else class="text-gray-600 dark:text-gray-400">
            → {{ trend.percentage }}%
          </span>
          <span class="text-gray-500 dark:text-gray-500 text-xs">{{ t('dashboard.vs_previous_period') }}</span>
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
    blue: 'bg-blue-100 dark:bg-blue-900/30',
    green: 'bg-green-100 dark:bg-green-900/30',
    purple: 'bg-purple-100 dark:bg-purple-900/30',
    indigo: 'bg-indigo-100 dark:bg-indigo-900/30',
    yellow: 'bg-yellow-100 dark:bg-yellow-900/30',
    orange: 'bg-orange-100 dark:bg-orange-900/30',
    pink: 'bg-pink-100 dark:bg-pink-900/30',
    red: 'bg-red-100 dark:bg-red-900/30',
  }
  return colors[props.color] || colors.blue
})
</script>
