<template>
  <Teleport to="body">
    <Transition name="drawer-backdrop">
      <div 
        v-if="open" 
        class="fixed inset-0 bg-black/50 z-40"
        @click="$emit('close')"
      />
    </Transition>
    <Transition :name="drawerTransition">
      <div 
        v-if="open"
        ref="drawerRef"
        class="fixed top-0 bottom-0 z-50 w-full sm:max-w-[480px] bg-[var(--color-bg-primary)] shadow-2xl flex flex-col overflow-hidden border-x border-white/10"
        :class="drawerPositionClass"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="event ? `drawer-title-${event.id}` : undefined"
        @keydown.escape="$emit('close')"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-[var(--color-border)]">
          <div class="flex items-center gap-3">
            <div 
              class="w-10 h-10 rounded-full flex items-center justify-center"
              :class="iconClass"
            >
              <Icon :name="iconName" size="md" />
            </div>
            <div>
              <h2 
                :id="event ? `drawer-title-${event.id}` : undefined"
                class="text-lg font-semibold text-[var(--color-text-primary)]"
              >
                {{ $t('audit.detail.title') }}
              </h2>
              <p class="text-sm text-[var(--color-text-muted)]">
                {{ exactTime }}
              </p>
            </div>
          </div>
          <button 
            @click="$emit('close')"
            class="p-2 rounded-lg hover:bg-[var(--color-bg-tertiary)] transition-colors"
            :aria-label="$t('common.close')"
          >
            <Icon name="x" size="md" />
          </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            class="flex-1 px-4 py-3 text-sm font-medium transition-colors relative"
            :class="activeTab === tab.id 
              ? 'text-primary-600 dark:text-primary-400' 
              : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
          >
            {{ tab.label }}
            <div 
              v-if="activeTab === tab.id"
              class="absolute bottom-0 inset-x-0 h-0.5 bg-primary-600 dark:bg-primary-400"
            />
          </button>
        </div>

        <!-- Tab Content -->
        <div class="flex-1 overflow-y-auto p-4">
          <!-- Summary Tab -->
          <div v-if="activeTab === 'summary'" class="space-y-4">
            <!-- Event Type Badge -->
            <div class="flex items-center gap-2">
              <span 
                class="px-3 py-1 rounded-full text-sm font-medium"
                :class="badgeClass"
              >
                {{ $t(`audit.events.${event?.event_type}`) }}
              </span>
            </div>

            <!-- Summary Text -->
            <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4">
              <p class="text-[var(--color-text-primary)]">
                {{ summaryText }}
              </p>
            </div>

            <!-- Actor Info -->
            <div class="space-y-2">
              <h3 class="text-sm font-medium text-[var(--color-text-muted)]">{{ $t('audit.by') }}</h3>
              <div class="flex items-center gap-3 bg-[var(--color-bg-secondary)] rounded-lg p-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                  <Icon name="user" size="md" class="text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                  <p class="font-medium text-[var(--color-text-primary)]">{{ event?.actor?.name || $t('audit.system') }}</p>
                  <p v-if="event?.actor?.role" class="text-sm text-[var(--color-text-muted)]">
                    {{ $t(`roles.${event.actor.role}`) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Ticket Info -->
            <div v-if="event?.ticket" class="space-y-2">
              <h3 class="text-sm font-medium text-[var(--color-text-muted)]">{{ $t('audit.target') }}</h3>
              <NuxtLink 
                :to="`/admin/tickets/${event.ticket.id}`"
                class="block bg-[var(--color-bg-secondary)] rounded-lg p-3 hover:bg-[var(--color-bg-tertiary)] transition-colors"
                @click="$emit('close')"
              >
                <p class="font-medium text-primary-600 dark:text-primary-400">{{ event.ticket.number }}</p>
                <p class="text-sm text-[var(--color-text-primary)]">{{ event.ticket.subject }}</p>
                <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ departmentName }}</p>
              </NuxtLink>
            </div>
          </div>

          <!-- Changes Tab -->
          <div v-else-if="activeTab === 'changes'" class="space-y-4">
            <div v-if="hasChanges" class="space-y-3">
              <!-- Status Change -->
              <div 
                v-if="event?.old_value || event?.new_value"
                class="bg-[var(--color-bg-secondary)] rounded-lg overflow-hidden"
              >
                <div class="px-4 py-2 bg-[var(--color-bg-tertiary)] text-sm font-medium text-[var(--color-text-muted)]">
                  {{ $t('audit.detail.fieldChanged', { field: 'Status' }) }}
                </div>
                <div class="p-4 flex items-center gap-4">
                  <div class="flex-1">
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">{{ $t('audit.detail.from') }}</p>
                    <span 
                      v-if="event?.old_value?.status"
                      class="px-2 py-1 rounded text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300"
                    >
                      {{ $t(`ticketStatus.${event.old_value.status}`) }}
                    </span>
                    <span v-else class="text-[var(--color-text-muted)]">—</span>
                  </div>
                  <Icon name="arrow-right" :class="{ 'rotate-180': isRtl }" class="text-[var(--color-text-muted)]" />
                  <div class="flex-1 text-end">
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">{{ $t('audit.detail.to') }}</p>
                    <span 
                      v-if="event?.new_value?.status"
                      class="px-2 py-1 rounded text-sm bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300"
                    >
                      {{ $t(`ticketStatus.${event.new_value.status}`) }}
                    </span>
                    <span v-else class="text-[var(--color-text-muted)]">—</span>
                  </div>
                </div>
              </div>

              <!-- Priority/Assignment Changes from meta -->
              <div 
                v-if="event?.meta?.from && event?.meta?.to && event?.event_type === 'priority_changed'"
                class="bg-[var(--color-bg-secondary)] rounded-lg overflow-hidden"
              >
                <div class="px-4 py-2 bg-[var(--color-bg-tertiary)] text-sm font-medium text-[var(--color-text-muted)]">
                  {{ $t('audit.detail.fieldChanged', { field: 'Priority' }) }}
                </div>
                <div class="p-4 flex items-center gap-4">
                  <div class="flex-1">
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">{{ $t('audit.detail.from') }}</p>
                    <span class="px-2 py-1 rounded text-sm bg-[var(--color-bg-tertiary)]">
                      {{ $t(`priority.${event.meta.from}`) }}
                    </span>
                  </div>
                  <Icon name="arrow-right" :class="{ 'rotate-180': isRtl }" class="text-[var(--color-text-muted)]" />
                  <div class="flex-1 text-end">
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">{{ $t('audit.detail.to') }}</p>
                    <span class="px-2 py-1 rounded text-sm bg-[var(--color-bg-tertiary)]">
                      {{ $t(`priority.${event.meta.to}`) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Assignment -->
              <div 
                v-if="event?.meta?.assigned_to && event?.event_type === 'assigned'"
                class="bg-[var(--color-bg-secondary)] rounded-lg p-4"
              >
                <p class="text-sm text-[var(--color-text-muted)] mb-1">{{ $t('audit.assignedTo') }}</p>
                <p class="font-medium text-[var(--color-text-primary)]">{{ event.meta.assigned_to }}</p>
              </div>

              <!-- Note Added -->
              <div 
                v-if="event?.meta?.note && event?.event_type === 'note_added'"
                class="bg-[var(--color-bg-secondary)] rounded-lg p-4"
              >
                <p class="text-sm text-[var(--color-text-muted)] mb-2">{{ $t('tickets.notes') }}</p>
                <p class="text-[var(--color-text-primary)] whitespace-pre-wrap">{{ event.meta.note }}</p>
              </div>
            </div>

            <!-- No Changes -->
            <div v-else class="text-center py-8 text-[var(--color-text-muted)]">
              <Icon name="check-circle" size="xl" class="mx-auto mb-3 opacity-50" />
              <p>{{ $t('audit.detail.noChanges') }}</p>
            </div>
          </div>

          <!-- Raw JSON Tab -->
          <div v-else-if="activeTab === 'raw'" class="space-y-4">
            <div class="bg-[var(--color-bg-tertiary)] rounded-lg p-4 overflow-x-auto">
              <pre class="text-xs text-[var(--color-text-secondary)] font-mono whitespace-pre-wrap">{{ formattedJson }}</pre>
            </div>
          </div>

          <!-- Metadata Tab -->
          <div v-else-if="activeTab === 'metadata'" class="space-y-4">
            <!-- Check if any metadata exists -->
            <template v-if="hasMetadata">
              <!-- IP Address -->
              <div v-if="event?.ip_address" class="bg-[var(--color-bg-secondary)] rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                  <Icon name="globe" size="sm" class="text-[var(--color-text-muted)]" />
                  <span class="text-sm font-medium text-[var(--color-text-muted)]">{{ $t('audit.metadata.ipAddress') }}</span>
                </div>
                <p class="font-mono text-[var(--color-text-primary)]">{{ event.ip_address }}</p>
              </div>

              <!-- User Agent -->
              <div v-if="event?.user_agent" class="bg-[var(--color-bg-secondary)] rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                  <Icon name="device-desktop" size="sm" class="text-[var(--color-text-muted)]" />
                  <span class="text-sm font-medium text-[var(--color-text-muted)]">{{ $t('audit.metadata.userAgent') }}</span>
                </div>
                <p class="text-sm text-[var(--color-text-primary)] break-all">{{ event.user_agent }}</p>
                <p v-if="parsedUserAgent" class="text-xs text-[var(--color-text-muted)] mt-1">
                  {{ parsedUserAgent }}
                </p>
              </div>

              <!-- Request Details -->
              <div class="bg-[var(--color-bg-secondary)] rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                  <Icon name="code" size="sm" class="text-[var(--color-text-muted)]" />
                  <span class="text-sm font-medium text-[var(--color-text-muted)]">{{ $t('audit.metadata.requestDetails') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div v-if="event?.method">
                    <p class="text-xs text-[var(--color-text-muted)]">{{ $t('audit.metadata.method') }}</p>
                    <span 
                      class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                      :class="methodClass"
                    >
                      {{ event.method }}
                    </span>
                  </div>
                  <div v-if="event?.route">
                    <p class="text-xs text-[var(--color-text-muted)]">{{ $t('audit.metadata.route') }}</p>
                    <p class="font-mono text-[var(--color-text-primary)]">{{ event.route }}</p>
                  </div>
                </div>
              </div>

              <!-- Request ID -->
              <div v-if="event?.request_id" class="bg-[var(--color-bg-secondary)] rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                  <Icon name="fingerprint" size="sm" class="text-[var(--color-text-muted)]" />
                  <span class="text-sm font-medium text-[var(--color-text-muted)]">{{ $t('audit.metadata.requestId') }}</span>
                </div>
                <p class="font-mono text-xs text-[var(--color-text-primary)] break-all">{{ event.request_id }}</p>
                <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ $t('audit.metadata.requestIdHint') }}</p>
              </div>
            </template>

            <!-- No Metadata Available -->
            <div v-else class="text-center py-8 text-[var(--color-text-muted)]">
              <Icon name="code" size="xl" class="mx-auto mb-3 opacity-50" />
              <p class="font-medium mb-1">{{ $t('audit.metadata.noData') }}</p>
              <p class="text-sm">{{ $t('audit.metadata.noDataHint') }}</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
          <div class="flex gap-3">
            <button 
              v-if="event?.ticket"
              @click="navigateToTicket"
              class="flex-1 btn-primary py-2.5"
            >
              <Icon name="external-link" size="sm" class="me-2" />
              {{ $t('audit.viewTicket') }}
            </button>
            <button 
              @click="$emit('close')"
              class="flex-1 btn-ghost py-2.5"
            >
              {{ $t('common.close') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import type { AuditEvent } from '~/composables/useAuditLog'

const props = defineProps<{
  event: AuditEvent | null
  open: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const { locale, t } = useI18n()
const router = useRouter()
const drawerRef = ref<HTMLElement | null>(null)

const isRtl = computed(() => locale.value === 'ar')

// Drawer position based on RTL
const drawerPositionClass = computed(() => isRtl.value ? 'left-0' : 'right-0')
const drawerTransition = computed(() => isRtl.value ? 'drawer-slide-left' : 'drawer-slide-right')

// Tabs
const tabs = computed(() => [
  { id: 'summary', label: t('audit.detail.summaryTab') },
  { id: 'changes', label: t('audit.detail.changesTab') },
  { id: 'raw', label: t('audit.detail.rawTab') },
  { id: 'metadata', label: t('audit.detail.metadataTab') },
])
const activeTab = ref('summary')

// Reset tab when drawer opens
watch(() => props.open, (isOpen) => {
  if (isOpen) activeTab.value = 'summary'
})

// Focus trap
watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    drawerRef.value?.focus()
  }
})

// Computed helpers
const exactTime = computed(() => {
  if (!props.event) return ''
  return new Date(props.event.created_at).toLocaleString(
    locale.value === 'ar' ? 'ar-SA' : 'en-US',
    { dateStyle: 'full', timeStyle: 'medium' }
  )
})

const departmentName = computed(() => {
  if (!props.event?.ticket) return null
  return locale.value === 'ar' 
    ? props.event.ticket.department_name_ar 
    : props.event.ticket.department_name_en
})

const hasChanges = computed(() => {
  const e = props.event
  if (!e) return false
  return !!(
    e.old_value || 
    e.new_value || 
    e.meta?.from || 
    e.meta?.to || 
    e.meta?.assigned_to ||
    e.meta?.note
  )
})

const summaryText = computed(() => {
  if (!props.event) return ''
  // Use the summary_key and summary_params for i18n interpolation
  // Fallback to simple event type translation if summary_key is missing
  const key = props.event.summary_key
  const params = props.event.summary_params || {}
  if (!key) {
    return t(`audit.events.${props.event.event_type}`)
  }
  try {
    return t(key, params)
  } catch {
    return t(`audit.events.${props.event.event_type}`)
  }
})

const formattedJson = computed(() => {
  if (!props.event) return ''
  return JSON.stringify(props.event, null, 2)
})

// Metadata tab computed properties
const hasMetadata = computed(() => {
  if (!props.event) return false
  return !!(props.event.ip_address || props.event.user_agent || props.event.route || props.event.method || props.event.request_id)
})

const methodClass = computed(() => {
  const method = props.event?.method?.toUpperCase()
  const classes: Record<string, string> = {
    GET: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    POST: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    PUT: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    PATCH: 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300',
    DELETE: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    CLI: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
  }
  return classes[method || ''] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
})

const parsedUserAgent = computed(() => {
  const ua = props.event?.user_agent
  if (!ua) return null
  
  // Simple browser/OS detection
  let browser = 'Unknown'
  let os = 'Unknown'
  
  if (ua.includes('Firefox')) browser = 'Firefox'
  else if (ua.includes('Chrome') && !ua.includes('Edg')) browser = 'Chrome'
  else if (ua.includes('Safari') && !ua.includes('Chrome')) browser = 'Safari'
  else if (ua.includes('Edg')) browser = 'Edge'
  else if (ua.includes('CLI')) browser = 'CLI'
  
  if (ua.includes('Windows')) os = 'Windows'
  else if (ua.includes('Mac OS')) os = 'macOS'
  else if (ua.includes('Linux')) os = 'Linux'
  else if (ua.includes('Android')) os = 'Android'
  else if (ua.includes('iOS') || ua.includes('iPhone')) os = 'iOS'
  
  return `${browser} on ${os}`
})

// Icon and badge classes
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
  return icons[props.event?.event_type || ''] || 'cog'
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
  return classes[props.event?.event_type || ''] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
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
  return classes[props.event?.event_type || ''] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
})

// Actions
const navigateToTicket = () => {
  if (props.event?.ticket) {
    router.push(`/admin/tickets/${props.event.ticket.id}`)
    emit('close')
  }
}
</script>

<style scoped>
/* Backdrop */
.drawer-backdrop-enter-active,
.drawer-backdrop-leave-active {
  transition: opacity 0.3s ease;
}
.drawer-backdrop-enter-from,
.drawer-backdrop-leave-to {
  opacity: 0;
}

/* Drawer slide from right (LTR) */
.drawer-slide-right-enter-active,
.drawer-slide-right-leave-active {
  transition: transform 0.3s ease;
}
.drawer-slide-right-enter-from,
.drawer-slide-right-leave-to {
  transform: translateX(100%);
}

/* Drawer slide from left (RTL) */
.drawer-slide-left-enter-active,
.drawer-slide-left-leave-active {
  transition: transform 0.3s ease;
}
.drawer-slide-left-enter-from,
.drawer-slide-left-leave-to {
  transform: translateX(-100%);
}
</style>
