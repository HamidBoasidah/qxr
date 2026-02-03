<template>
  <button
    class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-gray-700 h-11 w-11 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
    @click.prevent="onToggle"
    :title="title"
    aria-label="Toggle direction"
  >
    <!-- Use global icon component instead of inline SVG -->
    <GlobalIcon class="text-gray-600 dark:text-gray-300" width="20" height="20" />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { i18n } from '@/i18n'
import { switchLocale } from '@/composables/useLocale' // 👈 المهم
import GlobalIcon from '@/icons/GlobalIcon.vue'

const title = computed(() =>
  i18n.global.locale.value === 'ar'
    ? i18n.global.t('common.switchToLTR')
    : i18n.global.t('common.switchToRTL')
)

async function onToggle() {
  const next = i18n.global.locale.value === 'ar' ? 'en' : 'ar'
  await switchLocale(next) // 👈 يحدّث i18n + <html lang/dir> + يحفظ في السيرفر
}
</script>
