<template>
  <div
    v-if="isVisible"
    class="flex items-center justify-between gap-3 w-full sm:max-w-[340px] rounded-md border-b-4 bg-white p-3 shadow-theme-sm dark:bg-[#1E2634]"
    :class="[borderColorClass, shadowClass]"
  >
    <div class="flex items-center gap-4">
      <div
        class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0"
        :class="iconBackgroundClass"
      >
        <component :is="icon" class="fill-current" :class="iconColorClass" />
      </div>

      <h4 class="text-sm text-gray-800 sm:text-base dark:text-white/90">
        {{ message }}
      </h4>
    </div>

    <button @click="close" class="text-gray-400 hover:text-gray-800 dark:hover:text-white/90">
      <svg
        class="fill-current"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z"
        />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ErrorHexaIcon, SuccessIcon } from '@/icons'
import { ref, computed } from 'vue'
const emit = defineEmits(['close'])

const props = withDefaults(
  defineProps<{
    type: 'success' | 'error'
    message: string
  }>(),
  {
    type: 'success',
  },
)

const isVisible = ref(true)

const close = () => {
  isVisible.value = false
  emit('close')
}

const borderColorClass = computed(() => ({
  'border-success-500': props.type === 'success',
  'border-error-500': props.type === 'error',
}))

const shadowClass = computed(() => ({
  'shadow-theme-sm': props.type === 'success' || props.type === 'error'
}))

const iconBackgroundClass = computed(() => ({
  'bg-success-50 dark:bg-success-500/[0.15]': props.type === 'success',
  'bg-error-50 dark:bg-error-500/[0.15]': props.type === 'error',
}))

const iconColorClass = computed(() => ({
  'text-success-600 dark:text-success-500': props.type === 'success',
  'text-error-600 dark:text-error-500': props.type === 'error',
}))

const icon = computed(() => {
  switch (props.type) {
    case 'success':
      return SuccessIcon
    case 'error':
      return ErrorHexaIcon
    default:
      return SuccessIcon
  }
})
</script>
