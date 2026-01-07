<template>
  <div 
    class="p-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-primary)] shadow-sm transition-all hover:shadow-md"
    :class="{ 'opacity-50': loading }"
  >
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-[var(--color-text-muted)]">{{ title }}</p>
        <div v-if="!loading" class="mt-1 flex items-baseline gap-2">
          <h3 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ value }}</h3>
          <span v-if="trend" :class="trend > 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
            {{ trend > 0 ? '+' : '' }}{{ trend }}%
          </span>
        </div>
        <div v-else class="mt-2 w-16 h-8 bg-gray-200 dark:bg-gray-700 animate-pulse rounded"></div>
      </div>
      <div v-if="icon" class="w-10 h-10 rounded-lg bg-[var(--color-bg-tertiary)] flex items-center justify-center text-[var(--color-text-primary)]">
        <slot name="icon">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
          </svg>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  icon?: string
  trend?: number
  loading?: boolean
}>()
</script>
