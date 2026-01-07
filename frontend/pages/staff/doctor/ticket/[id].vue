<template>
  <NuxtLayout name="staff">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
      <div class="text-center">
        <svg class="animate-spin w-12 h-12 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <p class="mt-4 text-[var(--color-text-muted)]">{{ $t('common.loading') }}</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-[60vh]">
      <div class="text-center max-w-md">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">{{ $t('common.error') }}</h3>
        <p class="text-[var(--color-text-muted)] mb-4">{{ error }}</p>
        <button @click="loadTicket" class="btn-primary">{{ $t('common.retry') }}</button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else-if="ticket" class="ticket-detail-page">
      <!-- Back Navigation -->
      <div class="mb-4">
        <NuxtLink to="/staff/doctor/tickets" class="inline-flex items-center gap-2 text-sm text-[var(--color-text-muted)] hover:text-primary-600 transition-colors">
          <svg class="w-4 h-4 icon-flip" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          {{ $t('doctorPortal.backToList') }}
        </NuxtLink>
      </div>

      <!-- Patient Overview Panel (Sticky) -->
      <PatientOverviewPanel :ticket="ticket" class="rounded-xl mb-6" />

      <!-- Action Buttons -->
      <div class="flex flex-wrap gap-3 mb-6">
        <button 
          v-if="canStart" 
          @click="handleStart" 
          :disabled="actionLoading"
          class="btn-primary flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
          </svg>
          {{ $t('tickets.start') }}
        </button>
        <button 
          v-if="canComplete" 
          @click="handleComplete" 
          :disabled="actionLoading"
          class="btn-primary flex items-center gap-2 bg-green-600 hover:bg-green-700"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ $t('tickets.complete') }}
        </button>
        <span v-if="actionLoading" class="flex items-center gap-2 text-sm text-[var(--color-text-muted)]">
          <span class="w-4 h-4 border-2 border-primary-300 border-t-primary-600 rounded-full animate-spin"></span>
          {{ $t('common.processing') }}
        </span>
      </div>

      <!-- Main Grid -->
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Left Column: Case Details + Notes -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Case Details -->
          <CaseDetailsCard :ticket="ticket" />

          <!-- Medical Orders -->
          <MedicalOrdersSection 
            :ticket-id="ticket.id"
            :patient-name="ticket.patient?.name || $t('common.unknownPatient')"
            :is-emergency="ticket.is_emergency"
            :orders="orders"
            :can-create-orders="canCreateOrders"
            @order-created="loadOrders"
          />

          <!-- Referral -->
          <ReferralSection
            :ticket-id="ticket.id"
            :current-department="departmentName"
            :current-doctor="ticket.assignee?.name || $t('common.notAssigned')"
            :patient-name="ticket.patient?.name || $t('common.unknownPatient')"
            :patient-age="ticket.patient_age"
            :patient-gender="ticket.patient_gender"
            :is-emergency="ticket.is_emergency"
            :can-refer="canRefer"
            @referral-created="handleReferralCreated"
          />

          <!-- Notes Section -->
          <div class="card p-5">
            <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              {{ $t('doctorPortal.notes') }}
            </h3>

            <!-- Notes List -->
            <div v-if="ticket.notes.length === 0" class="text-sm text-[var(--color-text-muted)] text-center py-4">
              {{ $t('tickets.noNotes') }}
            </div>
            <div v-else class="space-y-3 mb-4 max-h-64 overflow-y-auto">
              <div 
                v-for="note in ticket.notes" 
                :key="note.id" 
                class="p-3 rounded-lg bg-[var(--color-bg-tertiary)]"
              >
                <div class="flex items-center justify-between text-xs text-[var(--color-text-muted)] mb-1 gap-2">
                  <span class="font-medium">{{ note.user?.name || '-' }}</span>
                  <span>{{ formatDate(note.created_at) }}</span>
                </div>
                <p class="text-sm whitespace-pre-wrap">{{ note.body }}</p>
              </div>
            </div>

            <!-- Add Note Form -->
            <div class="border-t border-[var(--color-border)] pt-4">
              <label class="block text-sm font-medium mb-2">{{ $t('tickets.addNote') }}</label>
              <textarea v-model="newNote" class="input w-full" rows="3" :placeholder="$t('tickets.notePlaceholder')"></textarea>
              <div class="flex justify-end mt-2">
                <button 
                  @click="submitNote" 
                  :disabled="!newNote.trim() || noteSending" 
                  class="btn-primary text-sm"
                >
                  {{ $t('tickets.submitNote') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Timeline -->
        <div class="lg:col-span-1">
          <ActivityTimeline 
            :events="ticket.events" 
            :created-at="ticket.created_at"
            :started-at="ticket.started_at"
            :completed-at="ticket.completed_at"
          />
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import PatientOverviewPanel from '~/components/doctor/PatientOverviewPanel.vue'
import CaseDetailsCard from '~/components/doctor/CaseDetailsCard.vue'
import ActivityTimeline from '~/components/doctor/ActivityTimeline.vue'
import MedicalOrdersSection from '~/components/doctor/MedicalOrdersSection.vue'
import ReferralSection from '~/components/doctor/ReferralSection.vue'
import { useTickets, type TicketDetail } from '~/composables/useTickets'

definePageMeta({ layout: false, middleware: ['auth'] })

const route = useRoute()
const { locale } = useI18n()
const { user, token } = useAuth()
const config = useRuntimeConfig()
const { loading, error, getTicket, startTicket, completeTicket, addNote } = useTickets()

const ticketId = computed(() => Number(route.params.id))
const ticket = ref<TicketDetail | null>(null)
const orders = ref<any[]>([])
const actionLoading = ref(false)
const newNote = ref('')
const noteSending = ref(false)

const departmentName = computed(() => {
  if (!ticket.value?.department) return ticket.value?.department_id || '-'
  return locale.value === 'ar' ? ticket.value.department.name_ar : ticket.value.department.name_en
})

const canStart = computed(() => {
  if (!ticket.value || !user.value) return false
  if (ticket.value.status !== 'pending' && ticket.value.status !== 'assigned') return false
  return ticket.value.assigned_to === user.value.id
})

const canComplete = computed(() => {
  if (!ticket.value || !user.value) return false
  if (ticket.value.status !== 'in_progress') return false
  return ticket.value.assigned_to === user.value.id
})

const canCreateOrders = computed(() => {
  if (!ticket.value || !user.value) return false
  return ticket.value.assigned_to === user.value.id
})

const canRefer = computed(() => {
  if (!ticket.value || !user.value) return false
  if (user.value.role !== 'doctor') return false
  if (ticket.value.assigned_to !== user.value.id) return false
  if (ticket.value.status !== 'in_progress') return false
  return true
})

const loadTicket = async () => {
  const data = await getTicket(ticketId.value)
  ticket.value = data
  if (data) loadOrders()
}

const loadOrders = async () => {
  if (!ticket.value) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/tickets/${ticket.value.id}/orders`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    orders.value = res.data || res || []
  } catch (e) {
    console.error('Failed to load orders:', e)
    orders.value = []
  }
}

const handleStart = async () => {
  if (!ticket.value) return
  actionLoading.value = true
  try {
    await startTicket(ticket.value.id)
    await loadTicket()
  } finally {
    actionLoading.value = false
  }
}

const handleComplete = async () => {
  if (!ticket.value) return
  actionLoading.value = true
  try {
    await completeTicket(ticket.value.id)
    await loadTicket()
  } finally {
    actionLoading.value = false
  }
}

const handleReferralCreated = async () => {
  await loadTicket()
}

const submitNote = async () => {
  if (!ticket.value || !newNote.value.trim()) return
  noteSending.value = true
  try {
    await addNote(ticket.value.id, newNote.value.trim())
    newNote.value = ''
    await loadTicket()
  } finally {
    noteSending.value = false
  }
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  loadTicket()
})
</script>

<style scoped>
.ticket-detail-page {
  max-width: 1400px;
  margin: 0 auto;
}
</style>
