<template>
  <section class="features-section py-16 lg:py-24 bg-gray-50">
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

      <!-- Features Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        <div
          v-for="feature in section.items"
          :key="feature.id"
          class="feature-card bg-white rounded-2xl p-6 lg:p-8 shadow-sm hover:shadow-xl transition-all duration-300 group"
        >
          <!-- Icon/Image -->
          <div class="flex items-center justify-center w-16 h-16 rounded-xl bg-[#EAF1F1] group-hover:bg-[#2B6F71] transition-colors mb-5">
            <component 
              v-if="feature.icon" 
              :is="getIconComponent(feature.icon)" 
              class="w-8 h-8 text-[#22595A] group-hover:text-white transition-colors" 
            />
            <img 
              v-else-if="feature.image_url" 
              :src="feature.image_url" 
              :alt="feature.title"
              class="w-8 h-8 object-contain"
            />
            <SearchIcon v-else class="w-8 h-8 text-[#22595A] group-hover:text-white transition-colors" />
          </div>

          <!-- Content -->
          <h3 class="text-xl font-bold text-gray-900 mb-3">
            {{ feature.title }}
          </h3>
          <p class="text-gray-600 leading-relaxed">
            {{ feature.description }}
          </p>

          <!-- Link (if exists) -->
          <a 
            v-if="feature.link" 
            :href="feature.link"
            class="inline-flex items-center gap-2 text-[#2B6F71] font-medium mt-4 group-hover:gap-3 transition-all"
          >
            {{ feature.link_text || 'حمّل التطبيق الآن' }}
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" transform="scale(-1, 1) translate(-24, 0)" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { h } from 'vue'

// Icons
const SearchIcon = { 
  render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' })
  ])
}

const VideoIcon = {
  render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' })
  ])
}

const DocumentIcon = {
  render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
  ])
}

const LocationIcon = {
  render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 11a3 3 0 11-6 0 3 3 0 016 0z' })
  ])
}

const ShieldIcon = {
  render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
  ])
}

const StarIcon = {
  render: () => h('svg', { class: 'w-full h-full', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })
  ])
}

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
})

const getIconComponent = (iconName) => {
  const icons = {
    search: SearchIcon,
    video: VideoIcon,
    document: DocumentIcon,
    location: LocationIcon,
    shield: ShieldIcon,
    star: StarIcon,
  }
  return icons[iconName] || SearchIcon
}
</script>

<style scoped>
.feature-card {
  border: 1px solid transparent;
}

.feature-card:hover {
  border-color: #2B6F71;
  transform: translateY(-4px);
}
</style>
