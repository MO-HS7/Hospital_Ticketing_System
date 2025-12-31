<template>
  <div class="flex flex-col items-center justify-center py-12 px-4">
    <div class="relative">
      <!-- Spinner -->
      <div class="w-12 h-12 border-4 border-primary-200 dark:border-primary-900 rounded-full animate-spin border-t-primary-600 dark:border-t-primary-400" />
    </div>
    
    <!-- Text -->
    <p 
      v-if="text" 
      class="mt-4 text-sm text-[var(--color-text-muted)]"
    >
      {{ text }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  text?: string
}

defineProps<Props>()
</script>
