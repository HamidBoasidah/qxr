<template>
  <div class="space-y-6">
    <!-- Offer Information Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('offers.offerInformation') }}
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-x-5 gap-y-6 md:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('offers.title') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ offer.title || 'N/A' }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('offers.status') }}
            </label>
            <p>
              <span
                class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': offer.status === 'active',
                  'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-500': offer.status === 'draft',
                  'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-500': offer.status === 'paused',
                  'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': offer.status === 'expired',
                }"
              >
                {{ t(`offers.status_${offer.status}`) }}
              </span>
            </p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('offers.scope') }}
            </label>
            <p>
              <span
                class="inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500': offer.scope === 'public',
                  'bg-yellow-50 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-500': offer.scope === 'private',
                }"
              >
                {{ t(`offers.scope_${offer.scope}`) }}
              </span>
            </p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('offers.period') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">
              <span v-if="offer.start_at">{{ formatDate(offer.start_at) }}</span>
              <span v-if="offer.start_at && offer.end_at"> - </span>
              <span v-if="offer.end_at">{{ formatDate(offer.end_at) }}</span>
              <span v-if="!offer.start_at && !offer.end_at">{{ t('offers.noLimit') }}</span>
            </p>
          </div>

          <div v-if="offer.description" class="md:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ t('offers.description') }}
            </label>
            <p class="text-base text-gray-800 dark:text-white/90">{{ offer.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Offer Items Section -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('offers.offerItems') }}
          <span class="ml-2 inline-flex items-center justify-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500">
            {{ offer.items?.length || 0 }}
          </span>
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div v-if="!offer.items || offer.items.length === 0" class="text-center py-8 text-gray-500">
          {{ t('offers.noItems') }}
        </div>
        <div v-else class="space-y-3">
          <div v-for="item in offer.items" :key="item.id" class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
            <p class="font-medium text-gray-800 dark:text-white/90">{{ item.product?.name }}</p>
            <p class="text-sm text-gray-500">{{ t('offers.minQty') }}: {{ item.min_qty }}</p>
            <p class="text-sm text-gray-500">{{ t('offers.rewardType') }}: {{ t(`offers.reward_${item.reward_type}`) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Offer Targets Section -->
    <div v-if="offer.scope === 'private'" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-medium text-gray-800 dark:text-white">
          {{ t('offers.offerTargets') }}
          <span class="ml-2 inline-flex items-center justify-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-500">
            {{ offer.targets?.length || 0 }}
          </span>
        </h2>
      </div>

      <div class="p-4 sm:p-6">
        <div v-if="!offer.targets || offer.targets.length === 0" class="text-center py-8 text-gray-500">
          {{ t('offers.noTargets') }}
        </div>
        <div v-else class="space-y-2">
          <div v-for="target in offer.targets" :key="target.id" class="p-3 border border-gray-200 rounded-lg dark:border-gray-700">
            <span class="text-sm text-gray-700 dark:text-gray-400">
              {{ t(`offers.target_${target.target_type}`) }}: {{ target.target_id }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
      <Link
        :href="route('admin.offers.index')"
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

const { t } = useI18n()

const props = defineProps({
  offer: Object,
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}
</script>
