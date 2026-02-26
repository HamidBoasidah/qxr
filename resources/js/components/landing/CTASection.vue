<template>
  <section class="cta-section py-16 lg:py-24 bg-gradient-to-r from-[#2B6F71] to-[#1A4344] text-white">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl md:text-5xl font-bold mb-6">
          {{ section.title }}
        </h2>
        <p v-if="section.subtitle" class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
          {{ section.subtitle }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-wrap gap-4 justify-center">
          <a
            v-for="button in section.items"
            :key="button.id"
            :href="button.link"
            :class="[
              'px-8 py-4 rounded-lg font-semibold text-lg transition-all duration-300',
              button.data?.variant === 'secondary'
                ? 'bg-white text-[#2B6F71] hover:bg-gray-100'
                : 'bg-yellow-400 text-[#1A4344] hover:bg-yellow-300',
            ]"
          >
            {{ button.title }}
          </a>
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
