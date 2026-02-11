<template>
  <form class="space-y-6" @submit.prevent="create">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('addresses.addressInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6 dark:border-gray-800">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
          <div>
            <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('addresses.label') }}
            </label>
            <input
              v-model="form.label"
              type="text"
              id="label"
              autocomplete="off"
              :placeholder="t('addresses.label')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
            <p v-if="form.errors.label" class="mt-1 text-sm text-error-500">{{ form.errors.label }}</p>
            
          </div>


          <div class="md:col-span-2">
            <label for="address" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('addresses.address') }}
            </label>
            <textarea
              v-model="form.address"
              id="address"
              :placeholder="t('addresses.address')"
              class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-28 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
            <p v-if="form.errors.address" class="mt-1 text-sm text-error-500">{{ form.errors.address }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('districts.selectGovernorate') }}
            </label>
            <div class="relative z-20 bg-transparent">
              <select
                v-model="form.governorate_id"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                :class="{ 'text-gray-800 dark:text-white/90': form.governorate_id }"
              >
                <option value="" disabled class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                  {{ t('districts.selectGovernorate') }}
                </option>
                <option
                  v-for="g in governorates"
                  :key="g.id"
                  :value="g.id"
                  class="text-gray-700 dark:bg-gray-900 dark:text-gray-400"
                >
                  {{ locale === 'ar' ? g.name_ar : g.name_en }}
                </option>
              </select>
              <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </div>
            <p v-if="form.errors.governorate_id" class="mt-1 text-sm text-error-500">{{ form.errors.governorate_id }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              {{ t('districts.selectDistrict') }}
            </label>
            <div class="relative z-20 bg-transparent">
              <select
                v-model="form.district_id"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                :class="{ 'text-gray-800 dark:text-white/90': form.district_id }"
              >
                <option value="" disabled class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                  {{ t('districts.selectDistrict') }}
                </option>
                <option
                  v-for="d in availableDistricts"
                  :key="d.id"
                  :value="d.id"
                  class="text-gray-700 dark:bg-gray-900 dark:text-gray-400"
                >
                  {{ locale === 'ar' ? d.name_ar : d.name_en }}
                </option>
              </select>
              <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </div>
            <p v-if="form.errors.district_id" class="mt-1 text-sm text-error-500">{{ form.errors.district_id }}</p>
          </div>

          <div>
            <label for="area_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ t('areas.area') }}</label>
            <div class="relative z-20 bg-transparent">
              <select
                v-model="form.area_id"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
              >
                <option value="">{{ t('areas.selectArea') }}</option>
                <option v-for="a in filteredAreas" :key="a.id" :value="a.id">{{ locale === 'ar' ? a.name_ar : a.name_en }}</option>
              </select>
              <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </div>
            <p v-if="form.errors.area_id" class="mt-1 text-sm text-error-500">{{ form.errors.area_id }}</p>
          </div>




        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('common.status') }}</h2>
      </div>
      <div class="p-4 sm:p-6">
          <div class="flex items-center gap-6">
            <label for="toggle-active" class="flex cursor-pointer select-none items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-400">
              <div class="relative">
                <input type="checkbox" id="toggle-active" class="sr-only" v-model="form.is_active" />
                <div class="block h-6 w-11 rounded-full" :class="form.is_active ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'"></div>
                <div :class="form.is_active ? 'translate-x-full' : 'translate-x-0'" class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-theme-sm duration-300 ease-linear"></div>
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

            <label for="is_default" class="flex gap-2 cursor-pointer select-none items-center text-sm font-medium text-gray-700 dark:text-gray-400">
              <div class="relative">
                <input type="checkbox" id="is_default" v-model="form.is_default" class="sr-only" />
                <div
                  :class="form.is_default ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                  class="flex h-5 w-5 items-center justify-center rounded-md border-[1.25px] hover:border-brand-500 dark:hover:border-brand-500"
                >
                  <span :class="form.is_default ? '' : 'opacity-0'">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </span>
                </div>
              </div>
              {{ t('addresses.setAsDefault') }}
            </label>
          </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('company.addresses.index')"
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
import { computed, watch, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'

const { t, locale } = useI18n()
const { success, error } = useNotifications()

const props = defineProps({
  governorates: { type: Array, required: true },
  districts: { type: Array, required: false, default: () => [] },
  areas: { type: Array, required: false, default: () => [] },
})

// expose reactive refs for template and script
const { governorates, districts, areas } = toRefs(props)

const form = useForm({
  label: '',
  address: '',
  governorate_id: '',
  district_id: '',
  area_id: '',
  is_active: true,
  is_default: false,
})

const availableDistricts = computed(() => {
  if (!form.governorate_id) return districts.value || []
  return (districts.value || []).filter((d) => String(d.governorate_id) === String(form.governorate_id))
})

const filteredAreas = computed(() => {
  if (!form.district_id) return areas.value || []
  return (areas.value || []).filter((a) => String(a.district_id) === String(form.district_id))
})

watch(
  () => form.governorate_id,
  () => {
    form.district_id = ''
    form.area_id = ''
  }
)

watch(
  () => form.district_id,
  () => {
    form.area_id = ''
  }
)

function create() {
  form.post(route('company.addresses.store'), {
    onSuccess: () => {
      success(t('addresses.addressCreatedSuccessfully'))
    },
    onError: () => {
      error(t('addresses.addressCreationFailed'))
    },
    preserveScroll: true,
  })
}
</script>
