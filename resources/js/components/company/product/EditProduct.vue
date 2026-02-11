<template>
  <div class="space-y-6">
    <!-- Product Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('product.title') || 'Edit Product' }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <!-- Name -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('product.name') }} <span class="text-error-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              :placeholder="t('product.name')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-error-500">{{ form.errors.name }}</p>
          </div>

          <!-- SKU -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('product.sku') }}
            </label>
            <input
              v-model="form.sku"
              type="text"
              :placeholder="t('product.sku')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.sku" class="mt-1 text-sm text-error-500">{{ form.errors.sku }}</p>
          </div>

          <!-- Category -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('categories.name') }}
            </label>
            <div class="relative z-20 bg-transparent">
              <select
                v-model="form.category_id"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option :value="null" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                  {{ t('categories.selectCategories') || t('product.selectCategory') || 'Select category' }}
                </option>
                <option
                  v-for="cat in categories"
                  :key="cat.id"
                  :value="cat.id"
                  class="text-gray-700 dark:bg-gray-900 dark:text-gray-400"
                >
                  {{ categoryName(cat) }}
                </option>
              </select>
              <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
            </div>
            <p v-if="form.errors.category_id" class="mt-1 text-sm text-error-500">{{ form.errors.category_id }}</p>
          </div>

          <!-- Base Price -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('product.base_price') || 'Base Price' }}
            </label>
            <input
              v-model.number="form.base_price"
              type="number"
              min="0"
              step="0.01"
              :placeholder="t('product.base_price') || '0.00'"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.base_price" class="mt-1 text-sm text-error-500">{{ form.errors.base_price }}</p>
          </div>

          <!-- Unit Name -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('product.unit') || 'Unit' }}
            </label>
            <input
              v-model="form.unit_name"
              type="text"
              :placeholder="t('product.unitPlaceholder') || 'e.g. piece'"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.unit_name" class="mt-1 text-sm text-error-500">{{ form.errors.unit_name }}</p>
          </div>

          <!-- Active Toggle -->
          <div class="flex items-end gap-3">
            <label class="flex cursor-pointer select-none items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-400">
              <div class="relative">
                <input type="checkbox" class="sr-only" v-model="form.is_active" />
                <div class="block h-6 w-11 rounded-full" :class="form.is_active ? 'bg-brand-500' : 'bg-gray-200 dark:bg-white/10'"></div>
                <div :class="form.is_active ? 'translate-x-full' : 'translate-x-0'" class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-theme-sm duration-300 ease-linear"></div>
              </div>
              <span :class="form.is_active ? 'text-green-600' : 'text-error-600'">
                {{ form.is_active ? t('common.active') : t('common.inactive') }}
              </span>
            </label>
          </div>

          <!-- Tags -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('tags.tagInformation') || t('product.tags') || 'Tags' }}
            </label>
            <MultipleSelect v-model="selectedTags" :options="tagsOptions" />
            <p v-if="form.errors.tag_ids" class="mt-1 text-sm text-error-500">{{ form.errors.tag_ids }}</p>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('product.descriptionText') }}
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              :placeholder="t('product.descriptionPlaceholder') || t('product.descriptionText')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-error-500">{{ form.errors.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Images Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('product.images') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6 space-y-6">
        <!-- Main Image -->
        <div>
          <ImageUploadBox
            v-model="form.main_image"
            input-id="product-main-image"
            :label="'product.images'"
            :initial-image="initialMainImage"
          />
          <p v-if="form.errors.main_image" class="mt-1 text-sm text-error-500">{{ form.errors.main_image }}</p>
        </div>

        <!-- Existing images with remove toggle -->
        <div v-if="existingImages.length" class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
          <div class="mb-2 flex items-center justify-between">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('product.currentImages') || 'الصور الحالية' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ totalImagesCount }}/{{ maxImages }}</p>
          </div>

          <div class="flex flex-wrap gap-3">
            <button
              v-for="img in existingImages"
              :key="img.id"
              type="button"
              class="group relative h-20 w-20 overflow-hidden rounded-md border text-left transition"
              :class="img.markedForDeletion ? 'border-error-400 ring-2 ring-error-400/60 bg-error-50/30' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/[0.03]'"
              @click="toggleExistingImage(img)"
            >
              <img :src="`/storage/${img.path}`" class="h-full w-full object-cover" />
              <div v-if="img.markedForDeletion" class="absolute inset-0 flex items-center justify-center bg-error-600/70 text-white text-xs font-semibold">{{ t('common.remove') || 'Remove' }}</div>
            </button>
          </div>

          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">انقر على الصورة لازالتها</p>
        </div>

        <!-- Additional Images (max 5) -->
        <div>
          <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ t('product.images') }} ({{ t('product.orDragDrop') || 'Max 5 images' }})
          </label>
          <div
            class="shadow-theme-xs group relative block cursor-pointer rounded-lg border-2 border-dashed border-gray-300 transition hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-brand-500"
            :class="{ 'border-error-500 dark:border-error-500': form.errors.images || imagesError }"
          >
            <div class="p-4">
              <div class="flex flex-wrap gap-3">
                <label
                  v-if="imagePreviews.length < maxImages"
                  class="flex h-28 w-28 flex-col items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-brand-500 dark:border-gray-700 dark:text-gray-400"
                  :for="'product-images'"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M20.0004 16V18.5C20.0004 19.3284 19.3288 20 18.5004 20H5.49951C4.67108 20 3.99951 19.3284 3.99951 18.5V16M12.0015 4L12.0015 16M7.37454 8.6246L11.9994 4.00269L16.6245 8.6246" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <span class="mt-1 text-xs">{{ t('product.clickToUpload') }}</span>
                  <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ imagePreviews.length }}/{{ maxImages }}</span>
                </label>

                <div
                  v-for="(img, idx) in imagePreviews"
                  :key="img.url"
                  class="relative h-28 w-28 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03]"
                >
                  <img :src="img.url" class="h-full w-full object-cover" />
                  <button
                    type="button"
                    class="absolute right-1 top-1 rounded-full bg-error-500 p-1 text-white shadow hover:bg-error-600"
                    @click.prevent="removeImage(idx)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <input
              ref="imagesInput"
              id="product-images"
              type="file"
              class="hidden"
              accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
              multiple
              @change="handleImagesChange"
            />
          </div>
          <p v-if="imagesError" class="mt-1 text-sm text-error-500">{{ imagesError }}</p>
          <p v-else-if="form.errors.images" class="mt-1 text-sm text-error-500">{{ form.errors.images }}</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('company.products.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>

      <button
        @click="update"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
        :class="{ 'cursor-not-allowed opacity-70': form.processing }"
        :disabled="form.processing"
      >
        {{ form.processing ? t('common.loading') : t('buttons.update') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onBeforeUnmount, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { route } from '@/route'
import { useNotifications } from '@/composables/useNotifications'
import MultipleSelect from '@/components/ui/MultipleSelect.vue'
import ImageUploadBox from '@/Components/common/ImageUploadBox.vue'

const { t, locale } = useI18n()
const { success, error } = useNotifications()

const props = defineProps({
  product: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  tags: { type: Array, default: () => [] },
})

const categories = props.categories ?? []
const existingImages = ref((props.product?.images ?? []).map(i => ({ ...i, markedForDeletion: false })))

const tagsOptions = computed(() => (props.tags || []).map(tag => ({ value: tag.id, label: tag.name })))

const initialTagIds = (props.product?.tags || []).map(t => t.id)
const initialMainImage = props.product?.main_image ? `/storage/${props.product.main_image}` : null

const form = useForm({
  _method: 'PUT',
  name: props.product?.name ?? '',
  sku: props.product?.sku ?? '',
  category_id: props.product?.category_id ?? null,
  description: props.product?.description ?? '',
  unit_name: props.product?.unit_name ?? '',
  base_price: props.product?.base_price ?? '',
  is_active: props.product?.is_active ?? true,
  main_image: null, // only set when user uploads/removes
  images: null, // only set when user uploads new ones
  delete_image_ids: [],
  tag_ids: initialTagIds,
})

// main image change tracking (to avoid overriding when untouched)
const mainImageChanged = ref(false)
watch(
  () => form.main_image,
  (val) => {
    // if user sets a File or clears it, mark changed
    if (val instanceof File || val === null) {
      mainImageChanged.value = true
    }
  }
)

const selectedTags = computed({
  get() {
    return tagsOptions.value.filter(opt => form.tag_ids.includes(opt.value))
  },
  set(opts) {
    form.tag_ids = opts.map(o => o.value)
  },
})

// Multiple images upload (optional)
const maxImages = 5
const imagesInput = ref(null)
const imagePreviews = ref([]) // [{file, url}]
const imagesError = ref(null)
const imagesChanged = ref(false)

const keptExistingCount = computed(() => existingImages.value.filter(img => !img.markedForDeletion).length)
const totalImagesCount = computed(() => keptExistingCount.value + imagePreviews.value.length)

function handleImagesChange(event) {
  imagesError.value = null
  const files = Array.from(event.target.files || [])

  const available = maxImages - keptExistingCount.value - imagePreviews.value.length
  if (available <= 0) {
    imagesError.value = `${t('product.images') || 'Images'}: ${t('common.max') || 'Max'} ${maxImages}`
    if (imagesInput.value) imagesInput.value.value = ''
    return
  }

  const toAdd = files.slice(0, available)
  if (toAdd.length < files.length) {
    imagesError.value = `${t('product.images') || 'Images'}: ${t('common.max') || 'Max'} ${maxImages}`
  }

  toAdd.forEach((file) => {
    const url = URL.createObjectURL(file)
    imagePreviews.value.push({ file, url })
  })

  syncImagesToForm(true)
  if (imagesInput.value) imagesInput.value.value = ''
}

function removeImage(idx) {
  const removed = imagePreviews.value.splice(idx, 1)
  if (removed[0]?.url) URL.revokeObjectURL(removed[0].url)
  syncImagesToForm(true)
}

function toggleExistingImage(img) {
  img.markedForDeletion = !img.markedForDeletion
  form.delete_image_ids = existingImages.value.filter(i => i.markedForDeletion).map(i => i.id)
  // clear images error when toggling
  imagesError.value = null
}

function syncImagesToForm(changed = false) {
  form.images = imagePreviews.value.length ? imagePreviews.value.map((p) => p.file) : null
  if (changed) imagesChanged.value = true
}

onBeforeUnmount(() => {
  imagePreviews.value.forEach((p) => p.url && URL.revokeObjectURL(p.url))
})

function categoryName(c) {
  return c?.name?.[locale.value] ?? c?.name_ar ?? c?.name_en ?? c?.name ?? `#${c?.id ?? ''}`
}

function update() {
  // build payload similar to EditUser.vue pattern so multipart + _method works
  const payload = { ...form.data() }

  // avoid sending untouched file fields
  if (!mainImageChanged.value) delete payload.main_image
  if (!imagesChanged.value) delete payload.images

  // send as POST with _method override (form already has _method:'PUT')
  form.transform(() => payload).post(route('company.products.update', props.product.id), {
    onSuccess: () => success(t('product.updatedSuccessfully') || 'Updated'),
    onError: () => error(t('product.updateFailed') || 'Failed'),
    preserveScroll: true,
    forceFormData: true,
  })
}
</script>
