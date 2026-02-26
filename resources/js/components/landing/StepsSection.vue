<template>
  <section class="steps-section py-16 lg:py-24 bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="text-center mb-16">
        <span v-if="section.settings?.badge" class="inline-block px-4 py-2 bg-[#2B6F71] text-white rounded-full text-sm font-medium mb-4">
          {{ section.settings.badge }}
        </span>
        <h2 class="text-3xl md:text-4xl font-bold text-[#1A4344] mb-4">
          {{ section.title }}
        </h2>
        <p v-if="section.subtitle" class="text-lg text-gray-600 max-w-2xl mx-auto">
          {{ section.subtitle }}
        </p>
      </div>

      <!-- Steps -->
      <div class="relative max-w-5xl mx-auto">
        <!-- Timeline connector (desktop only) -->
        <div class="hidden lg:block absolute top-1/2 left-0 right-0 h-1 bg-gradient-to-r from-[#2B6F71] via-[#22595A] to-[#2B6F71] transform -translate-y-1/2" style="z-index: 0;"></div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 relative">
          <div
            v-for="(step, index) in section.items"
            :key="step.id"
            class="step-item flex flex-col items-center text-center relative"
          >
            <!-- Step Number Circle -->
            <div class="step-circle relative z-10 w-20 h-20 rounded-full bg-white border-4 border-[#2B6F71] flex items-center justify-center mb-6 shadow-lg">
              <div class="w-14 h-14 rounded-full bg-[#2B6F71] flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                  <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>

            <!-- Content -->
            <h3 class="text-xl font-bold text-gray-900 mb-3">
              {{ step.title }}
            </h3>
            <p class="text-gray-600 leading-relaxed max-w-sm">
              {{ step.description }}
            </p>
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

<style scoped>
.step-circle {
  transition: all 0.3s ease;
}

.step-item:hover .step-circle {
  transform: scale(1.1);
  box-shadow: 0 20px 25px -5px rgba(43, 111, 113, 0.3);
}
</style>
