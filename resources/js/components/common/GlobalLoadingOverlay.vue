<template>
  <transition name="fade">
    <div
      v-if="globalLoading"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30 backdrop-blur-sm"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="flex flex-col items-center">
        <div class="logo-spinner">
          <svg class="spinner-ring" viewBox="0 0 100 100">
            <circle class="ring-track" cx="50" cy="50" r="45" />
            <circle class="ring-progress" cx="50" cy="50" r="45" />
          </svg>
          <div class="logo-center">
            <img
              class="dark:hidden"
              src="/images/logo/logo-icon.png"
              alt="Loading"
              width="36"
              height="36"
            />
            <img
              class="hidden dark:block"
              src="/images/logo/logo-dark.png"
              alt="Loading"
              width="36"
              height="36"
            />
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { useGlobalLoading } from '@/composables/useGlobalLoading'
import { useI18n } from 'vue-i18n'

const { globalLoading } = useGlobalLoading()
const { t } = useI18n()
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 150ms linear; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.logo-spinner {
  position: relative;
  width: 80px;
  height: 80px;
}

.spinner-ring {
  width: 100%;
  height: 100%;
  animation: spin 1.2s linear infinite;
}

.ring-track {
  fill: none;
  stroke: rgba(255, 255, 255, 0.15);
  stroke-width: 4;
}

.ring-progress {
  fill: none;
  stroke: #2B388F;
  stroke-width: 4;
  stroke-linecap: round;
  stroke-dasharray: 283;
  stroke-dashoffset: 200;
}

:is(.dark *) .ring-progress {
  stroke: #A3C89B;
}

.logo-center {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-center img {
  animation: pulse-logo 1.5s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse-logo {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(0.9); }
}
</style>
