<template>
  <div class="flex flex-col gap-1">
    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ label }}</label>
    <div class="text-base text-gray-800 dark:text-white/90 flex items-center gap-2">
      <slot>
        <span v-if="badge" :class="badge" class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium">
          {{ value ?? '—' }}
        </span>
        <span v-else>{{ displayValue }}</span>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number, null], default: null },
  badge: { type: String, default: null },
})

const displayValue = computed(() => (props.value === null || props.value === undefined || props.value === '' ? '—' : props.value))
</script>
