<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('audit.title') }}</h1>
        <p class="text-[var(--color-text-muted)]">{{ $t('audit.subtitle') }}</p>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-4">
        <select v-model="selectedEventType" class="input w-auto" @change="fetchEvents">
          <option value="">{{ $t('audit.allEvents') }}</option>
          <option value="created">{{ $t('audit.events.created') }}</option>
          <option value="accepted">{{ $t('audit.events.accepted') }}</option>
          <option value="completed">{{ $t('audit.events.completed') }}</option>
          <option value="status_changed">{{ $t('audit.events.status_changed') }}</option>
          <option value="note_added">{{ $t('audit.events.note_added') }}</option>
          <option value="priority_changed">{{ $t('audit.events.priority_changed') }}</option>
          <option value="assigned">{{ $t('audit.events.assigned') }}</option>
        </select>
      </div>

      <!-- Error -->
      <div v-if="error" class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">
        {{ error }}
      </div>

      <!-- Events List -->
      <div class="card overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
          <p class="mt-2 text-[var(--color-text-muted)]">{{ $t('common.loading') }}</p>
        </div>

        <div v-else-if="events.length === 0" class="p-8 text-center text-[var(--color-text-muted)]">
          {{ $t('common.noResults') }}
        </div>

        <div v-else class="divide-y divide-[var(--color-border)]">
          <div v-for="event in events" :key="event.id" class="p-4 flex items-start gap-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="getEventIconClass(event.event_type)">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="event.event_type === 'created'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                <path v-else-if="event.event_type === 'accepted'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                <path v-else-if="event.event_type === 'completed'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                <path v-else-if="event.event_type === 'note_added'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="badge" :class="getEventBadgeClass(event.event_type)">{{ $t(`audit.events.${event.event_type}`) }}</span>
                <span class="text-sm text-[var(--color-text-muted)]">{{ formatDate(event.created_at) }}</span>
              </div>
              <p class="text-sm text-[var(--color-text-primary)]">
                <span class="font-medium">{{ event.user?.name || $t('audit.system') }}</span>
                <span class="text-[var(--color-text-secondary)]"> {{ $t('audit.on') }} </span>
                <span class="font-medium">{{ $t('audit.ticket') }} #{{ event.ticket_id }}</span>
                <span v-if="event.ticket?.subject" class="text-[var(--color-text-muted)]"> — {{ event.ticket.subject }}</span>
              </p>
              <p v-if="event.ticket?.department" class="text-xs text-[var(--color-text-muted)] mt-1">
                {{ getDepartmentName(event.ticket.department) }} • {{ $t(`ticketType.${event.ticket.type}`) }}
              </p>
              <div v-if="event.meta && Object.keys(event.meta).length > 0" class="mt-2 text-xs bg-[var(--color-bg-tertiary)] rounded-lg p-2">
                <template v-if="event.event_type === 'status_changed'">
                  {{ event.meta.from }} → {{ event.meta.to }}
                </template>
                <template v-else-if="event.event_type === 'priority_changed'">
                  {{ event.meta.from }} → {{ event.meta.to }}
                </template>
                <template v-else>
                  <pre class="whitespace-pre-wrap text-[var(--color-text-muted)]">{{ JSON.stringify(event.meta, null, 2) }}</pre>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="p-4 border-t border-[var(--color-border)] flex items-center justify-between">
          <span class="text-sm text-[var(--color-text-muted)]">{{ $t('departments.showing') }} {{ events.length }} {{ $t('departments.of') }} {{ total }}</span>
          <div class="flex gap-2">
            <button @click="prevPage" :disabled="currentPage === 1" class="btn-ghost text-sm">{{ $t('common.previous') }}</button>
            <button @click="nextPage" :disabled="currentPage >= totalPages" class="btn-ghost text-sm">{{ $t('common.next') }}</button>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

interface EventUser { id: number; name: string; email: string }
interface EventDepartment { id: string; name_en: string; name_ar: string }
interface EventTicket { id: number; subject: string; type: string; department?: EventDepartment }
interface AuditEvent {
  id: number
  ticket_id: number
  user_id: number | null
  event_type: string
  meta: Record<string, any> | null
  created_at: string
  user?: EventUser | null
  ticket?: EventTicket | null
}

interface PaginatedEvents {
  data: AuditEvent[]
  current_page: number
  last_page: number
  total: number
}

const { locale } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()

const loading = ref(false)
const error = ref<string | null>(null)
const events = ref<AuditEvent[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const total = ref(0)
const selectedEventType = ref('')

const fetchEvents = async () => {
  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    params.set('page', String(currentPage.value))
    if (selectedEventType.value) params.set('event_type', selectedEventType.value)

    const res = await $fetch<PaginatedEvents>(`${config.public.apiBase}/admin/audit-log?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })

    events.value = res.data
    totalPages.value = res.last_page
    total.value = res.total
  } catch (e: any) {
    error.value = e.data?.message || e.message || 'Failed to fetch audit log'
  } finally {
    loading.value = false
  }
}

const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchEvents() } }
const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchEvents() } }

onMounted(() => fetchEvents())

const formatDate = (value: string) => {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString(locale.value === 'ar' ? 'ar' : 'en')
}

const getDepartmentName = (dept: EventDepartment) => {
  return locale.value === 'ar' ? dept.name_ar : dept.name_en
}

const getEventIconClass = (eventType: string) => {
  const classes: Record<string, string> = {
    created: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    accepted: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    completed: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    status_changed: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    note_added: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    priority_changed: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
    assigned: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
  }
  return classes[eventType] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

const getEventBadgeClass = (eventType: string) => {
  const classes: Record<string, string> = {
    created: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    accepted: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    completed: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    status_changed: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    note_added: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
    priority_changed: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    assigned: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400',
  }
  return classes[eventType] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}
</script>
