<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('addresses.addressInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('addresses.label') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ address.label ?? '—' }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('addresses.address') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ address.address ?? '—' }}</p>
          </div>

          <!-- street/building/floor/apartment fields removed -->

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('governorates.governorate') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ locale === 'ar' ? (address.governorate_name_ar ?? address.governorate?.name_ar ?? '—') : (address.governorate_name_en ?? address.governorate?.name_en ?? '—') }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('districts.district') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ locale === 'ar' ? (address.district_name_ar ?? address.district?.name_ar ?? '—') : (address.district_name_en ?? address.district?.name_en ?? '—') }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('areas.area') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ locale === 'ar' ? (address.area?.name_ar ?? address.area_name_ar ?? '—') : (address.area?.name_en ?? address.area_name_en ?? '—') }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('common.status') }}
            </label>
            <span
              class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
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
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('admin.addresses.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>

      <Link
        :href="route('admin.addresses.edit', address.id)"
        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
      >
        {{ t('buttons.edit') }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

defineProps({
  address: { type: Object, required: true },
})
</script>
