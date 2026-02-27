<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800/50">
    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">{{ title }}</h3>
    <div class="overflow-x-auto">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  }
})
</script>
