<template>
  <div class="overflow-hidden">
    <div
      class="flex flex-col gap-2 px-4 py-4 border border-b-0 border-gray-200 rounded-b-none rounded-xl dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-center gap-3">
        <span class="text-gray-500 dark:text-gray-400">{{ t('datatable.show') }}</span>
        <div class="relative z-20 bg-transparent">
          <select
            v-model="perPage"
            class="w-full py-2 pl-3 pr-8 text-sm text-gray-800 bg-transparent border border-gray-300 rounded-lg appearance-none dark:bg-dark-900 h-9 bg-none shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
          >
            <option value="10">10</option>
            <option value="8">8</option>
            <option value="5">5</option>
          </select>
          <span class="absolute z-30 text-gray-500 -translate-y-1/2 pointer-events-none right-2 top-1/2 dark:text-gray-400">
            <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </span>
        </div>
        <span class="text-gray-500 dark:text-gray-400">{{ t('datatable.entries') }}</span>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative">
          <button class="absolute text-gray-500 -translate-y-1/2 left-4 top-1/2 dark:text-gray-400">
            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
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

        <!-- Create offer button removed per request -->
      </div>
    </div>

    <div class="max-w-full overflow-x-auto">
      <table class="w-full min-w-full">
        <thead>
          <tr>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offers.title') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offers.scope') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offers.status') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offers.items') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offers.targets') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('offers.period') }}</p>
            </th>
            <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
              <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('common.action') }}</p>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="offer in paginatedData" :key="offer.id">
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-400">{{ offer.title }}</p>
              <p v-if="offer.description" class="text-xs text-gray-500 dark:text-gray-500">{{ truncate(offer.description, 50) }}</p>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span
                class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': offer.scope === 'public',
                  'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-500': offer.scope === 'private',
                }"
              >
                {{ t(`offers.scope_${offer.scope}`) }}
              </span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span
                class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': offer.status === 'active',
                  'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-500': offer.status === 'draft',
                  'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-500': offer.status === 'inactive',
                  'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': offer.status === 'expired',
                }"
              >
                {{ t(`offers.status_${offer.status}`) }}
              </span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="inline-flex items-center justify-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500">
                {{ offer.items_count || 0 }}
              </span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <span class="inline-flex items-center justify-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-500">
                {{ offer.targets_count || 0 }}
              </span>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <p class="text-xs text-gray-700 dark:text-gray-400">
                <span v-if="offer.start_at">{{ formatDate(offer.start_at) }}</span>
                <span v-if="offer.start_at && offer.end_at"> - </span>
                <span v-if="offer.end_at">{{ formatDate(offer.end_at) }}</span>
                <span v-if="!offer.start_at && !offer.end_at" class="text-gray-500">{{ t('offers.noLimit') }}</span>
              </p>
            </td>
            <td class="px-4 py-3 border border-gray-100 dark:border-gray-800">
              <div class="flex items-center w-full gap-2">
                <button
                  @click="handleViewClick(offer.id)"
                  class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90"
                >
                  <svg class="fill-current" width="21" height="20" viewBox="0 0 21 20" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.8749 13.8619C8.10837 13.8619 5.74279 12.1372 4.79804 9.70241C5.74279 7.26761 8.10837 5.54297 10.8749 5.54297C13.6415 5.54297 16.0071 7.26762 16.9518 9.70243C16.0071 12.1372 13.6415 13.8619 10.8749 13.8619ZM10.8749 4.04297C7.35666 4.04297 4.36964 6.30917 3.29025 9.4593C3.23626 9.61687 3.23626 9.78794 3.29025 9.94552C4.36964 13.0957 7.35666 15.3619 10.8749 15.3619C14.3932 15.3619 17.3802 13.0957 18.4596 9.94555C18.5136 9.78797 18.5136 9.6169 18.4596 9.45932C17.3802 6.30919 14.3932 4.04297 10.8749 4.04297ZM10.8663 7.84413C9.84002 7.84413 9.00808 8.67606 9.00808 9.70231C9.00808 10.7286 9.84002 11.5605 10.8663 11.5605H10.8811C11.9074 11.5605 12.7393 10.7286 12.7393 9.70231C12.7393 8.67606 11.9074 7.84413 10.8811 7.84413H10.8663Z" fill="" />
                  </svg>
                </button>
                <!-- Edit and Delete buttons removed per request -->
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Controls -->
    <div class="border border-t-0 rounded-b-xl border-gray-100 py-4 pl-[18px] pr-4 dark:border-gray-800">
      <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between">
        <p class="pb-3 text-sm font-medium text-center text-gray-500 border-b border-gray-100 dark:border-gray-800 dark:text-gray-400 xl:border-b-0 xl:pb-0 xl:text-left">
          {{ t('datatable.showing', { start: startEntry, end: endEntry, total: totalEntries }) }}
        </p>
        <div class="flex items-center justify-center gap-0.5 pt-3 xl:justify-end xl:pt-0">
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
          <span v-if="currentPage > 3" class="flex h-10 w-10 items-center justify-center">...</span>
          <button
            v-for="page in pagesAroundCurrent"
            :key="page"
            @click="goToPage(page)"
            :class="currentPage === page ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'"
            class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500"
          >
            {{ page }}
          </button>
          <span v-if="currentPage < totalPages - 2" class="flex h-10 w-10 items-center justify-center">...</span>
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
  </div>

  <!-- Delete confirmation removed (edit/delete buttons removed) -->
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/route'
import { useI18n } from 'vue-i18n'
// DangerAlert and notifications removed since edit/delete were removed

const { t } = useI18n()

const props = defineProps({ offers: Object })

const search = ref('')
const sortColumn = ref('title')
const sortDirection = ref('asc')
const currentPage = ref(props.offers?.current_page ?? 1)
const perPage = ref(props.offers?.per_page ?? 10)

const isDeleteModalOpen = ref(false) // kept for backward compatibility if needed elsewhere

function handleViewClick(id) {
  router.visit(route('admin.offers.show', id))
}

const filteredData = computed(() => {
  const searchLower = search.value.toLowerCase()
  return (props.offers?.data || [])
    .filter(offer =>
      offer.title?.toLowerCase().includes(searchLower) ||
      offer.description?.toLowerCase().includes(searchLower)
    )
    .sort((a, b) => {
      let modifier = sortDirection.value === 'asc' ? 1 : -1
      if (a[sortColumn.value] < b[sortColumn.value]) return -1 * modifier
      if (a[sortColumn.value] > b[sortColumn.value]) return 1 * modifier
      return 0
    })
})

const paginatedData = computed(() => filteredData.value)
const totalEntries = computed(() => props.offers?.total || filteredData.value.length)
const startEntry = computed(() => props.offers?.from || 1)
const endEntry = computed(() => props.offers?.to || filteredData.value.length)
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

watch(() => props.offers?.current_page, (val) => {
  currentPage.value = typeof val === 'number' ? val : 1
})

watch(() => props.offers?.per_page, (val) => {
  if (typeof val === 'number') perPage.value = val
})

const fetchPage = (page) => {
  const targetPage = page ?? currentPage.value
  router.get(
    window.location.pathname,
    { page: targetPage, per_page: perPage.value, search: search.value || undefined },
    { preserveState: true, preserveScroll: true, replace: true }
  )
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) fetchPage(page)
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) fetchPage(currentPage.value + 1)
}

const prevPage = () => {
  if (currentPage.value > 1) fetchPage(currentPage.value - 1)
}

watch(perPage, (val, oldVal) => {
  if (val !== oldVal) fetchPage(1)
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

function truncate(text, length) {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}
</script>
