<template>
  <form class="space-y-6" @submit.prevent="create">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('categories.categoryInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6 dark:border-gray-800">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div>
            <label for="category-name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('categories.name') }}
            </label>
            <input
              v-model="form.name"
              type="text"
              id="category-name"
              autocomplete="off"
              :placeholder="t('categories.name')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-error-500">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('categories.categoryType') || 'Category type' }}
            </label>
            <div class="relative z-20 bg-transparent">
              <select
                v-model="form.category_type"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option value="" disabled>--</option>
                <option value="company">{{ t('categories.types.company') || 'Company' }}</option>
                <option value="customer">{{ t('categories.types.customer') || 'Customer' }}</option>
                <option value="product">{{ t('categories.types.product') || 'Product' }}</option>
              </select>
              <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </div>
            <p v-if="form.errors.category_type" class="mt-1 text-sm text-error-500">{{ form.errors.category_type }}</p>
          </div>
        </div>
        
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('categories.iconManagement') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <!-- Icon Preview Display -->
        <div v-if="iconPreviewUrl" class="mb-6">
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ t('categories.currentIcon') }}
          </label>
          <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
              <img :src="iconPreviewUrl" alt="Icon Preview" class="h-12 w-12 object-contain" />
            </div>
            <button 
              type="button"
              @click="removeIcon" 
              class="inline-flex items-center gap-2 rounded-lg bg-red-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-600"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
              {{ t('categories.removeIcon') }}
            </button>
          </div>
        </div>

        <!-- Upload New Icon -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ t('categories.uploadIcon') }}
          </label>
          <div class="flex items-center gap-4">
            <input
              ref="iconInput"
              type="file"
              accept=".svg"
              @change="handleIconUpload"
              class="hidden"
            />
            <button 
              type="button"
              @click="triggerFileInput"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-600"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              {{ t('categories.selectIcon') }}
            </button>
            <Transition
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 scale-95 translate-x-2"
              enter-to-class="opacity-100 scale-100 translate-x-0"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 scale-100 translate-x-0"
              leave-to-class="opacity-0 scale-95 translate-x-2"
            >
              <span v-if="selectedIcon" class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-48">
                {{ selectedIcon.name }}
              </span>
            </Transition>
          </div>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ t('categories.iconRequirements') }}
          </p>
          <p v-if="form.errors.icon" class="mt-1 text-sm text-red-500">{{ form.errors.icon }}</p>
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('common.status') }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <label
          for="toggle-active"
          class="flex cursor-pointer select-none items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-400"
        >
          <div class="relative">
            <input type="checkbox" id="toggle-active" class="sr-only" v-model="form.is_active" />
            <div
              class="block h-6 w-11 rounded-full"
              :class="form.is_active ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'"
            ></div>
            <div
              :class="form.is_active ? 'translate-x-full' : 'translate-x-0'"
              class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-theme-sm duration-300 ease-linear"
            ></div>
          </div>
          <span
            class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
            :class="{
              'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': form.is_active,
              'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': !form.is_active,
            }"
          >
            {{ form.is_active ? t('common.active') : t('common.inactive') }}
          </span>
        </label>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('admin.categories.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>
      <button
        type="submit"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
        :class="{ 'cursor-not-allowed opacity-70': form.processing }"
        :disabled="form.processing"
      >
        {{ form.processing ? t('common.loading') : t('buttons.create') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'
import { ref } from 'vue'

const { t } = useI18n()
const { success, error } = useNotifications()

const selectedIcon = ref(null)
const iconPreviewUrl = ref(null)
const iconInput = ref(null)

const props = defineProps({})

const form = useForm({
  name: '',
  category_type: '',
  is_active: true,
  icon: null,
})

// استخدام دالة منفصلة لتجنب الوميض
function triggerFileInput() {
  if (iconInput.value) {
    iconInput.value.click()
  }
}

async function handleIconUpload(event) {
  const file = event.target.files[0]
  if (!file) return
  
  // Validate file type
  if (!file.type.includes('svg')) {
    error(t('validation.invalidFileType', { name: file.name, types: 'SVG' }))
    event.target.value = ''
    return
  }
  
  // Validate file size (100KB = 100 * 1024 bytes)
  if (file.size > 100 * 1024) {
    error(t('validation.fileTooLarge', { name: file.name, maxSize: '0.1' }))
    event.target.value = ''
    return
  }
  
  selectedIcon.value = file
  form.icon = file
  
  // إنشاء URL للمعاينة
  iconPreviewUrl.value = URL.createObjectURL(file)
}

function removeIcon() {
  // تحرير URL المعاينة
  if (iconPreviewUrl.value) {
    URL.revokeObjectURL(iconPreviewUrl.value)
  }
  
  selectedIcon.value = null
  iconPreviewUrl.value = null
  form.icon = null
  
  // إعادة تعيين input
  if (iconInput.value) {
    iconInput.value.value = ''
  }
}

function create() {
  form.post(route('admin.categories.store'), {
    onSuccess: () => {
      success(t('categories.categoryCreatedSuccessfully'))
      form.reset()
      selectedIcon.value = null
      if (iconPreviewUrl.value) {
        URL.revokeObjectURL(iconPreviewUrl.value)
        iconPreviewUrl.value = null
      }
    },
    onError: () => {
      error(t('categories.categoryCreationFailed'))
    },
    preserveScroll: true,
  })
}
</script>
