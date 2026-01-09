<template>
  <div
    class="insight-card card p-4 cursor-pointer transition-all duration-200 hover:shadow-lg hover:scale-[1.02] group"
    :class="severityClasses"
    @click="$emit('click')"
    @mouseenter="showTooltip = true"
    @mouseleave="showTooltip = false"
  >
    <div class="flex items-start gap-3">
      <!-- Icon -->
      <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center" :class="iconBgClass">
        <component :is="iconComponent" class="w-6 h-6" :class="iconColorClass" />
      </div>
      
      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="text-2xl font-bold" :class="countColorClass">{{ count }}</span>
          <SourceBadge v-if="sourceBreakdown" :manual="sourceBreakdown.manual" :chatbot="sourceBreakdown.chatbot" size="sm" />
        </div>
        <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">{{ title }}</p>
        <p class="text-xs text-[var(--color-text-muted)] mt-0.5">{{ subtitle }}</p>
      </div>
      
      <!-- Arrow indicator -->
      <div class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
        <svg class="w-5 h-5 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </div>
    </div>
    
    <!-- Hover Tooltip -->
    <Transition name="fade">
      <div 
        v-if="showTooltip && tooltipContent" 
        class="absolute z-50 top-full start-0 mt-2 p-3 bg-[var(--color-bg-primary)] border border-[var(--color-border)] rounded-lg shadow-xl min-w-[200px]"
      >
        <div class="text-sm space-y-1.5">
          <slot name="tooltip">
            <div v-for="(item, key) in tooltipContent" :key="key" class="flex justify-between gap-4">
              <span class="text-[var(--color-text-muted)]">{{ item.label }}</span>
              <span class="font-medium">{{ item.value }}</span>
            </div>
          </slot>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import SourceBadge from './SourceBadge.vue'

interface TooltipItem {
  label: string
  value: string | number
}

interface SourceBreakdown {
  manual: number
  chatbot: number
}

type Severity = 'critical' | 'warning' | 'success' | 'info'

interface Props {
  count: number | string
  title: string
  subtitle: string
  severity?: Severity
  icon?: 'alert' | 'clock' | 'check' | 'chart' | 'users' | 'building'
  sourceBreakdown?: SourceBreakdown
  tooltipContent?: Record<string, TooltipItem>
}

const props = withDefaults(defineProps<Props>(), {
  severity: 'info',
  icon: 'chart',
})

defineEmits<{
  click: []
}>()

const showTooltip = ref(false)

const severityClasses = computed(() => ({
  'ring-2 ring-red-500/20': props.severity === 'critical',
  'ring-2 ring-amber-500/20': props.severity === 'warning',
  'ring-2 ring-green-500/20': props.severity === 'success',
  'ring-2 ring-blue-500/20': props.severity === 'info',
}))

const iconBgClass = computed(() => ({
  'bg-red-100 dark:bg-red-900/30': props.severity === 'critical',
  'bg-amber-100 dark:bg-amber-900/30': props.severity === 'warning',
  'bg-green-100 dark:bg-green-900/30': props.severity === 'success',
  'bg-blue-100 dark:bg-blue-900/30': props.severity === 'info',
}))

const iconColorClass = computed(() => ({
  'text-red-600 dark:text-red-400': props.severity === 'critical',
  'text-amber-600 dark:text-amber-400': props.severity === 'warning',
  'text-green-600 dark:text-green-400': props.severity === 'success',
  'text-blue-600 dark:text-blue-400': props.severity === 'info',
}))

const countColorClass = computed(() => ({
  'text-red-600 dark:text-red-400': props.severity === 'critical',
  'text-amber-600 dark:text-amber-400': props.severity === 'warning',
  'text-green-600 dark:text-green-400': props.severity === 'success',
  'text-blue-600 dark:text-blue-400': props.severity === 'info',
}))

// Icon components as inline SVGs
const AlertIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`
}
const ClockIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
}
const CheckIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
}
const ChartIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>`
}
const UsersIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>`
}
const BuildingIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>`
}

const iconComponent = computed(() => {
  switch (props.icon) {
    case 'alert': return AlertIcon
    case 'clock': return ClockIcon
    case 'check': return CheckIcon
    case 'users': return UsersIcon
    case 'building': return BuildingIcon
    default: return ChartIcon
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.insight-card {
  position: relative;
}
</style>
