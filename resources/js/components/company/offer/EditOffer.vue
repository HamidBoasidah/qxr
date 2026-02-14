<template>
  <div class="space-y-6">
    <!-- Offer Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('offers.offerInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <!-- Title -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('offers.title') }} <span class="text-error-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              :placeholder="t('offers.titlePlaceholder')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-error-500">{{ form.errors.title }}</p>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('offers.description') }}
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              :placeholder="t('offers.descriptionPlaceholder')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            ></textarea>
            <p v-if="form.errors.description" class="mt-1 text-sm text-error-500">{{ form.errors.description }}</p>
          </div>

          <!-- Scope -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('offers.scope') }} <span class="text-error-500">*</span>
            </label>
            <select
              v-model="form.scope"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
              <option value="public">{{ t('offers.scope_public') }}</option>
              <option value="private">{{ t('offers.scope_private') }}</option>
            </select>
            <p v-if="form.errors.scope" class="mt-1 text-sm text-error-500">{{ form.errors.scope }}</p>
          </div>

          <!-- Status -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('offers.status') }} <span class="text-error-500">*</span>
            </label>
            <select
              v-model="form.status"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
              <option value="draft">{{ t('offers.status_draft') }}</option>
              <option value="active">{{ t('offers.status_active') }}</option>
              <option value="paused">{{ t('offers.status_paused') }}</option>
              <option value="expired">{{ t('offers.status_expired') }}</option>
            </select>
            <p v-if="form.errors.status" class="mt-1 text-sm text-error-500">{{ form.errors.status }}</p>
          </div>

          <!-- Start Date -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('offers.startDate') }}
            </label>
            <input
              v-model="form.start_at"
              type="date"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.start_at" class="mt-1 text-sm text-error-500">{{ form.errors.start_at }}</p>
          </div>

          <!-- End Date -->
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('offers.endDate') }}
            </label>
            <input
              v-model="form.end_at"
              type="date"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p v-if="form.errors.end_at" class="mt-1 text-sm text-error-500">{{ form.errors.end_at }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Offer Items Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('offers.offerItems') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div v-for="(item, index) in form.items" :key="index" class="mb-4 p-4 border border-gray-200 rounded-lg dark:border-gray-700">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.product') }}
              </label>
              <select
                v-model="item.product_id"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option :value="null">{{ t('offers.selectProduct') }}</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.minQty') }}
              </label>
              <input
                v-model.number="item.min_qty"
                type="number"
                min="1"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.rewardType') }}
              </label>
              <select
                v-model="item.reward_type"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option value="discount_percent">{{ t('offers.reward_discount_percent') }}</option>
                <option value="discount_fixed">{{ t('offers.reward_discount_fixed') }}</option>
                <option value="bonus_qty">{{ t('offers.reward_bonus_qty') }}</option>
              </select>
            </div>

            <div v-if="item.reward_type === 'discount_percent'">
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.discountPercent') }}
              </label>
              <input
                v-model.number="item.discount_percent"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              />
            </div>

            <div v-if="item.reward_type === 'discount_fixed'">
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.discountFixed') }}
              </label>
              <input
                v-model.number="item.discount_fixed"
                type="number"
                min="0"
                step="0.01"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              />
            </div>

            <div v-if="item.reward_type === 'bonus_qty'" class="md:col-span-2">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ t('offers.bonusQty') }}
                  </label>
                  <input
                    v-model.number="item.bonus_qty"
                    type="number"
                    min="1"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                  />
                </div>
                <div>
                  <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ t('offers.bonusProduct') }}
                  </label>
                  <select
                    v-model="item.bonus_product_id"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                  >
                    <option :value="null">{{ t('offers.selectProduct') }}</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <div class="flex items-end">
              <button
                @click="removeItem(index)"
                type="button"
                class="text-error-500 hover:text-error-600"
              >
                {{ t('common.remove') }}
              </button>
            </div>
          </div>
        </div>

        <button
          @click="addItem"
          type="button"
          class="mt-4 inline-flex items-center gap-2 text-sm text-brand-500 hover:text-brand-600"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M5 10H15M10 5V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          {{ t('offers.addItem') }}
        </button>
      </div>
    </div>

    <!-- Offer Targets Section (only for private offers) -->
    <div v-if="form.scope === 'private'" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('offers.offerTargets') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div v-for="(target, index) in form.targets" :key="index" class="mb-4 p-4 border border-gray-200 rounded-lg dark:border-gray-700">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.targetType') }}
              </label>
              <select
                v-model="target.target_type"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option value="customer">{{ t('offers.target_customer') }}</option>
                <option value="customer_category">{{ t('offers.target_customer_category') }}</option>
                <option value="customer_tag">{{ t('offers.target_customer_tag') }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                {{ t('offers.target') }}
              </label>
              <select
                v-model="target.target_id"
                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              >
                <option :value="null">{{ t('offers.selectTarget') }}</option>
                <option v-if="target.target_type === 'customer'" v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }}
                </option>
                <option v-if="target.target_type === 'customer_category'" v-for="category in customerCategories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
                <option v-if="target.target_type === 'customer_tag'" v-for="tag in customerTags" :key="tag.id" :value="tag.id">
                  {{ tag.name }}
                </option>
              </select>
            </div>

            <div class="flex items-end">
              <button
                @click="removeTarget(index)"
                type="button"
                class="text-error-500 hover:text-error-600"
              >
                {{ t('common.remove') }}
              </button>
            </div>
          </div>
        </div>

        <button
          @click="addTarget"
          type="button"
          class="mt-4 inline-flex items-center gap-2 text-sm text-brand-500 hover:text-brand-600"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M5 10H15M10 5V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          {{ t('offers.addTarget') }}
        </button>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('company.offers.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>

      <button
        @click="update"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
        :disabled="form.processing"
      >
        {{ form.processing ? t('common.loading') : t('buttons.update') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'

const { t } = useI18n()
const { success, error } = useNotifications()

const props = defineProps({
  offer: Object,
  products: Array,
  customerCategories: Array,
  customerTags: Array,
  customers: Array,
})

// Helper function to convert datetime to date format for input
function formatDateForInput(datetime) {
  if (!datetime) return null
  return datetime.split(' ')[0] // Extract YYYY-MM-DD from "YYYY-MM-DD HH:MM:SS"
}

const form = useForm({
  _method: 'PUT',
  title: props.offer?.title || '',
  description: props.offer?.description || '',
  scope: props.offer?.scope || 'public',
  status: props.offer?.status || 'draft',
  start_at: formatDateForInput(props.offer?.start_at),
  end_at: formatDateForInput(props.offer?.end_at),
  items: props.offer?.items || [],
  targets: props.offer?.targets || [],
})

function addItem() {
  form.items.push({
    product_id: null,
    min_qty: 1,
    reward_type: 'discount_percent',
    discount_percent: null,
    discount_fixed: null,
    bonus_qty: null,
    bonus_product_id: null,
  })
}

function removeItem(index) {
  form.items.splice(index, 1)
}

function addTarget() {
  form.targets.push({
    target_type: 'customer',
    target_id: null,
  })
}

function removeTarget(index) {
  form.targets.splice(index, 1)
}

function update() {
  form.post(route('company.offers.update', props.offer.id), {
    onSuccess: () => success(t('offers.offerUpdatedSuccessfully')),
    onError: () => error(t('offers.offerUpdateFailed')),
    preserveScroll: true,
  })
}
</script>
