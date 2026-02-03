import { ref, computed } from 'vue'

// Immediate show/hide with reference counting
const activeCount = ref(0)
const globalLoading = computed(() => activeCount.value > 0)

function showGlobalLoading() {
  activeCount.value++
}

function hideGlobalLoading() {
  if (activeCount.value > 0) activeCount.value--
}

export function useGlobalLoading() {
  return { globalLoading, showGlobalLoading, hideGlobalLoading }
}
