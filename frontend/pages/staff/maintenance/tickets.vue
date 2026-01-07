<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Maintenance Tickets</h1>
        <p class="text-[var(--color-text-muted)]">Manage assigned maintenance requests</p>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-amber-600">{{ pendingCount }}</div><div class="text-sm text-[var(--color-text-muted)]">Pending</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-blue-600">{{ inProgressCount }}</div><div class="text-sm text-[var(--color-text-muted)]">In Progress</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-green-600">{{ completedCount }}</div><div class="text-sm text-[var(--color-text-muted)]">Completed</div></div>
      </div>

      <div
        v-if="ticketsError"
        class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm"
      >
        {{ ticketsError }}
      </div>

      <div class="card overflow-hidden">
        <div v-if="listLoading" class="p-8 text-center">
          <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
          <p class="mt-2 text-[var(--color-text-muted)]">Loading...</p>
        </div>

        <div v-else class="divide-y divide-[var(--color-border)]">
          <div v-for="t in tickets" :key="t.id" class="p-4">
            <div class="flex items-start justify-between mb-2">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-medium">#{{ t.id }}</span>
                  <TicketStatusBadge :status="t.status" />
                  <span :class="['badge', t.priority === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400']">{{ t.priority }}</span>
                </div>
                <p class="text-sm text-[var(--color-text-secondary)]">{{ t.description || t.subject }}</p>
                <p class="text-xs text-[var(--color-text-muted)] mt-1">Created by: {{ createdByLabel(t) }} • {{ dateLabel(t) }}</p>
              </div>
              <div class="flex gap-2">
                <button v-if="canAccept(t)" :disabled="actionLoading" @click="handleAccept(t.id)" class="btn-primary text-sm">Accept</button>
                <button v-if="canStart(t)" :disabled="actionLoading" @click="handleStart(t.id)" class="btn-primary text-sm">Start</button>
                <button v-if="canComplete(t)" :disabled="actionLoading" @click="handleComplete(t.id)" class="btn-primary text-sm">Complete</button>
                <button class="btn-ghost text-sm" @click="openDetails(t.id)">View</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <TicketDetailsModal v-model="detailsOpen" :ticket-id="selectedTicketId" @updated="handleTicketUpdated" />
  </NuxtLayout>
</template>

<script setup lang="ts">
import { useTickets, type TicketBase } from '~/composables/useTickets'

definePageMeta({ layout: false, middleware: ['auth'] })

const { locale } = useI18n()
const { user } = useAuth()

const { fetchTickets, acceptTicket, startTicket, completeTicket, error: ticketsError } = useTickets()

const tickets = ref<TicketBase[]>([])
const listLoading = ref(false)
const actionLoading = ref(false)

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

const pendingCount = computed(() => tickets.value.filter(t => t.status === 'pending' || t.status === 'assigned').length)
const inProgressCount = computed(() => tickets.value.filter(t => t.status === 'in_progress').length)
const completedCount = computed(() => tickets.value.filter(t => t.status === 'completed' || t.status === 'closed_late').length)

const createdByLabel = (t: TicketBase) => t.patient?.name || '-'

const dateLabel = (t: TicketBase) => {
  const raw = t.created_at
  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return raw
  const lang = locale.value === 'ar' ? 'ar' : 'en'
  return date.toLocaleDateString(lang)
}

const canAccept = (t: TicketBase) => {
  if (t.status !== 'pending') return false
  if (!user.value) return false
  return t.assigned_to === null
}

const canStart = (t: TicketBase) => {
  if (t.status !== 'pending' && t.status !== 'assigned') return false
  if (!user.value) return false
  return t.assigned_to === user.value.id
}

const canComplete = (t: TicketBase) => {
  if (t.status !== 'in_progress') return false
  if (!user.value) return false
  return t.assigned_to === user.value.id
}

const handleAccept = async (ticketId: number) => {
  actionLoading.value = true
  try {
    const updated = await acceptTicket(ticketId)
    if (!updated) return
    await loadTickets()
  } finally {
    actionLoading.value = false
  }
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

const openDetails = (ticketId: number) => {
  selectedTicketId.value = ticketId
  detailsOpen.value = true
}

const handleTicketUpdated = async () => {
  await loadTickets()
}
</script>
