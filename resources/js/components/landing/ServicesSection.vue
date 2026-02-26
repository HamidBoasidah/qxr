<template>
  <section class="services-section py-16 lg:py-24 bg-white">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div v-if="section.title" class="text-center mb-12">
        <span v-if="section.settings?.badge" class="inline-block px-4 py-2 bg-[#2B6F71] text-white rounded-full text-sm font-medium mb-4">
          {{ section.settings.badge }}
        </span>
        <h2 class="text-3xl md:text-4xl font-bold text-[#1A4344] mb-4">
          {{ section.title }}
        </h2>
        <p v-if="section.subtitle" class="text-lg text-gray-600 max-w-3xl mx-auto">
          {{ section.subtitle }}
        </p>
      </div>

      <!-- Services Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="service in section.items"
          :key="service.id"
          :class="[
            'service-card rounded-3xl p-8 shadow-sm hover:shadow-2xl transition-all duration-300 group relative overflow-hidden',
            getCardBackground(service.data?.color || 'teal'),
          ]"
        >
          <!-- Background Pattern (optional) -->
          <div v-if="service.data?.pattern" class="absolute inset-0 opacity-5">
            <img :src="service.data.pattern" class="w-full h-full object-cover" alt="" />
          </div>

          <!-- Content -->
          <div class="relative z-10">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">
              {{ service.title }}
            </h3>
            <p class="text-gray-700 leading-relaxed mb-6">
              {{ service.description }}
            </p>

            <!-- Image/Illustration -->
            <div v-if="service.image_url" class="flex justify-center mb-6">
              <img 
                :src="service.image_url" 
                :alt="service.title"
                class="w-full max-w-[200px] h-auto object-contain"
              />
            </div>

            <!-- CTA Button -->
            <a 
              v-if="service.link"
              :href="service.link"
              class="inline-flex items-center gap-2 text-[#2B6F71] font-semibold hover:gap-3 transition-all group-hover:text-[#1A4344]"
            >
              <span class="px-4 py-2 bg-white/50 rounded-lg">
                {{ service.link_text || 'مزيد من المعلومات' }}
              </span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" transform="scale(-1, 1) translate(-24, 0)" />
              </svg>
            </a>
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

const getCardBackground = (color) => {
  const backgrounds = {
    teal: 'bg-gradient-to-br from-teal-50 to-teal-100',
    green: 'bg-gradient-to-br from-green-50 to-green-100',
    blue: 'bg-gradient-to-br from-blue-50 to-blue-100',
    pink: 'bg-gradient-to-br from-pink-50 to-pink-100',
    orange: 'bg-gradient-to-br from-orange-50 to-orange-100',
    purple: 'bg-gradient-to-br from-purple-50 to-purple-100',
  }
  return backgrounds[color] || backgrounds.teal
}
</script>

<style scoped>
.service-card {
  border: 2px solid transparent;
}

.service-card:hover {
  border-color: #2B6F71;
  transform: translateY(-8px);
}
</style>
