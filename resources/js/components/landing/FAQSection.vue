<template>
  <section class="faq-section py-16 lg:py-24 bg-white">
    <div class="container mx-auto px-4 max-w-4xl">
      <!-- Header -->
      <div class="text-center mb-12">
        <span v-if="section.settings?.badge" class="inline-block px-4 py-2 bg-[#2B6F71] text-white rounded-full text-sm font-medium mb-4">
          {{ section.settings.badge }}
        </span>
        <h2 class="text-3xl md:text-4xl font-bold text-[#1A4344] mb-4">
          {{ section.title }}
        </h2>
        <p v-if="section.subtitle" class="text-lg text-gray-600">
          {{ section.subtitle }}
        </p>
      </div>

      <!-- FAQ Items -->
      <div class="space-y-4">
        <div
          v-for="(faq, index) in section.items"
          :key="faq.id"
          class="faq-item border-2 border-gray-200 rounded-xl overflow-hidden transition-all"
          :class="{ 'border-[#2B6F71] shadow-lg': openIndex === index }"
        >
          <button
            @click="toggle(index)"
            class="w-full flex items-center justify-between p-6 text-right bg-white hover:bg-gray-50 transition-colors"
          >
            <span class="text-lg font-semibold text-gray-900 pr-4">
              {{ faq.title }}
            </span>
            <svg
              class="w-6 h-6 text-[#2B6F71] transform transition-transform duration-300"
              :class="{ 'rotate-180': openIndex === index }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <transition
            name="accordion"
            @enter="enter"
            @leave="leave"
          >
            <div v-show="openIndex === index" class="faq-content">
              <div class="p-6 pt-0 text-gray-600 leading-relaxed">
                {{ faq.description }}
              </div>
            </div>
          </transition>
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

const openIndex = ref(0) // First item open by default

const toggle = (index) => {
  openIndex.value = openIndex.value === index ? null : index
}

const enter = (el) => {
  el.style.height = 'auto'
  const height = el.scrollHeight
  el.style.height = '0px'
  el.offsetHeight // force reflow
  el.style.height = height + 'px'
}

const leave = (el) => {
  el.style.height = el.scrollHeight + 'px'
  el.offsetHeight // force reflow
  el.style.height = '0px'
}
</script>

<style scoped>
.faq-content {
  overflow: hidden;
  transition: height 0.3s ease;
}

.accordion-enter-active,
.accordion-leave-active {
  transition: height 0.3s ease;
}
</style>
