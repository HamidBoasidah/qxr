<template>
  <CompanyLayout>
    <div class="space-y-5 sm:space-y-6">
      <!-- Welcome Card -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
          {{ t('pages.welcomeLoggedIn') }}
        </h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          {{ profile?.company_name || t('company.dashboard') }}
        </p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 sm:gap-6">
        <!-- Products Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('menu.products') }}</p>
              <p class="mt-2 text-3xl font-semibold text-gray-800 dark:text-white/90">
                {{ stats.total_products }}
              </p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-500/10">
              <svg class="h-6 w-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Messages Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('menu.messages') }}</p>
              <p class="mt-2 text-3xl font-semibold text-gray-800 dark:text-white/90">
                {{ stats.total_messages }}
              </p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/10">
              <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Active Offers Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('menu.offers') }}</p>
              <p class="mt-2 text-3xl font-semibold text-gray-800 dark:text-white/90">
                {{ stats.active_offers }}
              </p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-500/10">
              <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </CompanyLayout>
</template>

<script setup>
import CompanyLayout from '@/components/layout/CompanyLayout.vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const { t } = useI18n()

const stats = computed(() => usePage().props.stats || {
  total_products: 0,
  total_messages: 0,
  active_offers: 0
})

const profile = computed(() => usePage().props.profile)
</script>
