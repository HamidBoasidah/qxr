<template>
  <section class="stats-section py-16 lg:py-20 bg-white">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
        <div
          v-for="stat in section.items"
          :key="stat.id"
          class="text-center"
        >
          <div class="text-4xl md:text-5xl font-bold text-[#2B6F71] mb-2">
            {{ stat.data?.value || '0' }}{{ stat.data?.suffix || '' }}
          </div>
          <div class="text-gray-600 font-medium">
            {{ stat.title }}
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
})
</script>
