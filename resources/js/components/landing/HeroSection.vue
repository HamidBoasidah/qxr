<template>
  <section class="hero-section relative w-full" :style="sectionStyle">
    <!-- Background blurred shapes -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="hero-blur-left"></div>
      <div class="hero-blur-right"></div>
    </div>

    <!-- Content -->
    <div class="container relative z-10 mx-auto px-4 py-20 lg:py-28">
      <div class="flex flex-col items-center gap-12">
        <!-- Heading & Subtitle -->
        <div class="flex flex-col items-center gap-6 text-center max-w-4xl">
          <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight text-[#1A4344]">
            {{ section.title }}
          </h1>
          <p class="text-lg md:text-xl text-gray-700 max-w-3xl">
            {{ section.subtitle }}
          </p>
        </div>

        <!-- Main Content Grid: Rating Badge | Image | Consultants Badge -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center w-full max-w-7xl">
          
          <!-- Rating Badge (Left/Start) -->
          <div v-if="showOverlays" class="flex justify-center lg:justify-end order-2 lg:order-1">
            <div class="flex flex-col items-center gap-4 max-w-xs">
              <div class="flex items-center gap-2 bg-white shadow-lg rounded-full px-6 py-4">
                <svg class="w-8 h-8 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-2xl font-bold text-gray-900">4.9</span>
              </div>
              <p class="text-center text-sm text-gray-600 leading-relaxed">
                جلسة الاستشارية التجارية وضحت لي نقاط ضعف المنافسين وزودتني بأفكار جديدة للتطوير
              </p>
              <button class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                اقرأ المزيد
              </button>
            </div>
          </div>

          <!-- Main Image with Action Icons (Center) -->
          <div class="flex flex-col items-center gap-6 order-1 lg:order-2">
            <div class="hero-image-frame relative">
              <!-- Image/Video -->
              <img
                v-if="heroImage"
                :src="heroImage"
                :alt="section.title"
                class="hero-image w-full h-auto rounded-3xl object-cover"
              />
            </div>
            
            <!-- Action Icons -->
            <div v-if="showOverlays" class="flex gap-4 justify-center">
              <button class="action-icon bg-[#EAF1F1] hover:bg-[#2B6F71] text-[#22595A] hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </button>
              <button class="action-icon bg-[#EAF1F1] hover:bg-[#2B6F71] text-[#22595A] hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </button>
              <button class="action-icon bg-[#EAF1F1] hover:bg-[#2B6F71] text-[#22595A] hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </button>
              <button class="action-icon bg-red-50 hover:bg-red-500 text-red-500 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Consultants Badge (Right/End) -->
          <div v-if="showOverlays" class="flex justify-center lg:justify-start order-3">
            <div class="flex flex-col items-center gap-4">
              <div class="flex -space-x-2 rtl:space-x-reverse">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 border-4 border-white shadow-md"></div>
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 border-4 border-white shadow-md"></div>
              </div>
              <div class="bg-white shadow-2xl rounded-3xl px-8 py-6 border-4 border-[#2B6F71]/10">
                <p class="text-5xl font-bold text-gray-900 text-center">50+</p>
                <p class="text-base text-gray-600 whitespace-nowrap text-center mt-2">مستشار مالي وتجاري</p>
              </div>
            </div>
          </div>

        </div>

        <!-- CTA Buttons (if exist in items) -->
        <div v-if="ctaButtons.length > 0" class="flex flex-wrap gap-4 justify-center mt-4">
          <a
            v-for="button in ctaButtons"
            :key="button.id"
            :href="button.link"
            :class="[
              'px-8 py-4 rounded-lg font-semibold text-lg transition-all duration-300',
              button.data?.variant === 'primary'
                ? 'bg-[#2B6F71] text-white hover:bg-[#1A4344] shadow-lg hover:shadow-xl'
                : 'bg-white text-[#2B6F71] border-2 border-[#2B6F71] hover:bg-[#EAF1F1]',
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
import { computed } from 'vue'

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
})

const sectionStyle = computed(() => {
  const settings = props.section.settings || {}
  return {
    background: settings.background || 'linear-gradient(139deg, rgba(236, 253, 245, 0.6) 10.51%, rgba(209, 250, 229, 0.6) 87.75%)',
    minHeight: settings.minHeight || '798px',
  }
})

const heroImage = computed(() => {
  const imageItem = props.section.items.find(item => item.data?.type === 'hero_image')
  return imageItem?.image_url || null
})

const showOverlays = computed(() => {
  const settings = props.section.settings || {}
  return settings.showOverlays !== false
})

const ctaButtons = computed(() => {
  return props.section.items.filter(item => item.data?.type === 'cta_button')
})
</script>

<style scoped>
.hero-section {
  position: relative;
  overflow: hidden;
}

.hero-blur-left {
  position: absolute;
  width: 1055px;
  height: 981px;
  left: -508px;
  top: 293px;
  background: rgba(32, 83, 85, 0.3);
  filter: blur(142.8px);
  border-radius: 100px;
  transform: rotate(-10.05deg);
}

.hero-blur-right {
  position: absolute;
  width: 479px;
  height: 446px;
  right: -150px;
  top: -49px;
  background: rgba(32, 83, 85, 0.3);
  filter: blur(142.8px);
  border-radius: 100px;
  transform: rotate(-10.05deg);
}

.hero-image-frame {
  border: 6px solid #2B6F71;
  border-radius: 28px;
  overflow: hidden;
  max-width: 450px;
  margin: 0 auto;
}

.hero-image {
  width: 100%;
  height: auto;
  display: block;
}

.action-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  border-radius: 9999px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  cursor: pointer;
}

@media (max-width: 1024px) {
  .hero-blur-left,
  .hero-blur-right {
    opacity: 0.5;
  }
  
  .hero-image-frame {
    max-width: 400px;
  }
}

@media (max-width: 768px) {
  .hero-blur-left,
  .hero-blur-right {
    display: none;
  }

  .action-icon {
    width: 50px;
    height: 50px;
  }
  
  .hero-image-frame {
    max-width: 350px;
  }
}
</style>
