<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('doctor.title') }}</h1>
          <p class="text-[var(--color-text-muted)]">{{ $t('doctor.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Current Slot Indicator -->
          <div v-if="currentSlotInfo" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300">
            <svg class="w-4 h-4 text-emerald-600 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="6" />
            </svg>
            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ currentSlotInfo }}</span>
          </div>
          <!-- Doctor identity chip -->
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-100 dark:bg-primary-900/30">
            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ user?.name }}</span>
          </div>
          <!-- Slot Filter Toggle -->
          <button 
            @click="showCurrentSlotOnly = !showCurrentSlotOnly" 
            class="text-sm px-3 py-1.5 rounded-full border transition-all"
            :class="showCurrentSlotOnly 
              ? 'bg-primary-100 dark:bg-primary-900/30 border-primary-400 text-primary-700 dark:text-primary-300' 
              : 'border-[var(--color-border)] text-[var(--color-text-muted)] hover:bg-[var(--color-bg-tertiary)]'"
          >
            {{ showCurrentSlotOnly ? 'Current Slot' : 'All Slots' }}
          </button>
          <!-- Quick Actions -->
          <button @click="loadTickets" :disabled="listLoading" class="btn-ghost text-sm">
            <svg class="w-4 h-4 icon-flip" :class="{ 'animate-spin': listLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ $t('common.refresh') }}
          </button>
          <NuxtLink to="/staff/doctor/maintenance" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ $t('doctor.reportIssue') }}
          </NuxtLink>
        </div>
      </div>

      <!-- KPI Stats Row -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <button
          v-for="stat in stats"
          :key="stat.key"
          @click="setStatusFilter(stat.filterValue)"
          :class="[
            'card p-4 text-center transition-all hover:shadow-md hover:scale-[1.02] cursor-pointer',
            activeStatus === stat.filterValue && 'ring-2 ring-primary-500'
          ]"
        >
          <div :class="['text-2xl font-bold', stat.colorClass]">{{ stat.value }}</div>
          <div class="text-xs text-[var(--color-text-muted)] mt-1">{{ stat.label }}</div>
        </button>
      </div>

      <!-- Ticket List Section -->
      <div class="card overflow-hidden">
        <!-- Tabs -->
        <div class="border-b border-[var(--color-border)] overflow-x-auto">
          <div class="flex">
            <button
              v-for="tab in tabs"
              :key="tab.value"
              @click="setStatusFilter(tab.value)"
              :class="[
                'px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                activeStatus === tab.value
                  ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                  : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'
              ]"
            >
              {{ tab.label }}
              <span v-if="tab.count > 0" class="ms-1.5 px-1.5 py-0.5 text-xs rounded-full bg-[var(--color-bg-tertiary)]">{{ tab.count }}</span>
            </button>
          </div>
        </div>

        <!-- Search & Filters -->
        <div class="p-4 border-b border-[var(--color-border)] flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="$t('doctor.searchPlaceholder')"
              class="input ps-10 w-full"
            />
          </div>
          <select v-model="sortBy" class="input w-full sm:w-auto">
            <option value="slot_start">By Slot Time</option>
            <option value="newest">{{ $t('doctor.sortNewest') }}</option>
            <option value="deadline">{{ $t('doctor.sortDeadline') }}</option>
            <option value="priority">{{ $t('doctor.sortPriority') }}</option>
          </select>
        </div>

        <!-- Loading State -->
        <div v-if="listLoading" class="p-8">
          <div class="space-y-4">
            <div v-for="i in 3" :key="i" class="animate-pulse flex items-center gap-4 p-4">
              <div class="w-12 h-12 bg-[var(--color-bg-tertiary)] rounded-full"></div>
              <div class="flex-1 space-y-2">
                <div class="h-4 bg-[var(--color-bg-tertiary)] rounded w-1/3"></div>
                <div class="h-3 bg-[var(--color-bg-tertiary)] rounded w-2/3"></div>
              </div>
              <div class="h-8 w-20 bg-[var(--color-bg-tertiary)] rounded"></div>
            </div>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="ticketsError" class="p-8 text-center">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">{{ $t('common.error') }}</h3>
          <p class="text-[var(--color-text-muted)] mb-4">{{ ticketsError }}</p>
          <button @click="loadTickets" class="btn-primary">{{ $t('common.retry') }}</button>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredTickets.length === 0" class="p-8 text-center">
          <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-primary-100 to-cyan-100 dark:from-primary-900/30 dark:to-cyan-900/30 flex items-center justify-center">
            <svg class="w-10 h-10 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">{{ $t('doctor.emptyTitle') }}</h3>
          <p class="text-[var(--color-text-muted)] max-w-md mx-auto mb-6">{{ $t('doctor.emptyDescription') }}</p>
          <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <button @click="loadTickets" class="btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {{ $t('common.refresh') }}
            </button>
            <button v-if="activeStatus !== 'overdue'" @click="setStatusFilter('overdue')" class="btn-ghost">
              {{ $t('doctor.showOverdue') }}
            </button>
            <NuxtLink to="/staff/doctor/maintenance" class="btn-primary">
              {{ $t('doctor.reportIssue') }}
            </NuxtLink>
          </div>
        </div>

        <!-- Ticket List -->
        <div v-else class="divide-y divide-[var(--color-border)]">
          <div
            v-for="t in filteredTickets"
            :key="t.id"
            class="p-4 hover:bg-[var(--color-bg-tertiary)] transition-colors"
          >
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
              <!-- Ticket Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                  <span class="font-mono text-sm font-medium text-[var(--color-text-primary)]">#{{ t.id }}</span>
                  <TicketStatusBadge :status="t.status" />
                  <span v-if="t.priority === 'urgent'" class="px-2 py-0.5 text-xs font-medium rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                    {{ $t('tickets.priority.urgent') }}
                  </span>
                  <span v-if="t.is_emergency" class="px-2 py-0.5 text-xs font-medium rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 flex items-center gap-1">
                    ⚠️ {{ $t('patientInfo.isEmergency') }}
                  </span>
                </div>
                <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">{{ t.subject }}</p>
                <div class="flex items-center gap-3 mt-1 text-xs text-[var(--color-text-muted)]">
                  <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ t.patient?.name || '-' }}
                  </span>
                  <!-- Show slot time prominently -->
                  <span v-if="t.slot_start && t.slot_end" class="flex items-center gap-1 text-primary-600 dark:text-primary-400 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ formatSlotTime(t.slot_start) }} - {{ formatSlotTime(t.slot_end) }}
                  </span>
                  <span v-else class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ formatDateTime(t.scheduled_at || t.created_at) }}
                  </span>
                </div>
                <!-- SLA Indicator -->
                <div v-if="t.deadline" class="mt-2">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full',
                      isOverdue(t.deadline) ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                    ]"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ isOverdue(t.deadline) ? $t('doctor.overdue') : $t('doctor.deadline') }}: {{ formatDateTime(t.deadline) }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 shrink-0">
                <!-- Start button (auto-transition when slot starts) -->
                <button
                  v-if="canStart(t)"
                  :disabled="actionLoading"
                  @click="handleStart(t.id)"
                  class="btn-primary text-sm"
                >
                  {{ $t('tickets.start') }}
                </button>
                <button
                  v-if="canComplete(t)"
                  :disabled="actionLoading"
                  @click="handleComplete(t.id)"
                  class="btn-primary text-sm"
                >
                  {{ $t('doctor.complete') }}
                </button>
                <NuxtLink :to="`/staff/doctor/ticket/${t.id}`" class="btn-ghost text-sm">
                  {{ $t('common.view') }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredTickets.length > 0" class="p-4 border-t border-[var(--color-border)] flex items-center justify-between">
          <p class="text-sm text-[var(--color-text-muted)]">
            {{ $t('common.showing') }} {{ filteredTickets.length }} {{ $t('common.of') }} {{ tickets.length }} {{ $t('common.items') }}
          </p>
        </div>
      </div>
    </div>

    <TicketDetailsModal v-model="detailsOpen" :ticket-id="selectedTicketId" @updated="handleTicketUpdated" />
  </NuxtLayout>
</template>

<script setup lang="ts">
import { useTickets, type TicketBase } from '~/composables/useTickets'

definePageMeta({ layout: false, middleware: ['auth'] })

const { t, locale } = useI18n()
const { user } = useAuth()

const { fetchTickets, startTicket, completeTicket, error: ticketsError } = useTickets()

const tickets = ref<TicketBase[]>([])
const listLoading = ref(false)
const actionLoading = ref(false)
const activeStatus = ref<string>('all')
const searchQuery = ref('')
const sortBy = ref<'slot_start' | 'newest' | 'deadline' | 'priority'>('slot_start')
const showCurrentSlotOnly = ref(true) // Default: show only current slot

// Current slot info
const currentSlotInfo = computed(() => {
  const now = new Date()
  const hour = now.getHours()
  const minute = now.getMinutes()
  const slotStart = minute < 30 ? `${hour}:00` : `${hour}:30`
  const slotEnd = minute < 30 ? `${hour}:30` : `${hour + 1}:00`
  return `${slotStart} - ${slotEnd}`
})

// Check if ticket is in current slot
const isInCurrentSlot = (t: TicketBase) => {
  if (!t.slot_start || !t.slot_end) return true // Show tickets without slot data
  const now = new Date()
  const slotStart = new Date(t.slot_start)
  const slotEnd = new Date(t.slot_end)
  return now >= slotStart && now < slotEnd
}

const loadTickets = async () => {
  listLoading.value = true
  try {
    const res = await fetchTickets()
    tickets.value = res.data
  } finally {
    listLoading.value = false
  }
}

onMounted(async () => {
  await loadTickets()
})

// Stats computed - updated for slot-based statuses
const stats = computed(() => [
  { key: 'total', label: t('doctor.statTotal'), value: tickets.value.length, colorClass: 'text-primary-600', filterValue: 'all' },
  { key: 'inQueue', label: 'In Queue', value: tickets.value.filter(t => t.status === 'in_queue' || t.status === 'pending').length, colorClass: 'text-amber-600', filterValue: 'in_queue' },
  { key: 'inProgress', label: t('doctor.statInProgress'), value: tickets.value.filter(t => t.status === 'in_progress').length, colorClass: 'text-blue-600', filterValue: 'in_progress' },
  { key: 'completed', label: t('doctor.statCompleted'), value: tickets.value.filter(t => t.status === 'completed').length, colorClass: 'text-green-600', filterValue: 'completed' },
  { key: 'scheduled', label: 'Scheduled', value: tickets.value.filter(t => t.status === 'scheduled').length, colorClass: 'text-slate-600', filterValue: 'scheduled' },
  { key: 'overdue', label: t('doctor.statOverdue'), value: tickets.value.filter(t => isOverdue(t.deadline)).length, colorClass: 'text-red-600', filterValue: 'overdue' },
])

const tabs = computed(() => [
  { value: 'all', label: t('doctor.tabAll'), count: tickets.value.length },
  { value: 'pending', label: t('doctor.tabPending'), count: tickets.value.filter(t => t.status === 'pending' || t.status === 'assigned').length },
  { value: 'in_progress', label: t('doctor.tabInProgress'), count: tickets.value.filter(t => t.status === 'in_progress').length },
  { value: 'completed', label: t('doctor.tabCompleted'), count: tickets.value.filter(t => t.status === 'completed' || t.status === 'closed_late').length },
  { value: 'overdue', label: t('doctor.tabOverdue'), count: tickets.value.filter(t => isOverdue(t.deadline)).length },
])

const setStatusFilter = (status: string) => {
  activeStatus.value = status
}

const filteredTickets = computed(() => {
  let result = [...tickets.value]

  // Current slot filter
  if (showCurrentSlotOnly.value) {
    result = result.filter(t => isInCurrentSlot(t))
  }

  // Status filter
  if (activeStatus.value !== 'all') {
    if (activeStatus.value === 'completed') {
      result = result.filter(t => t.status === 'completed' || t.status === 'closed_late')
    } else if (activeStatus.value === 'overdue') {
      result = result.filter(t => isOverdue(t.deadline))
    } else if (activeStatus.value === 'in_queue') {
      result = result.filter(t => t.status === 'in_queue' || t.status === 'pending')
    } else {
      result = result.filter(t => t.status === activeStatus.value)
    }
  }

  // Search filter
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(t =>
      t.id.toString().includes(q) ||
      t.subject?.toLowerCase().includes(q) ||
      t.patient?.name?.toLowerCase().includes(q)
    )
  }

  // Sorting - default by slot_start
  result.sort((a, b) => {
    if (sortBy.value === 'slot_start') {
      return new Date(a.slot_start || a.scheduled_at || 0).getTime() - new Date(b.slot_start || b.scheduled_at || 0).getTime()
    } else if (sortBy.value === 'deadline') {
      return new Date(a.deadline || 0).getTime() - new Date(b.deadline || 0).getTime()
    } else if (sortBy.value === 'priority') {
      const order = { urgent: 0, high: 1, medium: 2, low: 3 }
      return (order[a.priority as keyof typeof order] || 2) - (order[b.priority as keyof typeof order] || 2)
    }
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })

  return result
})

const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const isOverdue = (deadline: string | null) => {
  if (!deadline) return false
  return new Date(deadline) < new Date()
}

// Slot-based workflow: only show Start if ticket is in current slot and in_queue/pending
const canStart = (t: TicketBase) => {
  if (!['pending', 'in_queue', 'scheduled'].includes(t.status)) return false
  if (!user.value) return false
  // Must be in current slot or no slot defined
  if (t.slot_start && !isInCurrentSlot(t)) return false
  return t.assigned_to === user.value.id
}

const canComplete = (t: TicketBase) => {
  if (t.status !== 'in_progress') return false
  if (!user.value) return false
  return t.assigned_to === user.value.id
}

// Format slot time for display
const formatSlotTime = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleTimeString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const handleStart = async (ticketId: number) => {
  actionLoading.value = true
  try {
    const updated = await startTicket(ticketId)
    if (!updated) return
    await loadTickets()
  } finally {
    actionLoading.value = false
  }
}

const handleComplete = async (ticketId: number) => {
  actionLoading.value = true
  try {
    const updated = await completeTicket(ticketId)
    if (!updated) return
    await loadTickets()
  } finally {
    actionLoading.value = false
  }
}

const detailsOpen = ref(false)
const selectedTicketId = ref<number | null>(null)

const handleTicketUpdated = async () => {
  await loadTickets()
}
</script>
