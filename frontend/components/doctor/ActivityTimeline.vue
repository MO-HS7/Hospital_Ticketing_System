<template>
  <div class="activity-timeline card p-5">
    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      {{ $t('doctorPortal.activityTimeline') }}
    </h3>

    <div v-if="timelineItems.length === 0" class="text-sm text-[var(--color-text-muted)] text-center py-4">
      {{ $t('doctorPortal.noActivity') }}
    </div>

    <div v-else class="relative">
      <!-- Timeline line -->
      <div class="absolute start-4 top-0 bottom-0 w-0.5 bg-[var(--color-border)]"></div>

      <!-- Timeline items -->
      <div class="space-y-4">
        <div 
          v-for="(item, index) in timelineItems" 
          :key="index"
          class="relative flex gap-4 ps-10"
        >
          <!-- Icon -->
          <div 
            class="absolute start-0 w-8 h-8 rounded-full flex items-center justify-center"
            :class="item.iconClass"
          >
            <component :is="getIcon(item.type)" class="w-4 h-4" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-medium text-[var(--color-text-primary)]">{{ item.title }}</p>
                <p v-if="item.actor" class="text-xs text-[var(--color-text-muted)]">{{ item.actor }}</p>
              </div>
              <span class="text-xs text-[var(--color-text-muted)] shrink-0">{{ item.time }}</span>
            </div>
            <p v-if="item.details" class="text-sm text-[var(--color-text-secondary)] mt-1">{{ item.details }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { h } from 'vue'

interface TicketEvent {
  id: number
  event_type: string
  meta?: Record<string, any> | null
  created_at: string
  user?: { name?: string } | null
}

const props = defineProps<{
  events: TicketEvent[]
  createdAt?: string // Keep for fallback but not used for event generation
}>()

const { t, locale } = useI18n()

const formatTime = (dateStr: string) => {
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getEventTitle = (event: TicketEvent) => {
  const type = event.event_type
  
  // Translate known event types
  const eventTypeMap: Record<string, string> = {
    'status_changed': t('doctorPortal.statusChanged'),
    'order_created': t('doctorPortal.orderCreated'),
    'referral_created': t('doctorPortal.referralCreated'),
    'note_added': t('doctorPortal.noteAdded'),
    'created': t('doctorPortal.ticketCreated'),
    'started': t('doctorPortal.processingStarted'),
    'completed': t('doctorPortal.ticketCompleted'),
    'accepted': t('doctorPortal.ticketAccepted'),
    'assigned': t('doctorPortal.ticketAssigned'),
  }
  
  if (type === 'status_changed') {
    const from = event.meta?.from
    const to = event.meta?.to
    if (from && to) {
      return `${t('doctorPortal.statusChanged')}: ${from} → ${to}`
    }
  }
  
  return eventTypeMap[type] || type.replace(/_/g, ' ')
}

const getEventIconClass = (type: string) => {
  if (type === 'status_changed') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-600'
  if (type === 'order_created') return 'bg-purple-100 dark:bg-purple-900/30 text-purple-600'
  if (type === 'referral_created') return 'bg-orange-100 dark:bg-orange-900/30 text-orange-600'
  if (type === 'note_added') return 'bg-gray-100 dark:bg-gray-800 text-gray-600'
  return 'bg-primary-100 dark:bg-primary-900/30 text-primary-600'
}

interface TimelineItem {
  type: string
  title: string
  actor?: string
  time: string
  rawTime?: string
  details?: string
  iconClass: string
}

const timelineItems = computed<TimelineItem[]>(() => {
  // Use ONLY API events as single source of truth - no manual duplicates
  const items: TimelineItem[] = props.events.map(event => ({
    type: event.event_type,
    title: getEventTitle(event),
    actor: event.user?.name,
    time: formatTime(event.created_at),
    rawTime: event.created_at, // Keep raw time for sorting
    details: event.meta?.details || undefined,
    iconClass: getEventIconClass(event.event_type),
  }))

  // Sort by time ascending (oldest first = proper timeline order)
  items.sort((a, b) => {
    return new Date(a.rawTime || a.time).getTime() - new Date(b.rawTime || b.time).getTime()
  })

  return items
})

// Icon component helper
const getIcon = (type: string) => {
  return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    type === 'created' || type === 'completed' 
      ? h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 13l4 4L19 7' })
      : type === 'started'
      ? h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z' })
      : type === 'order_created'
      ? h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' })
      : type === 'referral_created'
      ? h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' })
      : h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
  ])
}
</script>
