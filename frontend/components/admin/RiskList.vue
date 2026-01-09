<template>
  <div class="risk-list">
    <div class="space-y-2">
      <div 
        v-for="item in items" 
        :key="item.id"
        class="flex items-center gap-3 p-3 rounded-lg bg-[var(--color-bg-secondary)] hover:bg-[var(--color-bg-tertiary)] cursor-pointer transition-colors"
        @click="$emit('item-click', item)"
      >
        <!-- Risk indicator -->
        <div class="shrink-0 w-2 h-8 rounded-full" :class="getRiskColor(item.riskLevel)" />
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-medium text-sm truncate">{{ item.title }}</span>
            <SLAIndicator 
              v-if="item.deadline" 
              :deadline="item.deadline" 
              :created-at="item.createdAt"
              class="hidden sm:flex"
            />
          </div>
          <p class="text-xs text-[var(--color-text-muted)] truncate">{{ item.subtitle }}</p>
        </div>
        
        <!-- Count badge (optional) -->
        <span v-if="item.count" class="shrink-0 px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
          {{ item.count }}
        </span>
        
        <!-- Arrow -->
        <svg class="shrink-0 w-4 h-4 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </div>
      
      <!-- Empty state -->
      <div v-if="items.length === 0" class="text-center py-6 text-[var(--color-text-muted)]">
        <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm">{{ emptyText || $t('commandCenter.noRisks') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import SLAIndicator from './SLAIndicator.vue'

interface RiskItem {
  id: string | number
  title: string
  subtitle: string
  riskLevel: 'critical' | 'warning' | 'normal'
  deadline?: string
  createdAt?: string
  count?: number
}

interface Props {
  items: RiskItem[]
  emptyText?: string
}

defineProps<Props>()

defineEmits<{
  'item-click': [item: RiskItem]
}>()

const getRiskColor = (level: string) => ({
  'bg-red-500': level === 'critical',
  'bg-amber-500': level === 'warning',
  'bg-green-500': level === 'normal',
})
</script>
