<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <Link :href="route('admin.advertisements.create')" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
        {{ t('advertisements.create') }}
      </Link>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm text-right">
        <thead>
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">#</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.image') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.titleField') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.position') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.company') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.dates') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('common.status') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ad in advertisements.data" :key="ad.id" class="border-b border-gray-100 dark:border-gray-800">
            <td class="px-4 py-3">{{ ad.id }}</td>
            <td class="px-4 py-3">
              <img v-if="ad.image_url" :src="ad.image_url" class="h-10 w-16 rounded object-cover" />
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-4 py-3">{{ ad.title }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                {{ positionLabel(ad.position) }}
              </span>
            </td>
            <td class="px-4 py-3">{{ ad.company_name || '-' }}</td>
            <td class="px-4 py-3 text-xs">
              {{ ad.start_date || '-' }} → {{ ad.end_date || '-' }}
            </td>
            <td class="px-4 py-3">
              <button @click="toggleActive(ad)" :class="ad.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" class="rounded-full px-2 py-1 text-xs">
                {{ ad.is_active ? t('common.active') : t('common.inactive') }}
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <Link :href="route('admin.advertisements.show', ad.id)" class="text-brand-500 hover:text-brand-600">
                  {{ t('common.view') }}
                </Link>
                <Link :href="route('admin.advertisements.edit', ad.id)" class="text-yellow-500 hover:text-yellow-600">
                  {{ t('common.edit') }}
                </Link>
                <button @click="deleteAd(ad.id)" class="text-red-500 hover:text-red-600">
                  {{ t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { route } from '@/route'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const props = defineProps({ advertisements: Object })

const positionLabel = (pos) => {
  const labels = {
    home_slider: t('advertisements.positions.home_slider'),
    home_banner: t('advertisements.positions.home_banner'),
    category_banner: t('advertisements.positions.category_banner'),
    popup: t('advertisements.positions.popup'),
  }
  return labels[pos] || pos
}

const toggleActive = (ad) => {
  const url = ad.is_active
    ? route('admin.advertisements.deactivate', ad.id)
    : route('admin.advertisements.activate', ad.id)
  router.patch(url)
}

const deleteAd = (id) => {
  if (confirm(t('common.confirmDelete'))) {
    router.delete(route('admin.advertisements.destroy', id))
  }
}
</script>
