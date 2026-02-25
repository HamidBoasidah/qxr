<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.totalProducts') || 'إجمالي المنتجات' }}
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ summaryData.total_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.activeProducts') || 'المنتجات النشطة' }}
        </div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
          {{ summaryData.active_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.inactiveProducts') || 'المنتجات غير النشطة' }}
        </div>
        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
          {{ summaryData.inactive_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.averagePrice') || 'متوسط السعر' }}
        </div>
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
          {{ summaryData.avg_price?.toLocaleString('en-US', { minimumFractionDigits: 2 }) || '0.00' }}
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ t('reports.filters') }}</h3>
      <form @submit.prevent="applyFilters" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Date Preset -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ t('reports.datePreset') }}
            </label>
            <select
              v-model="form.date_preset"
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="">{{ t('reports.allTime') }}</option>
              <option value="today">{{ t('reports.today') }}</option>
              <option value="yesterday">{{ t('reports.yesterday') }}</option>
              <option value="last_7_days">{{ t('reports.last7Days') }}</option>
              <option value="last_30_days">{{ t('reports.last30Days') }}</option>
              <option value="this_month">{{ t('reports.thisMonth') }}</option>
              <option value="last_month">{{ t('reports.lastMonth') }}</option>
            </select>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ t('reports.status') }}
            </label>
            <select
              v-model="form.status"
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="">{{ t('reports.allStatuses') }}</option>
              <option value="1">{{ t('product.active') || 'نشط' }}</option>
              <option value="0">{{ t('product.inactive') || 'غير نشط' }}</option>
            </select>
          </div>

          <!-- Search -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ t('reports.search') }}
            </label>
            <input
              v-model="form.search"
              type="text"
              :placeholder="t('reports.searchPlaceholder')"
              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>

        <div class="flex gap-2">
          <button
            type="submit"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            {{ t('reports.applyFilters') }}
          </button>
          <button
            type="button"
            @click="clearFilters"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
          >
            {{ t('reports.clearFilters') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Export Buttons & Table -->
    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ t('reports.products') || 'تقرير المنتجات' }}</h3>
        <div class="flex gap-2">
          <button
            @click="exportReport('excel')"
            class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700"
          >
            Excel
          </button>
          <button
            @click="exportReport('pdf')"
            class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700"
          >
            PDF
          </button>
          <button
            @click="exportReport('word')"
            class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
          >
            Word
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th 
                @click="sortBy('name')"
                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <div class="flex items-center justify-end gap-1">
                  {{ t('product.name') || 'الاسم' }}
                  <span v-if="form.sort === 'name'">{{ form.direction === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                {{ t('product.sku') || 'رمز المنتج' }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                {{ t('reports.company') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                {{ t('category.name') || 'الفئة' }}
              </th>
              <th 
                @click="sortBy('base_price')"
                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <div class="flex items-center justify-end gap-1">
                  {{ t('product.base_price') || 'السعر' }}
                  <span v-if="form.sort === 'base_price'">{{ form.direction === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                {{ t('reports.status') }}
              </th>
              <th 
                @click="sortBy('created_at')"
                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <div class="flex items-center justify-end gap-1">
                  {{ t('common.createdAt') || 'تاريخ الإنشاء' }}
                  <span v-if="form.sort === 'created_at'">{{ form.direction === 'asc' ? '↑' : '↓' }}</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="product in productsData.data"
              :key="product.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                {{ product.name }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                {{ product.sku || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                {{ product.company_name || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                {{ product.category || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                {{ product.base_price?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  class="px-2 py-1 text-xs rounded-full"
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': product.is_active,
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200': !product.is_active
                  }"
                >
                  {{ product.is_active ? (t('product.active') || 'نشط') : (t('product.inactive') || 'غير نشط') }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                {{ product.created_at || '—' }}
              </td>
            </tr>
            <tr v-if="!productsData?.data || productsData.data.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                {{ t('reports.noData') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="productsData?.data?.length > 0 && productsData?.links" class="mt-4 flex items-center justify-between">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ t('common.showing') || 'عرض' }} {{ productsData.from || 0 }} - {{ productsData.to || 0 }} {{ t('common.of') || 'من' }} {{ productsData.total?.toLocaleString('en-US') || 0 }}
        </div>
        <div class="flex gap-1">
          <button
            v-for="(link, index) in productsData.links"
            :key="index"
            @click="goToPage(link)"
            :disabled="!link.url || link.active"
            class="px-3 py-1 text-sm rounded border"
            :class="{
              'bg-blue-600 text-white border-blue-600': link.active,
              'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700': !link.active && link.url,
              'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border-gray-200 dark:border-gray-700 cursor-not-allowed': !link.url
            }"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { route } from '@/route'
import { router } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  products: {
    type: Object,
    required: true
  },
  summary: {
    type: Object,
    default: () => ({})
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const productsData = computed(() => props.products || { data: [] })
const summaryData = computed(() => props.summary || {})

// Initialize form with existing filters
const form = reactive({
  date_preset: props.filters?.date_preset || '',
  status: props.filters?.status || '',
  search: props.filters?.search || '',
  sort: props.filters?.sort || 'created_at',
  direction: props.filters?.direction || 'desc'
})

// Watch for filter changes from props
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    form.date_preset = newFilters.date_preset || ''
    form.status = newFilters.status || ''
    form.search = newFilters.search || ''
    form.sort = newFilters.sort || 'created_at'
    form.direction = newFilters.direction || 'desc'
  }
}, { deep: true })

const fetchPage = (url) => {
  if (!url) return
  
  router.get(url, {}, {
    preserveState: true,
    preserveScroll: true
  })
}

const goToPage = (link) => {
  if (!link.url || link.active) return
  fetchPage(link.url)
}

const sortBy = (column) => {
  if (form.sort === column) {
    form.direction = form.direction === 'asc' ? 'desc' : 'asc'
  } else {
    form.sort = column
    form.direction = 'asc'
  }
  applyFilters()
}

const applyFilters = () => {
  router.get(route('company.reports.products'), form, {
    preserveState: true,
    preserveScroll: true
  })
}

const clearFilters = () => {
  form.date_preset = ''
  form.status = ''
  form.search = ''
  form.sort = 'created_at'
  form.direction = 'desc'
  router.get(route('company.reports.products'))
}

const exportReport = (format) => {
  const params = new URLSearchParams({ ...form, format })
  window.location.href = route('company.reports.products.export') + '?' + params.toString()
}
</script>
