<template>
  <transition name="fade">
    <div
      v-if="globalLoading"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30 backdrop-blur-sm"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="flex flex-col items-center gap-4">
        <SpinnerThree />
        <span class="text-white text-sm">{{ t('common.loading') }}</span>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { useGlobalLoading } from '@/composables/useGlobalLoading'
import { useI18n } from 'vue-i18n'
import SpinnerThree from '@/components/spinner/SpinnerThree.vue'

const { globalLoading } = useGlobalLoading()
const { t } = useI18n()
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 150ms linear; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
