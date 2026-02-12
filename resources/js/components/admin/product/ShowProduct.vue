<template>
  <div class="space-y-6">
    <!-- Product Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('product.title') || 'Product' }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <!-- Name -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.name') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ product.name || 'N/A' }}</p>
          </div>

          <!-- SKU -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.sku') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ product.sku || '—' }}</p>
          </div>

          <!-- Category -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.Category') || t('product.Category') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ categoryLabel }}</p>
          </div>

          <!-- Base Price -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.base_price') || 'Base Price' }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ priceLabel(product.base_price) }}</p>
          </div>

          <!-- Unit -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.unit') || 'Unit' }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ product.unit_name || '—' }}</p>
          </div>

          <!-- Status -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.status') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">
              <span
                class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': product.is_active,
                  'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': !product.is_active,
                }"
              >
                {{ product.is_active ? t('common.active') : t('common.inactive') }}
              </span>
            </p>
          </div>

          <!-- Tags (span full width) -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.tags') || t('tags.tagInformation') }}</label>
            <div class="flex flex-wrap gap-2">
              <span v-for="tag in product.tags || []" :key="tag.id" class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                {{ tag.name || tag.title || tag.label }}
              </span>
              <span v-if="!(product.tags || []).length" class="text-sm text-gray-500">—</span>
            </div>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('product.descriptionText') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ product.description || '—' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Images Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('product.images') }}</h2>
      </div>
      <div class="p-4 sm:p-6">
        <div v-if="product.main_image" class="flex justify-center p-4">
          <img :src="`/storage/${product.main_image}`" alt="Main Image" class="max-h-72 rounded-lg border border-gray-200 object-contain dark:border-gray-800" />
        </div>

        <div v-else class="flex justify-center p-10">
          <p class="text-center text-sm text-gray-500 dark:text-gray-400">{{ t('product.noImage') || t('users.noImage') || 'No image' }}</p>
        </div>

        <div v-if="(product.images || []).length" class="mt-4 grid grid-cols-3 gap-3">
          <div v-for="img in product.images" :key="img.id" class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-center h-28 bg-gray-50 dark:bg-white/[0.03]">
              <img :src="`/storage/${img.path}`" class="max-h-28 max-w-full object-contain" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link :href="route('admin.products.index')" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
        {{ t('buttons.backToList') }}
      </Link>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const props = defineProps({
  product: { type: Object, required: true },
})

const product = computed(() => props.product || {})

const categoryLabel = computed(() => {
  const category = product.value.category || product.value.product_category
  if (!category) return product.value.category_name || '—'
  const loc = locale.value
  return category?.name?.[loc] ?? category?.name_ar ?? category?.name_en ?? category?.name ?? product.value.category_name ?? '—'
})

function priceLabel(price) {
  if (price === null || price === undefined) return '—'
  return Number(price).toLocaleString(locale.value)
}
</script>
