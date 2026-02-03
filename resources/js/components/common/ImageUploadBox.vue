<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
  >
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
      <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t(label) }}</h2>
    </div>
    <div class="p-4 sm:p-6">
      <label
        :for="inputId"
        class="shadow-theme-xs group relative block cursor-pointer rounded-lg border-2 border-dashed border-gray-300 transition hover:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-brand-500"
      >
        <div v-if="!previewUrl" class="flex justify-center p-10">
          <div class="flex max-w-[260px] flex-col items-center gap-4">
            <div
              class="inline-flex h-13 w-13 items-center justify-center rounded-full border border-gray-200 text-gray-700 transition dark:border-gray-800 dark:text-gray-400"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M20.0004 16V18.5C20.0004 19.3284 19.3288 20 18.5004 20H5.49951C4.67108 20 3.99951 19.3284 3.99951 18.5V16M12.0015 4L12.0015 16M7.37454 8.6246L11.9994 4.00269L16.6245 8.6246" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
              <span class="font-medium text-gray-800 dark:text-white/90">{{ t('common.clickToUpload') }}</span>
              {{ t('common.orDragDrop') }}
            </p>
          </div>
        </div>
        <div v-else class="relative flex justify-center p-4">
          <img :src="previewUrl" alt="Image preview" class="max-h-48 rounded-lg border border-gray-200 object-contain dark:border-gray-800" />
          <button type="button" @click.stop="remove" class="absolute -top-2 -right-2 hidden rounded-full bg-error-500 p-1 text-white shadow group-hover:flex">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>
        <input ref="inputRef" :id="inputId" type="file" class="hidden" :accept="accept" @change="onFileChange" />
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: [File, Object, null], default: null },
  inputId: { type: String, default: 'product-image' },
  accept: { type: String, default: 'image/*' },
  label: { type: String, default: 'users.userImage' },
})
const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const inputRef = ref(null)
const previewUrl = ref(null)

watch(() => props.modelValue, (val) => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  if (val instanceof File) {
    previewUrl.value = URL.createObjectURL(val)
  } else if (typeof val === 'string' && val) {
    // if backend provided existing url
    previewUrl.value = val
  }
})

function onFileChange(e) {
  const file = e.target.files?.[0] || null
  emit('update:modelValue', file)
}

function remove() {
  emit('update:modelValue', null)
  if (inputRef.value) inputRef.value.value = ''
}

onBeforeUnmount(() => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})
</script>

<style scoped>
/* keep styling minimal — uses existing utility classes */
</style>
