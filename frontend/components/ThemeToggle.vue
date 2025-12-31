<template>
  <button
    class="btn-ghost p-2 rounded-lg"
    :title="$t(`theme.${nextMode}`)"
    @click="toggleTheme"
  >
    <!-- Sun icon (light mode) -->
    <svg 
      v-if="colorMode.value === 'light'" 
      class="w-5 h-5 text-amber-500" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    
    <!-- Moon icon (dark mode) -->
    <svg 
      v-else-if="colorMode.value === 'dark'" 
      class="w-5 h-5 text-primary-400" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
    
    <!-- System icon -->
    <svg 
      v-else 
      class="w-5 h-5 text-[var(--color-text-secondary)]" 
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    </svg>
  </button>
</template>

<script setup lang="ts">
const colorMode = useColorMode()

const nextMode = computed(() => {
  if (colorMode.value === 'light') return 'dark'
  if (colorMode.value === 'dark') return 'system'
  return 'light'
})

const toggleTheme = () => {
  colorMode.preference = nextMode.value
}
</script>
