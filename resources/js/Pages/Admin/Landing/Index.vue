<template>
  <AdminLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ t('landing.pages') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              {{ t('landing.pagesDescription') }}
            </p>
          </div>
          <Link
            :href="route('admin.landing.create')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#2B6F71] text-white rounded-lg hover:bg-[#1A4344] transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('landing.createPage') }}
          </Link>
        </div>

        <!-- Landing Pages List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  {{ t('common.title') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  {{ t('common.slug') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  {{ t('landing.sections_count') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  {{ t('common.status') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  {{ t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="landingPage in landing_pages" :key="landingPage.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ landingPage.title }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <code class="text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">
                    /{{ landingPage.slug }}
                  </code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ landingPage.sections_count }} {{ t('landing.sections') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                      landingPage.is_active
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                    ]"
                  >
                    {{ landingPage.is_active ? t('common.active') : t('common.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <div class="flex items-center gap-3">
                    <Link
                      :href="route('admin.landing.sections.index', landingPage.id)"
                      class="text-[#2B6F71] hover:text-[#1A4344] font-medium"
                    >
                      {{ t('landing.manageSections') }}
                    </Link>
                    <Link
                      :href="route('admin.landing.edit', landingPage.id)"
                      class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300font-medium"
                    >
                      {{ t('common.edit') }}
                    </Link>
                    <button
                      @click="confirmDelete(landingPage)"
                      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-medium"
                    >
                      {{ t('common.delete') }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty State -->
          <div v-if="landing_pages.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              {{ t('landing.noPages') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ t('landing.noPagesDescription') }}
            </p>
            <div class="mt-6">
              <Link
                :href="route('admin.landing.create')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#2B6F71] text-white rounded-lg hover:bg-[#1A4344] transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('landing.createFirstPage') }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const { t } = useI18n()

const props = defineProps({
  landing_pages: {
    type: Array,
    default: () => [],
  },
})

const confirmDelete = (landingPage) => {
  if (confirm(t('landing.deleteConfirmation', { title: landingPage.title }))) {
    router.delete(route('admin.landing.destroy', landingPage.id), {
      onSuccess: () => {
        // Success message handled by backend
      },
    })
  }
}
</script>
