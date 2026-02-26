<template>
  <section class="testimonials-section py-16 lg:py-24 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="text-center mb-12">
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

      <!-- Testimonials Carousel -->
      <div class="relative max-w-4xl mx-auto">
        <div class="overflow-hidden">
          <transition-group name="slide" tag="div">
            <div
              v-for="(testimonial, index) in section.items"
              v-show="index === currentIndex"
              :key="testimonial.id"
              class="testimonial-card bg-gradient-to-br from-[#EAF1F1] to-white rounded-3xl p-8 lg:p-12 shadow-xl"
            >
              <!-- Stars -->
              <div class="flex justify-center gap-1 mb-6">
                <svg v-for="i in 5" :key="i" class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>

              <!-- Quote -->
              <blockquote class="text-xl md:text-2xl text-gray-800 text-center leading-relaxed mb-8">
                "{{ testimonial.description }}"
              </blockquote>

              <!-- Author -->
              <div class="flex items-center justify-center gap-4">
                <img
                  v-if="testimonial.image_url"
                  :src="testimonial.image_url"
                  :alt="testimonial.title"
                  class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg"
                />
                <div class="text-center">
                  <p class="font-bold text-gray-900">{{ testimonial.title }}</p>
                  <p v-if="testimonial.data?.position" class="text-sm text-gray-600">
                    {{ testimonial.data.position }}
                  </p>
                </div>
              </div>
            </div>
          </transition-group>
        </div>

        <!-- Navigation Arrows -->
        <button
          @click="prev"
          class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 lg:-translate-x-16 w-12 h-12 rounded-full bg-white shadow-xl flex items-center justify-center text-[#2B6F71] hover:bg-[#2B6F71] hover:text-white transition-all"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          @click="next"
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 lg:translate-x-16 w-12 h-12 rounded-full bg-white shadow-xl flex items-center justify-center text-[#2B6F71] hover:bg-[#2B6F71] hover:text-white transition-all"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <!-- Dots -->
        <div class="flex justify-center gap-2 mt-8">
          <button
            v-for="(testimonial, index) in section.items"
            :key="`dot-${testimonial.id}`"
            @click="currentIndex = index"
            :class="[
              'w-3 h-3 rounded-full transition-all',
              index === currentIndex ? 'bg-[#2B6F71] w-8' : 'bg-gray-300',
            ]"
          ></button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
})

const currentIndex = ref(0)

const next = () => {
  currentIndex.value = (currentIndex.value + 1) % props.section.items.length
}

const prev = () => {
  currentIndex.value = currentIndex.value === 0 ? props.section.items.length - 1 : currentIndex.value - 1
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: all 0.5s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(50px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(-50px);
}
</style>
