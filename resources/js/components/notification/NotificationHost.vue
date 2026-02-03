<template>
  <teleport to="body">
    <transition-group
      name="toast"
      tag="div"
      :class="containerClass"
      :style="{ zIndex: zIndex }"
    >
      <Notification
        v-for="n in limitedNotifications"
        :key="n.id"
        :type="n.type"
        :message="n.message"
        class="pointer-events-auto"
        @close="removeNotification(n.id)"
      />
    </transition-group>
  </teleport>
  
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useNotifications } from '@/composables/useNotifications'
import Notification from '@/components/notification/Notification.vue'

const props = withDefaults(defineProps<{
  // Logical positions adapt to LTR/RTL automatically via tailwindcss-rtl
  // Backwards-compatible with physical positions
  position?: 'top-start'|'top-end'|'bottom-start'|'bottom-end'|'top-left'|'top-right'|'bottom-left'|'bottom-right'
  zIndex?: number
  max?: number
}>(), {
  // Default to "end" so toasts appear opposite the sidebar (which sits on inline-start)
  position: 'top-end',
  zIndex: 200000,
  max: 3,
})

const { notifications, removeNotification } = useNotifications()

const containerClass = computed(() => {
  // Physical fallbacks
  const physical: Record<string, string> = {
    'top-left': 'top-5 left-5',
    'top-right': 'top-5 right-5',
    'bottom-left': 'bottom-5 left-5',
    'bottom-right': 'bottom-5 right-5',
  }

  if (props.position in physical) {
    return `fixed ${physical[props.position as keyof typeof physical]} space-y-3 pointer-events-none`
  }

  // Logical, direction-aware positions using tailwindcss-rtl variants
  const logical: Record<string, string> = {
    'top-start': 'top-5 ltr:left-5 rtl:right-5',
    'top-end': 'top-5 ltr:right-5 rtl:left-5',
    'bottom-start': 'bottom-5 ltr:left-5 rtl:right-5',
    'bottom-end': 'bottom-5 ltr:right-5 rtl:left-5',
  }

  return `fixed ${logical[props.position]} space-y-3 pointer-events-none`
})

const limitedNotifications = computed(() => notifications.value.slice(-props.max))

// Expose for template style binding
const zIndex = props.zIndex
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
