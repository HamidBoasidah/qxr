<template>
  <section class="mobile-app-section py-16 lg:py-24 bg-[#2B6F71] text-white relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
      <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full filter blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
      <!-- Header -->
      <div class="text-center mb-12 lg:mb-16">
        <span v-if="section.settings?.badge" class="inline-block px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium mb-4">
          {{ section.settings.badge }}
        </span>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
          {{ section.title }}
        </h2>
        <p v-if="section.subtitle" class="text-lg text-white/90 max-w-2xl mx-auto">
          {{ section.subtitle }}
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
        <!-- Mobile Mockup -->
        <div class="flex justify-center">
          <div class="relative max-w-sm w-full">
            <img
              v-if="mobileImage"
              :src="mobileImage"
              alt="Mobile App"
              class="w-full h-auto"
            />
            <div v-else class="bg-white/10 backdrop-blur-md rounded-[3rem] p-4 shadow-2xl border-8 border-white/20">
              <div class="bg-[#1A4344] rounded-[2.5rem] aspect-[9/19] flex items-center justify-center">
                <svg class="w-24 h-24 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div>
          <!-- Features List -->
          <div class="space-y-6 mb-8">
            <div
              v-for="feature in features"
              :key="feature.id"
              class="flex items-start gap-4 bg-white/5 backdrop-blur-sm rounded-2xl p-6 hover:bg-white/10 transition-all"
            >
              <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold mb-2">{{ feature.title }}</h3>
                <p class="text-white/80 leading-relaxed">{{ feature.description }}</p>
              </div>
            </div>
          </div>

          <!-- App Store Badges -->
          <div class="flex flex-wrap gap-4">
            <a
              v-for="button in storeButtons"
              :key="button.id"
              :href="button.link"
              target="_blank"
              class="inline-flex items-center gap-3 px-6 py-3 bg-white text-[#2B6F71] rounded-xl font-semibold hover:bg-white/90 transition-all shadow-lg hover:shadow-xl"
            >
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                <path v-if="button.data?.store === 'appstore'" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z" />
                <path v-else d="M3 20.5v-17c0-.59.34-1.11.84-1.35L13.69 12l-9.85 9.85c-.5-.24-.84-.76-.84-1.35zm13.81-5.12L6.05 21.34l8.49-8.49 2.27 2.53zm0-2.76l-2.27 2.53L6.05 6.66l10.76 5.96zM17.81 12L21.16 9.85A1.5 1.5 0 0122 11.21v1.58c0 .54-.29 1.03-.84 1.36L17.81 12z" />
              </svg>
              <span>{{ button.title }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
})

const mobileImage = computed(() => {
  const imageItem = props.section.items.find(item => item.data?.type === 'mobile_image')
  return imageItem?.image_url || null
})

const features = computed(() => {
  return props.section.items.filter(item => item.data?.type === 'feature')
})

const storeButtons = computed(() => {
  return props.section.items.filter(item => item.data?.type === 'store_button')
})
</script>
