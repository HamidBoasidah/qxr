<template>
  <div class="min-h-screen xl:flex">
    <app-sidebar />
    <Backdrop />
    <div
      class="flex-1 transition-all duration-300 ease-in-out bg-gray-50 dark:bg-gray-900"
      :class="[
        (isExpanded || isHovered)
          ? 'lg:[padding-inline-start:290px]'
          : 'lg:[padding-inline-start:90px]'
      ]"
    >
      <app-header />
      <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <slot></slot>
      </div>
    </div>
    <NotificationHost position="top-end" />
  </div>
</template>

<script setup>
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import { useSidebar } from '@/composables/useSidebar'
import Backdrop from './Backdrop.vue'
import NotificationHost from '@/components/notification/NotificationHost.vue'
const { isExpanded, isHovered } = useSidebar()
</script>
