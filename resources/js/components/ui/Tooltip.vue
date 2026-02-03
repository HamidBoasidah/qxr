<template>
  <span
    class="relative inline-flex group"
    :aria-describedby="shouldRender ? tooltipId : undefined"
  >
    <slot />

    <span
      v-if="shouldRender"
      :id="tooltipId"
      role="tooltip"
      :aria-hidden="ariaHidden"
      :class="tooltipClasses"
    >
      <slot name="content">
        {{ text }}
      </slot>
    </span>
  </span>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'

type TooltipPosition = 'top' | 'right' | 'bottom' | 'left'
type TooltipTheme = 'dark' | 'light'

interface TooltipProps {
  text?: string
  show?: boolean
  position?: TooltipPosition
  theme?: TooltipTheme
  interactive?: boolean
}

const POSITION_CLASS_MAP: Record<TooltipPosition, string> = {
  top: 'bottom-full left-1/2 -translate-x-1/2 translate-y-1 group-hover:translate-y-0 group-focus-within:translate-y-0 mb-2',
  right: 'left-full top-1/2 -translate-y-1/2 -translate-x-1 group-hover:translate-x-0 group-focus-within:translate-x-0 ml-3',
  bottom: 'top-full left-1/2 -translate-x-1/2 -translate-y-1 group-hover:translate-y-0 group-focus-within:translate-y-0 mt-2',
  left: 'right-full top-1/2 -translate-y-1/2 translate-x-1 group-hover:translate-x-0 group-focus-within:translate-x-0 mr-3'
}

const THEME_CLASS_MAP: Record<TooltipTheme, string> = {
  dark: 'bg-gray-900 text-white shadow-lg',
  light: 'bg-white text-gray-700 shadow-theme-xs border border-gray-200'
}

const props = withDefaults(defineProps<TooltipProps>(), {
  text: '',
  show: true,
  position: 'top',
  theme: 'dark',
  interactive: false
})

const slots = useSlots()

const tooltipId = `tooltip-${Math.random().toString(36).slice(2, 10)}`

const hasSlotContent = computed(() => Boolean(slots.content))

const shouldRender = computed(() => props.show && (Boolean(props.text) || hasSlotContent.value))

const positionClasses = computed(() => POSITION_CLASS_MAP[props.position] ?? POSITION_CLASS_MAP.top)

const themeClasses = computed(() => THEME_CLASS_MAP[props.theme] ?? THEME_CLASS_MAP.dark)

const pointerClass = computed(() => (props.interactive ? 'pointer-events-auto' : 'pointer-events-none'))

const tooltipClasses = computed(() => [
  'absolute z-50 whitespace-nowrap rounded-lg px-3 py-2 text-xs font-medium transition-all duration-150 ease-out opacity-0 transform',
  'group-hover:opacity-100 group-focus-within:opacity-100 focus-visible:opacity-100',
  'shadow-lg shadow-black/15 dark:shadow-black/25',
  positionClasses.value,
  themeClasses.value,
  pointerClass.value
])

const ariaHidden = computed(() => (props.show ? 'false' : 'true'))
</script>
