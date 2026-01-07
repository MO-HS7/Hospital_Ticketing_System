<template>
  <span 
    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border"
    :class="classes"
  >
    <span v-if="dot" class="w-1.5 h-1.5 rounded-full mr-1.5 rtl:mr-0 rtl:ml-1.5" :class="dotColor"></span>
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  label?: string
  color?: 'primary' | 'success' | 'warning' | 'danger' | 'gray'
  variant?: 'solid' | 'soft' | 'outline'
  dot?: boolean
}>(), {
  color: 'gray',
  variant: 'soft',
  dot: false
})

const classes = computed(() => {
  const c = props.color
  const v = props.variant
  
  if (v === 'solid') {
    switch (c) {
      case 'primary': return 'bg-primary-600 text-white border-transparent'
      case 'success': return 'bg-green-600 text-white border-transparent'
      case 'warning': return 'bg-amber-500 text-white border-transparent'
      case 'danger': return 'bg-red-600 text-white border-transparent'
      default: return 'bg-gray-600 text-white border-transparent'
    }
  } else if (v === 'outline') {
    switch (c) {
      case 'primary': return 'text-primary-700 bg-transparent border-primary-600'
      case 'success': return 'text-green-700 bg-transparent border-green-600'
      case 'warning': return 'text-amber-700 bg-transparent border-amber-600'
      case 'danger': return 'text-red-700 bg-transparent border-red-600'
      default: return 'text-gray-700 bg-transparent border-gray-600'
    }
  } else { // soft (default)
    switch (c) {
      case 'primary': return 'bg-primary-50 text-primary-700 border-primary-200 dark:bg-primary-900/30 dark:text-primary-400 dark:border-primary-800'
      case 'success': return 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
      case 'warning': return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800'
      case 'danger': return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'
      default: return 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'
    }
  }
})

const dotColor = computed(() => {
  switch (props.color) {
    case 'primary': return 'bg-primary-500'
    case 'success': return 'bg-green-500'
    case 'warning': return 'bg-amber-500'
    case 'danger': return 'bg-red-500'
    default: return 'bg-gray-500'
  }
})
</script>
