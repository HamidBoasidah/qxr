<template>
  <ThemeProvider>
    <SidebarProvider>
      <GlobalLoadingOverlay />
  <slot />
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup lang="ts">
import ThemeProvider from './components/layout/ThemeProvider.vue'
import SidebarProvider from './components/layout/SidebarProvider.vue'
import GlobalLoadingOverlay from './components/common/GlobalLoadingOverlay.vue'
</script>
