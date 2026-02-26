<template>
  <div class="landing-page min-h-screen bg-white">
    <!-- Render sections dynamically -->
    <component
      v-for="section in sections"
      :key="section.id"
      :is="getSectionComponent(section.type)"
      :section="section"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import HeroSection from '@/components/landing/HeroSection.vue'
import FeaturesSection from '@/components/landing/FeaturesSection.vue'
import ServicesSection from '@/components/landing/ServicesSection.vue'
import StepsSection from '@/components/landing/StepsSection.vue'
import TestimonialsSection from '@/components/landing/TestimonialsSection.vue'
import FAQSection from '@/components/landing/FAQSection.vue'
import CTASection from '@/components/landing/CTASection.vue'
import StatsSection from '@/components/landing/StatsSection.vue'
import MobileAppSection from '@/components/landing/MobileAppSection.vue'

const props = defineProps({
  landingPage: {
    type: Object,
    required: true,
  },
})

const sections = computed(() => props.landingPage.sections || [])

const getSectionComponent = (type) => {
  const components = {
    hero: HeroSection,
    features: FeaturesSection,
    services: ServicesSection,
    steps: StepsSection,
    testimonials: TestimonialsSection,
    faq: FAQSection,
    cta: CTASection,
    stats: StatsSection,
    mobile_app: MobileAppSection,
  }

  return components[type] || null
}
</script>

<style scoped>
.landing-page {
  font-family: 'Readex Pro', sans-serif;
}
</style>
