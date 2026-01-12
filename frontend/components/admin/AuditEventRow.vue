<template>
  <div 
    class="flex items-start gap-3 p-3 sm:p-4 hover:bg-[var(--color-bg-tertiary)] transition-colors cursor-pointer group"
    @click="$emit('click')"
    role="button"
    tabindex="0"
    @keydown.enter="$emit('click')"
    @keydown.space.prevent="$emit('click')"
  >
    <!-- Action Icon -->
    <div 
      class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
      :class="iconClass"
    >
      <Icon :name="iconName" size="md" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <!-- Summary Line -->
      <div class="flex flex-wrap items-center gap-2 mb-0.5">
        <span 
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
          :class="badgeClass"
        >
          {{ $t(`audit.events.${event.event_type}`) }}
        </span>
        <span v-if="hasDiff" class="text-xs text-purple-600 dark:text-purple-400">
          <Icon name="code" size="xs" class="me-0.5" />
          {{ $t('audit.hasDiff') }}
        </span>
      </div>

      <!-- Actor + Summary -->
      <p class="text-sm text-[var(--color-text-primary)] line-clamp-1">
        <span class="font-medium">{{ actorName }}</span>
        <span 
          v-if="actorRole" 
          class="ms-1 text-xs px-1.5 py-0.5 rounded bg-[var(--color-bg-tertiary)] text-[var(--color-text-muted)]"
        >
          {{ $t(`roles.${actorRole}`) }}
        </span>
      </p>

      <!-- Target: Ticket + Department + Subject -->
      <p class="text-sm text-[var(--color-text-muted)] mt-0.5 truncate">
        <NuxtLink 
          v-if="event.ticket"
          :to="`/admin/tickets/${event.ticket.id}`"
          class="font-medium text-primary-600 hover:underline"
          @click.stop
        >
          {{ event.ticket.number }}
        </NuxtLink>
        <template v-if="departmentName">
          <span class="mx-1.5">•</span>
          <span>{{ departmentName }}</span>
        </template>
        <template v-if="event.ticket?.subject">
          <span class="mx-1.5">–</span>
          <span class="truncate">{{ event.ticket.subject }}</span>
        </template>
      </p>

      <!-- Change Preview (for status_changed) -->
      <div 
        v-if="event.event_type === 'status_changed' && event.old_value && event.new_value" 
        class="mt-1.5 inline-flex items-center gap-1.5 text-xs bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 px-2 py-1 rounded"
      >
        <Icon name="refresh" size="xs" />
        <span>{{ $t('ticketStatus.' + event.old_value.status) }}</span>
        <Icon name="arrow-right" size="xs" />
        <span class="font-medium">{{ $t('ticketStatus.' + event.new_value.status) }}</span>
      </div>
    </div>

    <!-- Time + Action -->
    <div class="text-end shrink-0">
      <time 
        :datetime="event.created_at" 
        :title="exactTime"
        class="text-xs text-[var(--color-text-muted)]"
      >
        {{ relativeTime }}
      </time>
      <div class="mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <Icon 
          name="chevron-right" 
          size="sm" 
          class="text-[var(--color-text-muted)]"
          :class="{ 'rotate-180': isRtl }"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AuditEvent } from '~/composables/useAuditLog'

const props = defineProps<{
  event: AuditEvent
}>()

defineEmits<{
  click: []
}>()

const { locale, t } = useI18n()
const isRtl = computed(() => locale.value === 'ar')

// Actor info
const actorName = computed(() => props.event.actor?.name || t('audit.system'))
const actorRole = computed(() => props.event.actor?.role)

// Department name (locale-aware)
const departmentName = computed(() => {
  if (!props.event.ticket) return null
  return locale.value === 'ar' 
    ? props.event.ticket.department_name_ar 
    : props.event.ticket.department_name_en
})

// Has diff indicator
const hasDiff = computed(() => !!(props.event.old_value || props.event.new_value))

// Time formatting
const relativeTime = computed(() => {
  const date = new Date(props.event.created_at)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) return t('common.justNow') || 'Just now'
  if (diffMins < 60) return `${diffMins}m`
  if (diffHours < 24) return `${diffHours}h`
  if (diffDays < 7) return `${diffDays}d`
  
  return date.toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
  })
})

const exactTime = computed(() => {
  return new Date(props.event.created_at).toLocaleString(
    locale.value === 'ar' ? 'ar-SA' : 'en-US',
    { dateStyle: 'medium', timeStyle: 'short' }
  )
})

// Visual styling by event type
const iconName = computed(() => {
  const icons: Record<string, string> = {
    created: 'plus-circle',
    accepted: 'check',
    started: 'play',
    completed: 'check-circle',
    status_changed: 'refresh',
    note_added: 'comment',
    priority_changed: 'flag',
    assigned: 'user-plus',
    cancelled: 'x-circle',
  }
  return icons[props.event.event_type] || 'cog'
})

const iconClass = computed(() => {
  const classes: Record<string, string> = {
    created: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    accepted: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    started: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    completed: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    status_changed: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    note_added: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    priority_changed: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
    assigned: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
  }
  return classes[props.event.event_type] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
})

const badgeClass = computed(() => {
  const classes: Record<string, string> = {
    created: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    accepted: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    started: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    completed: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
    status_changed: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
    note_added: 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
    priority_changed: 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
    assigned: 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300',
    cancelled: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
  }
  return classes[props.event.event_type] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
})
</script>
