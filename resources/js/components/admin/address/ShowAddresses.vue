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
            :class="{ 'text-gray-500 dark:text-gray-400': perPage !== '' }"
          >
            <option value="9" class="text-gray-500 dark:bg-gray-900 dark:text-gray-400">9</option>
            <option value="6"  class="text-gray-500 dark:bg-gray-900 dark:text-gray-400">6</option>
            <option value="3"  class="text-gray-500 dark:bg-gray-900 dark:text-gray-400">3</option>
          </select>
          <span
            class="absolute z-30 text-gray-500 -translate-y-1/2 pointer-events-none right-2 top-1/2 dark:text-gray-400"
          >
            <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </div>
        <span class="text-gray-500 dark:text-gray-400">{{ t('datatable.entries') }}</span>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative">
          <button class="absolute text-gray-500 -translate-y-1/2 left-4 top-1/2 dark:text-gray-400">
            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill=""/>
            </svg>
          </button>
          <input
            v-model="search"
            type="text"
            :placeholder="t('datatable.searchPlaceholder')"
            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 xl:w-[300px]"
          />
        </div>

        <!-- Add button removed (admin read-only) -->
      </div>
    </div>

    <div class="max-w-full">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
        <div
          v-for="address in paginatedData"
          :key="address.id"
          :class="{ 'opacity-60': address.selected }
          "
          class="border border-gray-100 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-900 p-4 shadow-theme-sm"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <div class="flex items-start gap-3">
                <label class="relative">
                  <input type="checkbox" class="sr-only" v-model="address.selected" @change="updateSelectAll" />
                  <span :class="address.selected ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'" class="flex h-4 w-4 items-center justify-center rounded-sm border-[1.25px]">
                    <span :class="address.selected ? '' : 'opacity-0'">
                      <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M10 3L4.5 8.5L2 6" stroke="white" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                  </span>
                </label>

                <div>
                  <p class="text-gray-700 dark:text-gray-200 font-medium">{{ address.label }}</p>
                  <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ address.address }}</p>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <div>
                <span
                  class="inline-flex items-center justify-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="{
                    'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': address.is_active,
                    'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': !address.is_active,
                  }"
                >
                  {{ address.is_active ? t('common.active') : t('common.inactive') }}
                </span>
              </div>
            </div>
          </div>

          <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex flex-col gap-1">
              <div><strong class="text-gray-700 dark:text-gray-200">{{ t('governorates.governorate') }}:</strong> <span class="ml-1">{{ locale === 'ar' ? (address.governorate?.name_ar ?? address.governorate_name_ar ?? '—') : (address.governorate?.name_en ?? address.governorate_name_en ?? '—') }}</span></div>
              <div><strong class="text-gray-700 dark:text-gray-200">{{ t('districts.district') }}:</strong> <span class="ml-1">{{ locale === 'ar' ? (address.district?.name_ar ?? address.district_name_ar ?? '—') : (address.district?.name_en ?? address.district_name_en ?? '—') }}</span></div>
              <div><strong class="text-gray-700 dark:text-gray-200">{{ t('areas.area') }}:</strong> <span class="ml-1">{{ locale === 'ar' ? (address.area?.name_ar ?? address.area_name_ar ?? '—') : (address.area?.name_en ?? address.area_name_en ?? '—') }}</span></div>
            </div>
          </div>

          <div class="mt-4 flex items-center justify-between">
            <!-- Activation toggle removed (admin read-only) -->

            <div class="flex items-center gap-2">
              <button :disabled="!canView" @click="handleViewClick(address.id)" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90 disabled:text-gray-400 disabled:dark:text-gray-500">
                <svg class="fill-current" width="21" height="20" viewBox="0 0 21 20" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.8749 13.8619C8.10837 13.8619 5.74279 12.1372 4.79804 9.70241C5.74279 7.26761 8.10837 5.54297 10.8749 5.54297C13.6415 5.54297 16.0071 7.26762 16.9518 9.70243C16.0071 12.1372 13.6415 13.8619 10.8749 13.8619ZM10.8749 4.04297C7.35666 4.04297 4.36964 6.30917 3.29025 9.4593C3.23626 9.61687 3.23626 9.78794 3.29025 9.94552C4.36964 13.0957 7.35666 15.3619 10.8749 15.3619C14.3932 15.3619 17.3802 13.0957 18.4596 9.94555C18.5136 9.78797 18.5136 9.6169 18.4596 9.45932C17.3802 6.30919 14.3932 4.04297 10.8749 4.04297ZM10.8663 7.84413C9.84002 7.84413 9.00808 8.67606 9.00808 9.70231C9.00808 10.7286 9.84002 11.5605 10.8663 11.5605H10.8811C11.9074 11.5605 12.7393 10.7286 12.7393 9.70231C12.7393 8.67606 11.9074 7.84413 10.8811 7.84413H10.8663Z" />
                  </svg>
              </button>

                  <!-- Edit and Delete buttons removed (admin read-only) -->
            </div>
          </div>
        </div>

        <div v-if="paginatedData.length === 0" class="col-span-1 sm:col-span-2 lg:col-span-3 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
          {{ t('addresses.noAddress') }}
        </div>
      </div>
    </div>

    <div class="border border-t-0 rounded-b-xl border-gray-100 py-4 pl-[18px] pr-4 dark:border-gray-800">
      <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between">
        <p class="pb-3 text-sm font-medium text-center text-gray-500 border-b border-gray-100 dark:border-gray-800 dark:text-gray-400 xl:border-b-0 xl:pb-0 xl:text-left">
          {{ t('datatable.showing', { start: startEntry, end: endEntry, total: totalEntries }) }}
        </p>
        <div class="flex items-center justify-center gap-0.5 pt-3 xl:justify-end xl:pt-0">
          <button @click="prevPage" :disabled="currentPage === 1" class="mr-2.5 flex items-center h-10 justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
            {{ t('datatable.previous') }}
          </button>
          <button @click="goToPage(1)" :class="currentPage === 1 ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'" class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500">1</button>
          <span v-if="currentPage > 3" class="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500">...</span>
          <button v-for="page in pagesAroundCurrent" :key="page" @click="goToPage(page)" :class="currentPage === page ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'" class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500">
            {{ page }}
          </button>
          <span v-if="currentPage < totalPages - 2" class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-500/[0.08] hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500">...</span>
          <button v-if="totalPages > 1" @click="goToPage(totalPages)" :class="currentPage === totalPages ? 'bg-blue-500/[0.08] text-brand-500' : 'text-gray-700 dark:text-gray-400'" class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/[0.08] hover:text-brand-500 dark:hover:text-brand-500">
            {{ totalPages }}
          </button>
          <button @click="nextPage" :disabled="currentPage === totalPages" class="ml-2.5 flex items-center h-10 justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
            {{ t('datatable.next') }}
          </button>
        </div>
      </div>
    </div>

    <DangerAlert
      :isOpen="isDeleteModalOpen"
      :title="t('messages.areYouSure')"
      :message="t('messages.deleteAddressConfirmation')"
      @close="closeDeleteModal"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from '@/route'
import { useI18n } from 'vue-i18n'
import DangerAlert from '@/Components/modals/DangerAlert.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useNotifications } from '@/composables/useNotifications'

const { hasAnyPermission } = usePermissions()

const canCreate = computed(() => hasAnyPermission(['addresses.create', 'addresses.store', 'addresses.add']))
const canView   = computed(() => hasAnyPermission(['addresses.view', 'addresses.show', 'addresses.read']))
const canEdit   = computed(() => hasAnyPermission(['addresses.update', 'addresses.edit']))
const canDelete = computed(() => hasAnyPermission(['addresses.delete', 'addresses.destroy']))

const { t, locale } = useI18n()
const { success, error } = useNotifications()

const props = defineProps({ addresses: Object })

const search         = ref('')
const sortColumn     = ref('label')
const sortDirection  = ref('asc')
const currentPage    = ref(props.addresses?.current_page ?? 1)
const perPage        = ref(props.addresses?.per_page ?? 10)
const selectAll      = ref(false)

function goToCreate()      { router.visit(route('admin.addresses.create')) }
function goToView(id)      { router.visit(route('admin.addresses.show', id)) }
function goToEdit(id)      { router.visit(route('admin.addresses.edit', id)) }

function handleCreateClick(){ if (!canCreate.value) return; goToCreate() }
function handleViewClick(id){ if (!canView.value)   return; goToView(id) }
function handleEditClick(id){ if (!canEdit.value)   return; goToEdit(id) }

const isDeleteModalOpen  = ref(false)
const addressToDeleteId = ref(null)

function openDeleteModal(id)  { addressToDeleteId.value = id; isDeleteModalOpen.value = true }
function handleDeleteClick(id){ if (!canDelete.value) return; openDeleteModal(id) }
function closeDeleteModal()   { isDeleteModalOpen.value = false; addressToDeleteId.value = null }

function confirmDelete() {
  if (addressToDeleteId.value) {
    router.delete(route('admin.addresses.destroy', addressToDeleteId.value), {
      onSuccess: () => { success(t('addresses.addressDeletedSuccessfully')); closeDeleteModal() },
      onError:   () => { error(t('addresses.addressDeletionFailed'));       closeDeleteModal() },
      preserveScroll: true,
    })
  }
}

// toggleAddressStatus removed for admin read-only UI

const filteredData = computed(() => {
  const searchLower = (search.value || '').toLowerCase()
  return (props.addresses?.data || [])
    .filter((d) => {
      const label = d.label?.toLowerCase() || ''
      const addr  = d.address?.toLowerCase() || ''
      const govAr  = (d.governorate?.name_ar || d.governorate_name_ar || '').toLowerCase()
      const govEn  = (d.governorate?.name_en || d.governorate_name_en || '').toLowerCase()
      const distAr = (d.district?.name_ar || d.district_name_ar || '').toLowerCase()
      const distEn = (d.district?.name_en || d.district_name_en || '').toLowerCase()
      const areaAr = (d.area?.name_ar || d.area_name_ar || '').toLowerCase()
      const areaEn = (d.area?.name_en || d.area_name_en || '').toLowerCase()
      return label.includes(searchLower) || addr.includes(searchLower) || govAr.includes(searchLower) || govEn.includes(searchLower) || distAr.includes(searchLower) || distEn.includes(searchLower) || areaAr.includes(searchLower) || areaEn.includes(searchLower)
    })
    .sort((a, b) => {
      const modifier = sortDirection.value === 'asc' ? 1 : -1
      const valueA = a?.[sortColumn.value] ?? ''
      const valueB = b?.[sortColumn.value] ?? ''
      if (valueA < valueB) return -1 * modifier
      if (valueA > valueB) return  1 * modifier
      return 0
    })
})

const paginatedData = computed(() => filteredData.value)

const totalEntries = computed(() => props.addresses?.total || filteredData.value.length)
const startEntry   = computed(() => props.addresses?.from  || 1)
const endEntry     = computed(() => props.addresses?.to    || filteredData.value.length)
const totalPages   = computed(() => props.addresses?.last_page || 1)

const pagesAroundCurrent = computed(() => {
  const pages = []
  const startPage = Math.max(2, currentPage.value - 2)
  const endPage   = Math.min(totalPages.value - 1, currentPage.value + 2)
  for (let i = startPage; i <= endPage; i += 1) pages.push(i)
  return pages
})

watch(() => props.addresses?.current_page, (val) => {
  currentPage.value = typeof val === 'number' ? val : 1
})
watch(() => props.addresses?.per_page, (val) => {
  if (typeof val === 'number') perPage.value = val
})

const fetchPage = (page) => {
  const targetPage = page ?? currentPage.value
  router.get(
    window.location.pathname,
    {
      page: targetPage,
      per_page: perPage.value,
      search: search.value || undefined,
      sort: sortColumn.value,
      direction: sortDirection.value,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  )
}

const goToPage = (page) => { if (page >= 1 && page <= totalPages.value) fetchPage(page) }
const nextPage = () => { if (currentPage.value < totalPages.value) fetchPage(currentPage.value + 1) }
const prevPage = () => { if (currentPage.value > 1) fetchPage(currentPage.value - 1) }

const sortBy = (column) => {
  if (sortColumn.value === column) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  else { sortDirection.value = 'asc'; sortColumn.value = column }
}

const toggleSelectAll = () => { filteredData.value.forEach((d) => { d.selected = selectAll.value }) }
const updateSelectAll = () => {
  const items = filteredData.value
  selectAll.value = items.length > 0 && items.every((d) => d.selected)
}

watch(perPage, (val, oldVal) => { if (val !== oldVal) fetchPage(1) })
</script>
