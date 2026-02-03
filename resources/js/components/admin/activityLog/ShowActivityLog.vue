<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">{{ t('activitylogs.activityInformation') }}</h2>
      </div>

      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.id') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ activity.id }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.event') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ activity.event }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.description') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ activity.description }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.causer') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ activity.causer_name ?? ('ID: ' + (activity.causer_id ?? '—')) }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.subject') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ activity.subject_type ? activity.subject_type.split('\\').pop() : '—' }}{{ activity.subject_id ? (' #' + activity.subject_id) : '' }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.createdAt') }}</label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ dayjs(activity.created_at).format('YYYY-MM-DD HH:mm:ss') }}</p>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('activitylogs.properties.attributes') }}</label>
            <pre class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 p-3 rounded overflow-x-auto">
              {{ JSON.stringify(activity.properties ?? {}, null, 2) }}
            </pre>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('admin.activitylogs.index')"
        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]"
      >
        {{ t('buttons.backToList') }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'

const { t } = useI18n()

defineProps({ activity: { type: Object, required: true } })
</script>
