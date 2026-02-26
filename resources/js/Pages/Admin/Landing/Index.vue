<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="currentPageTitle" :desc="t('landing.pagesDescription')">

        <div class="overflow-hidden">
          <!-- Toolbar -->
          <div class="flex flex-col gap-2 px-4 py-4 border border-b-0 border-gray-200 rounded-b-none rounded-xl dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ landing_pages.length }} {{ t('landing.pages') }}</span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
              <Link
                :href="route('admin.landing.create')"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-[11px] text-sm font-medium text-white transition sm:w-auto"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ t('landing.createPage') }}
              </Link>
            </div>
          </div>

          <!-- Table -->
          <div class="max-w-full overflow-x-auto">
            <table class="w-full min-w-full">
              <thead>
                <tr>
                  <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                    <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('common.title') }}</p>
                  </th>
                  <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                    <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('common.slug') }}</p>
                  </th>
                  <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                    <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('landing.sections_count') }}</p>
                  </th>
                  <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                    <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('common.status') }}</p>
                  </th>
                  <th class="px-4 py-3 text-start border border-gray-100 dark:border-gray-800">
                    <p class="font-medium text-gray-700 text-theme-xs dark:text-gray-400">{{ t('common.actions') }}</p>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="landingPage in landing_pages" :key="landingPage.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors">
                  <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                      {{ landingPage.title }}
                    </p>
                  </td>
                  <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                    <code class="text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">
                      /{{ landingPage.slug }}
                    </code>
                  </td>
                  <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                      {{ landingPage.sections_count }} {{ t('landing.sections') }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                    <span
                      :class="[
                        'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium',
                        landingPage.is_active
                          ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300'
                          : 'bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300',
                      ]"
                    >
                      {{ landingPage.is_active ? t('common.active') : t('common.inactive') }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                      <Link
                        :href="route('admin.landing.sections.index', landingPage.id)"
                        class="text-brand-500 hover:text-brand-600 dark:text-brand-400 text-sm font-medium"
                      >
                        {{ t('landing.manageSections') }}
                      </Link>
                      <Link
                        :href="route('admin.landing.edit', landingPage.id)"
                        class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
                      >
                        {{ t('common.edit') }}
                      </Link>
                      <button
                        @click="confirmDelete(landingPage)"
                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
                      >
                        {{ t('common.delete') }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="landing_pages.length === 0" class="text-center py-12 border border-t-0 border-gray-200 dark:border-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
              {{ t('landing.noPages') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ t('landing.noPagesDescription') }}
            </p>
            <div class="mt-6">
              <Link
                :href="route('admin.landing.create')"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-[11px] text-sm font-medium text-white transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ t('landing.createFirstPage') }}
              </Link>
            </div>
          </div>
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import ComponentCard from '@/components/common/ComponentCard.vue'

const { t } = useI18n()
const currentPageTitle = computed(() => t('landing.pages'))

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
