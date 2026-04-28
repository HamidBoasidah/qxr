<template>
  <div class="p-4 sm:p-6 space-y-6">
    <div v-if="advertisement.image_url" class="mb-4">
      <img :src="advertisement.image_url" class="h-48 w-auto rounded-lg object-cover" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.titleField') }}</label>
        <p class="mt-1 text-gray-800 dark:text-white/90">{{ advertisement.title }}</p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.position') }}</label>
        <p class="mt-1 text-gray-800 dark:text-white/90">{{ advertisement.position }}</p>
      </div>
      <div v-if="advertisement.description" class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.description') }}</label>
        <p class="mt-1 text-gray-800 dark:text-white/90">{{ advertisement.description }}</p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.company') }}</label>
        <p class="mt-1 text-gray-800 dark:text-white/90">{{ advertisement.company_name || '-' }}</p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('common.status') }}</label>
        <p class="mt-1">
          <span :class="advertisement.is_active ? 'text-green-600' : 'text-red-600'">
            {{ advertisement.is_active ? t('common.active') : t('common.inactive') }}
          </span>
        </p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.startDate') }}</label>
        <p class="mt-1 text-gray-800 dark:text-white/90">{{ advertisement.start_date || '-' }}</p>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.endDate') }}</label>
        <p class="mt-1 text-gray-800 dark:text-white/90">{{ advertisement.end_date || '-' }}</p>
      </div>
      <div v-if="advertisement.link_url" class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('advertisements.linkUrl') }}</label>
        <a :href="advertisement.link_url" target="_blank" class="mt-1 block text-brand-500 hover:underline">{{ advertisement.link_url }}</a>
      </div>
    </div>

    <div class="flex gap-3">
      <Link :href="route('admin.advertisements.edit', advertisement.id)" class="rounded-lg bg-yellow-500 px-4 py-2 text-sm text-white hover:bg-yellow-600">
        {{ t('common.edit') }}
      </Link>
      <Link :href="route('admin.advertisements.index')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400">
        {{ t('common.back') }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from '@/route'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
defineProps({ advertisement: Object })
</script>
