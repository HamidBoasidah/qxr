<template>
  <div class="overflow-hidden">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.totalOffers') || 'إجمالي العروض' }}
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ summary?.total_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.activeOffers') || 'العروض النشطة' }}
        </div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
          {{ summary?.active_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.publicOffers') || 'العروض العامة' }}
        </div>
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
          {{ summary?.public_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          {{ t('reports.expiredOffers') || 'العروض المنتهية' }}
        </div>
        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
          {{ summary?.expired_count?.toLocaleString('en-US') || 0 }}
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="flex flex-col gap-2 px-4 py-4 border border-b-0 border-gray-200 rounded-t-xl dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <span class="text-gray-500 dark:text-gray-400">{{ t('datatable.show') }}</span>
        <div class="relative z-20 bg-transparent">
          <select
            v-model="perPage"
            class="w-full py-2 pl-3 pr-8 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg appearance-none dark:bg-dark-900 h-9 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
          >
            <option value="10" class="dark:bg-gray-900">10</option>
            <option value="25" class="dark:bg-gray-900">25</option>
            <option value="50" class="dark:bg-gray-900">50</option>
          </select>
          <span class="absolute z-30 text-gray-500 -translate-y-1/2 pointer-events-none right-2 top-1/2 dark:text-gray-400">
            <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </span>
        </div>
        <span class="text-gray-500 dark:text-gray-400">{{ t('datatable.entries') }}</span>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <!-- Scope -->
        <select
          v-model="scope"
          class="py-2 pl-3 pr-8 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg appearance-none h-11 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
          <option value="">{{ t('reports.allScopes') || 'جميع النطاقات' }}</option>
          <option value="public">{{ t('offer.scope.public') || 'عام' }}</option>
          <option value="private">{{ t('offer.scope.private') || 'خاص' }}</option>
        </select>

        <!-- Status -->
        <select
          v-model="status"
          class="py-2 pl-3 pr-8 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg appearance-none h-11 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
          <option value="">{{ t('reports.allStatuses') }}</option>
          <option value="active">{{ t('status.active') }}</option>
          <option value="inactive">{{ t('status.inactive') }}</option>
          <option value="draft">{{ t('status.draft') }}</option>
          <option value="expired">{{ t('status.expired') }}</option>
        </select>

        <!-- Search -->
        <div class="relative">
          <button class="absolute text-gray-500 -translate-y-1/2 left-4 top-1/2 dark:text-gray-400">
            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
            </svg>
          </button>
          <input
            v-model="search"
            type="text"
            :placeholder="t('datatable.searchPlaceholder')"
            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 xl:w-[300px]"
          />
        </div>

        <!-- Export Buttons -->
        <div class="flex gap-2">
          <button
            @click="exportReport('excel')"
            class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 shadow-theme-xs"
          >
            Excel
          </button>
          <button
            @click="exportReport('pdf')"
            class="px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 shadow-theme-xs"
          >
            PDF
          </button>
          <button
            @click="exportReport('word')"
            class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 shadow-theme-xs"
          >
            Word
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="max-w-full overflow-x-auto">
      <table class="w-full min-w-full">
        <thead>
          <tr>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <div class="flex items-center justify-between w-full cursor-pointer" @click="sortBy('title')">
                <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offer.title') || 'العنوان' }}</p>
                <span class="flex flex-col gap-0.5">
                  <svg :class="sortColumn === 'title' && sortDirection === 'asc' ? 'fill-brand-500' : 'fill-gray-300 dark:fill-gray-700'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="" />
                  </svg>
                  <svg :class="sortColumn === 'title' && sortDirection === 'desc' ? 'fill-brand-500' : 'fill-gray-300 dark:fill-gray-700'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="" />
                  </svg>
                </span>
              </div>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('reports.company') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('dashboard.scope') || 'النطاق' }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('reports.status') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('reports.itemsCount') || 'العناصر' }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('reports.targetsCount') || 'الأهداف' }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <div class="flex items-center justify-between w-full cursor-pointer" @click="sortBy('start_at')">
                <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offer.start_at') || 'تاريخ البدء' }}</p>
                <span class="flex flex-col gap-0.5">
                  <svg :class="sortColumn === 'start_at' && sortDirection === 'asc' ? 'fill-brand-500' : 'fill-gray-300 dark:fill-gray-700'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="" />
                  </svg>
                  <svg :class="sortColumn === 'start_at' && sortDirection === 'desc' ? 'fill-brand-500' : 'fill-gray-300 dark:fill-gray-700'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="" />
                  </svg>
                </span>
              </div>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <div class="flex items-center justify-between w-full cursor-pointer" @click="sortBy('end_at')">
                <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offer.end_at') || 'تاريخ الانتهاء' }}</p>
                <span class="flex flex-col gap-0.5">
                  <svg :class="sortColumn === 'end_at' && sortDirection === 'asc' ? 'fill-brand-500' : 'fill-gray-300 dark:fill-gray-700'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="" />
                  </svg>
                  <svg :class="sortColumn === 'end_at' && sortDirection === 'desc' ? 'fill-brand-500' : 'fill-gray-300 dark:fill-gray-700'" width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="" />
                  </svg>
                </span>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="offer in paginatedData"
            :key="offer.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-800/50"
          >
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="text-sm text-gray-800 dark:text-white/90">{{ offer.title }}</span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="text-sm text-gray-800 dark:text-white/90">{{ offer.company_name || '—' }}</span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span
                class="inline-flex rounded-full px-2 py-0.5 text-theme-xs font-medium"
                :class="{
                  'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-500': offer.scope === 'public',
                  'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-500': offer.scope === 'private'
                }"
              >
                {{ t(`offer.scope.${offer.scope}`) || offer.scope }}
              </span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span
                class="inline-flex rounded-full px-2 py-0.5 text-theme-xs font-medium"
                :class="getStatusClass(offer.status)"
              >
                {{ t(`status.${offer.status}`) || offer.status }}
              </span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="text-sm text-gray-800 dark:text-white/90">{{ offer.items_count?.toLocaleString('en-US') || 0 }}</span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="text-sm text-gray-800 dark:text-white/90">{{ offer.targets_count?.toLocaleString('en-US') || 0 }}</span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ offer.start_at || '—' }}</span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ offer.end_at || '—' }}</span>
            </td>
          </tr>
          <tr v-if="!paginatedData || paginatedData.length === 0">
            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-800">
              {{ t('reports.noData') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col gap-4 px-6 py-4 border border-t-0 border-gray-200 rounded-b-xl sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ t('datatable.showing') }} {{ startEntry }} {{ t('datatable.to') }} {{ endEntry }} {{ t('datatable.of') }} {{ totalEntries }} {{ t('datatable.entries') }}
      </div>
      <div class="flex items-center">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="mr-2.5 flex items-center h-10 justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
        >
          {{ t('datatable.previous') }}
        </button>
        <button
          @click="goToPage(1)"
          :class="currentPage === 1 ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'"
          class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500"
        >
          1
        </button>
        <span v-if="currentPage > 3" class="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500">...</span>
        <button
          v-for="page in pagesAroundCurrent"
          :key="page"
          @click="goToPage(page)"
          :class="currentPage === page ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'"
          class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500"
        >
          {{ page }}
        </button>
        <span v-if="currentPage < totalPages - 2" class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-500/[0.08] hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500">...</span>
        <button
          v-if="totalPages > 1"
          @click="goToPage(totalPages)"
          :class="currentPage === totalPages ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'"
          class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500"
        >
          {{ totalPages }}
        </button>
        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="ml-2.5 flex items-center h-10 justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
        >
          {{ t('datatable.next') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/route'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  offers: Object,
  summary: Object,
  filters: Object
})

// Filter state
const search = ref(props.filters?.search || '')
const scope = ref(props.filters?.scope || '')
const status = ref(props.filters?.status || '')
const sortColumn = ref(props.filters?.sort || 'title')
const sortDirection = ref(props.filters?.direction || 'desc')

// Pagination state
const currentPage = ref(props.offers?.current_page ?? 1)
const perPage = ref(props.offers?.per_page ?? 10)

const paginatedData = computed(() => props.offers?.data || [])
const totalEntries = computed(() => props.offers?.total || 0)
const startEntry = computed(() => props.offers?.from || 0)
const endEntry = computed(() => props.offers?.to || 0)
const totalPages = computed(() => props.offers?.last_page || 1)

const pagesAroundCurrent = computed(() => {
  let pages = []
  const startPage = Math.max(2, currentPage.value - 2)
  const endPage = Math.min(totalPages.value - 1, currentPage.value + 2)
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }
  return pages
})

const getStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500',
    'inactive': 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
    'draft': 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
    'expired': 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-500',
  }
  return classes[status] || 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400'
}

// Sync with server updates
watch(() => props.offers?.current_page, (val) => {
  currentPage.value = typeof val === 'number' ? val : 1
})
watch(() => props.offers?.per_page, (val) => {
  if (typeof val === 'number') perPage.value = val
})

const fetchPage = (page) => {
  const targetPage = page ?? currentPage.value
  router.get(
    route('company.reports.offers'),
    {
      page: targetPage,
      per_page: perPage.value,
      search: search.value || undefined,
      scope: scope.value || undefined,
      status: status.value || undefined,
      sort: sortColumn.value,
      direction: sortDirection.value,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  )
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    fetchPage(page)
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    fetchPage(currentPage.value + 1)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    fetchPage(currentPage.value - 1)
  }
}

const sortBy = (column) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortDirection.value = 'asc'
    sortColumn.value = column
  }
  fetchPage(1)
}

const exportReport = (format) => {
  const params = new URLSearchParams({
    format,
    search: search.value || '',
    scope: scope.value || '',
    status: status.value || '',
    sort: sortColumn.value,
    direction: sortDirection.value,
  })
  window.location.href = route('company.reports.offers.export') + '?' + params.toString()
}

// Watchers for filter changes
watch([search, scope, status], () => {
  fetchPage(1)
}, { debounce: 300 })

watch(perPage, (val, oldVal) => {
  if (val !== oldVal) fetchPage(1)
})
</script>
