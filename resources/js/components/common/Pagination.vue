<template>
  <div v-if="links && links.length > 3" class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex justify-between flex-1 sm:hidden">
      <Link
        v-if="links[0].url"
        :href="links[0].url"
        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        {{ t('pagination.previous') }}
      </Link>
      <span
        v-else
        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed opacity-50"
      >
        {{ t('pagination.previous') }}
      </span>
      <Link
        v-if="links[links.length - 1].url"
        :href="links[links.length - 1].url"
        class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        {{ t('pagination.next') }}
      </Link>
      <span
        v-else
        class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-md cursor-not-allowed opacity-50"
      >
        {{ t('pagination.next') }}
      </span>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-400">
          {{ getPaginationText() }}
        </p>
      </div>
      <div>
        <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <template v-for="(link, index) in links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="[
                'relative inline-flex items-center px-4 py-2 text-sm font-medium',
                link.active
                  ? 'z-10 bg-brand-600 text-white focus:z-20'
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                index === 0 ? 'rounded-l-md' : '',
                index === links.length - 1 ? 'rounded-r-md' : '',
                'border border-gray-300 dark:border-gray-600'
              ]"
              :preserve-scroll="true"
              v-html="link.label"
            />
            <span
              v-else
              :class="[
                'relative inline-flex items-center px-4 py-2 text-sm font-medium cursor-not-allowed opacity-50',
                'bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500',
                index === 0 ? 'rounded-l-md' : '',
                index === links.length - 1 ? 'rounded-r-md' : '',
                'border border-gray-300 dark:border-gray-600'
              ]"
              v-html="link.label"
            />
          </template>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  links: {
    type: Array,
    required: true
  }
})

function getPaginationText() {
  // Extract current page info from links
  const activeLink = props.links.find(link => link.active)
  if (activeLink) {
    // Parse pagination info if available in meta
    return t('pagination.showing') || 'Showing results'
  }
  return ''
}
</script>
