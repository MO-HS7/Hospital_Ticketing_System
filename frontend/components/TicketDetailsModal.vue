 <template>
  <Modal
    :model-value="modelValue"
    :title="ticket ? `${$t('tickets.details')} #${ticket.id}` : $t('tickets.details')"
    size="xl"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="p-8 text-center">
      <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
      <p class="mt-2 text-[var(--color-text-muted)]">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="error" class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">
      {{ error }}
    </div>

    <div v-else-if="ticket" class="space-y-6">
      <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="space-y-1">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-[var(--color-text-secondary)]">#{{ ticket.id }}</span>
            <TicketStatusBadge :status="ticket.status" />
            <span class="badge bg-[var(--color-bg-tertiary)] text-[var(--color-text-secondary)]">{{ $t(`ticketType.${ticket.type}`) }}</span>
            <span class="badge bg-[var(--color-bg-tertiary)] text-[var(--color-text-secondary)]">{{ $t(`priority.${ticket.priority}`) }}</span>
          </div>
          <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ ticket.subject }}</h3>
          <p v-if="ticket.description" class="text-sm text-[var(--color-text-muted)] whitespace-pre-wrap">{{ ticket.description }}</p>
        </div>

        <div class="flex items-center gap-2">
          <button v-if="canAccept" class="btn-primary text-sm" :disabled="actionLoading" @click="handleAccept">
            {{ $t('tickets.accept') }}
          </button>
          <button v-if="canStart" class="btn-primary text-sm" :disabled="actionLoading" @click="handleStart">
            {{ $t('tickets.start') }}
          </button>
          <button v-if="canComplete" class="btn-primary text-sm" :disabled="actionLoading" @click="handleComplete">
            {{ $t('tickets.complete') }}
          </button>
          <button v-if="canRefer" class="btn-outline text-sm" :disabled="actionLoading" @click="showReferralModal = true">
            {{ $t('referral.title') }}
          </button>
          <button v-if="canCreateOrder" class="btn-outline text-sm" :disabled="actionLoading" @click="showOrderModal = true">
            {{ $t('orders.create') }}
          </button>
        </div>
      </div>

      <!-- Emergency Banner -->
      <div v-if="ticket.is_emergency" class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 flex items-center gap-3">
        <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <p class="font-semibold text-red-700 dark:text-red-300">⚠️ {{ $t('patientInfo.isEmergency') }}</p>
          <p class="text-sm text-red-600 dark:text-red-400">{{ $t('patientInfo.emergencyWarning') }}</p>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div class="card p-4 space-y-2">
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.department') }}</span>
            <span class="font-medium text-end">{{ departmentName }}</span>
          </div>
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.patient') }}</span>
            <span class="font-medium text-end">{{ ticket.patient?.name || '-' }}</span>
          </div>
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.assignee') }}</span>
            <span class="font-medium text-end">{{ ticket.assignee?.name || '-' }}</span>
          </div>
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.createdAt') }}</span>
            <span class="font-medium text-end">{{ formatDate(ticket.created_at) }}</span>
          </div>
        </div>

        <div class="card p-4 space-y-2">
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.deadline') }}</span>
            <span class="font-medium text-end">{{ formatDate(ticket.deadline) }}</span>
          </div>
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.acceptedAt') }}</span>
            <span class="font-medium text-end">{{ formatDate(ticket.accepted_at) }}</span>
          </div>
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.startedAt') }}</span>
            <span class="font-medium text-end">{{ formatDate(ticket.started_at) }}</span>
          </div>
          <div class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.completedAt') }}</span>
            <span class="font-medium text-end">{{ formatDate(ticket.completed_at) }}</span>
          </div>
          <div v-if="ticket.time_spent_minutes" class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.timeSpent') }}</span>
            <span class="font-medium text-end">{{ ticket.time_spent_minutes }} {{ $t('tickets.minutes') }}</span>
          </div>

          <div class="pt-2 border-t border-[var(--color-border)]">
            <div class="flex items-center justify-between text-sm gap-4">
              <span class="text-[var(--color-text-muted)]">{{ $t('tickets.sla') }}</span>
              <span
                class="font-medium text-end"
                :class="ticket.sla?.is_overdue ? 'text-red-600 dark:text-red-400' : 'text-[var(--color-text-primary)]'"
              >
                {{ slaText }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Patient Information Section -->
      <div v-if="hasPatientInfo" class="card p-4">
        <h4 class="font-semibold mb-3 flex items-center gap-2">
          <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          {{ $t('patientInfo.demographics') }}
        </h4>
        <div class="grid md:grid-cols-2 gap-x-6 gap-y-2">
          <div v-if="ticket.patient_age" class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.age') }}</span>
            <span class="font-medium text-end">{{ ticket.patient_age }}</span>
          </div>
          <div v-if="ticket.patient_gender" class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.gender') }}</span>
            <span class="font-medium text-end">{{ $t(`patientInfo.${ticket.patient_gender}`) }}</span>
          </div>
          <div v-if="ticket.contact_method" class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.contactMethod') }}</span>
            <span class="font-medium text-end">{{ getContactMethodLabel(ticket.contact_method) }}</span>
          </div>
          <div v-if="ticket.contact_phone" class="flex justify-between text-sm gap-4">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.contactPhone') }}</span>
            <span class="font-medium text-end" dir="ltr">{{ ticket.contact_phone }}</span>
          </div>
        </div>
        <div v-if="ticket.medical_conditions" class="mt-3 pt-3 border-t border-[var(--color-border)]">
          <span class="text-sm text-[var(--color-text-muted)]">{{ $t('patientInfo.medicalConditions') }}:</span>
          <p class="text-sm mt-1">{{ ticket.medical_conditions }}</p>
        </div>
        <div v-if="ticket.additional_notes" class="mt-3 pt-3 border-t border-[var(--color-border)]">
          <span class="text-sm text-[var(--color-text-muted)]">{{ $t('patientInfo.additionalNotes') }}:</span>
          <p class="text-sm mt-1">{{ ticket.additional_notes }}</p>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div class="card p-4">
          <h4 class="font-semibold mb-3">{{ $t('tickets.notes') }}</h4>

          <div v-if="ticket.notes.length === 0" class="text-sm text-[var(--color-text-muted)]">
            {{ $t('tickets.noNotes') }}
          </div>
          <div v-else class="space-y-3 max-h-72 overflow-y-auto pr-1">
            <div
              v-for="note in ticket.notes"
              :key="note.id"
              class="p-3 rounded-lg bg-[var(--color-bg-tertiary)]"
            >
              <div class="flex items-center justify-between text-xs text-[var(--color-text-muted)] mb-1 gap-2">
                <span class="truncate">{{ note.user?.name || '-' }}</span>
                <span class="shrink-0">{{ formatDate(note.created_at) }}</span>
              </div>
              <p class="text-sm whitespace-pre-wrap">{{ note.body }}</p>
            </div>
          </div>

          <div class="mt-4 pt-4 border-t border-[var(--color-border)] space-y-2">
            <label class="block text-sm font-medium">{{ $t('tickets.addNote') }}</label>
            <textarea v-model="newNote" class="input" rows="3" :placeholder="$t('tickets.notePlaceholder')" />
            <div class="flex justify-end">
              <button class="btn-primary text-sm" :disabled="actionLoading || !newNote.trim()" @click="submitNote">
                {{ $t('tickets.submitNote') }}
              </button>
            </div>
          </div>
        </div>

        <div class="card p-4">
          <h4 class="font-semibold mb-3">{{ $t('tickets.timeline') }}</h4>

          <div v-if="ticket.events.length === 0" class="text-sm text-[var(--color-text-muted)]">
            {{ $t('tickets.noEvents') }}
          </div>
          <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-1">
            <div v-for="ev in ticket.events" :key="ev.id" class="flex items-start gap-3">
              <div class="w-2 h-2 rounded-full mt-2 bg-primary-600 shrink-0" />
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between text-xs text-[var(--color-text-muted)] gap-2">
                  <span class="truncate">{{ eventTitle(ev) }}</span>
                  <span class="shrink-0">{{ formatDate(ev.created_at) }}</span>
                </div>
                <div v-if="ev.user?.name" class="text-xs text-[var(--color-text-muted)]">
                  {{ ev.user.name }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Patient Orders (if encounter exists) -->
      <PatientOrdersView v-if="ticket && ticket.encounter_id != null" :encounterId="ticket.encounter_id" />
    </div>

    <!-- Referral Modal -->
    <ReferralModal
      v-if="showReferralModal && ticket"
      :ticket-id="ticket.id"
      :current-department-id="ticket.department_id"
      @close="showReferralModal = false"
      @success="handleReferralSuccess"
    />

    <!-- Order Modal -->
    <CreateOrderModal
      v-if="showOrderModal && ticket"
      :ticket-id="ticket.id"
      @close="showOrderModal = false"
      @success="handleOrderSuccess"
    />
  </Modal>
</template>

<script setup lang="ts">
import { useTickets, type TicketDetail, type TicketEvent } from '~/composables/useTickets'
import ReferralModal from '~/components/ReferralModal.vue'
import CreateOrderModal from '~/components/CreateOrderModal.vue'
import PatientOrdersView from '~/components/PatientOrdersView.vue'

type Props = {
  modelValue: boolean
  ticketId: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  updated: []
}>()

const { locale, t } = useI18n()
const { user } = useAuth()
const { loading, error, getTicket, acceptTicket, startTicket, completeTicket, addNote } = useTickets()

const ticket = ref<TicketDetail | null>(null)
const actionLoading = ref(false)
const newNote = ref('')
const showReferralModal = ref(false)
const showOrderModal = ref(false)

const departmentName = computed(() => {
  if (!ticket.value?.department) return ticket.value?.department_id || '-'
  return locale.value === 'ar' ? ticket.value.department.name_ar : ticket.value.department.name_en
})

// Check if ticket has any patient info fields
const hasPatientInfo = computed(() => {
  if (!ticket.value) return false
  return !!(
    ticket.value.patient_age ||
    ticket.value.patient_gender ||
    ticket.value.contact_method ||
    ticket.value.contact_phone ||
    ticket.value.medical_conditions ||
    ticket.value.additional_notes
  )
})

// Get translated label for contact method
const getContactMethodLabel = (method: string) => {
  const methodMap: Record<string, string> = {
    'phone': t('patientInfo.phone'),
    'whatsapp': t('patientInfo.whatsapp'),
    'sms': t('patientInfo.sms'),
    'in_app': t('patientInfo.inApp'),
  }
  return methodMap[method] || method
}

const canAccept = computed(() => {
  if (!ticket.value) return false
  if (!user.value) return false

  if (!['doctor', 'maintenance', 'admin'].includes(user.value.role)) return false
  if (!['pending'].includes(ticket.value.status)) return false
  if (ticket.value.assigned_to !== null) return false

  if (user.value.role === 'doctor' && ticket.value.type !== 'appointment') return false
  if (user.value.role === 'maintenance' && ticket.value.type !== 'maintenance') return false

  return true
})

const canStart = computed(() => {
  if (!ticket.value) return false
  if (!user.value) return false

  if (!['doctor', 'maintenance', 'admin'].includes(user.value.role)) return false
  if (!['pending', 'assigned'].includes(ticket.value.status)) return false

  if (user.value.role === 'doctor' && ticket.value.type !== 'appointment') return false
  if (user.value.role === 'maintenance' && ticket.value.type !== 'maintenance') return false

  if (user.value.role !== 'admin' && ticket.value.assigned_to !== user.value.id) return false

  return true
})

const canComplete = computed(() => {
  if (!ticket.value) return false
  if (!user.value) return false

  if (!['doctor', 'maintenance', 'admin'].includes(user.value.role)) return false
  if (ticket.value.status !== 'in_progress') return false

  if (user.value.role !== 'admin' && ticket.value.assigned_to !== user.value.id) return false

  return true
})

const canRefer = computed(() => {
  if (!ticket.value) return false
  if (!user.value) return false
  // Only doctors can refer
  if (user.value.role !== 'doctor') return false
  // Must be assigned to current doctor
  if (ticket.value.assigned_to !== user.value.id) return false
  // Ticket must be in_progress
  if (ticket.value.status !== 'in_progress') return false
  return true
})

const canCreateOrder = computed(() => {
  if (!ticket.value) return false
  if (!user.value) return false
  // Only doctors can create orders
  if (user.value.role !== 'doctor') return false
  // Must be assigned to current doctor
  if (ticket.value.assigned_to !== user.value.id) return false
  return true
})

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  const lang = locale.value === 'ar' ? 'ar' : 'en'
  return date.toLocaleString(lang)
}

const slaText = computed(() => {
  const sla = ticket.value?.sla
  if (!sla || sla.minutes_remaining === null || sla.minutes_remaining === undefined) return '-'

  const minutes = Math.trunc(sla.minutes_remaining)
  if (sla.is_overdue || minutes < 0) {
    return `${t('tickets.slaOverdue')} ${Math.abs(minutes)}`
  }

  return `${t('tickets.slaRemaining')} ${minutes}`
})

const eventTitle = (ev: TicketEvent) => {
  if (ev.event_type === 'status_changed') {
    const from = (ev.meta as any)?.from
    const to = (ev.meta as any)?.to
    if (from && to) return `status: ${from} → ${to}`
  }

  if (ev.event_type === 'priority_changed') {
    const from = (ev.meta as any)?.from
    const to = (ev.meta as any)?.to
    if (from && to) return `priority: ${from} → ${to}`
  }

  return ev.event_type
}

const fetchTicket = async () => {
  if (!props.ticketId) {
    ticket.value = null
    return
  }

  const data = await getTicket(props.ticketId)
  ticket.value = data
}

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      await fetchTicket()
      return
    }

    ticket.value = null
    newNote.value = ''
  }
)

watch(
  () => props.ticketId,
  async () => {
    if (!props.modelValue) return
    await fetchTicket()
  }
)

const handleAccept = async () => {
  if (!ticket.value) return

  actionLoading.value = true
  try {
    const updated = await acceptTicket(ticket.value.id)
    if (!updated) return
    await fetchTicket()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

const handleStart = async () => {
  if (!ticket.value) return

  actionLoading.value = true
  try {
    const updated = await startTicket(ticket.value.id)
    if (!updated) return
    await fetchTicket()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

const handleComplete = async () => {
  if (!ticket.value) return

  actionLoading.value = true
  try {
    const updated = await completeTicket(ticket.value.id)
    if (!updated) return
    await fetchTicket()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

const submitNote = async () => {
  if (!ticket.value) return

  const body = newNote.value.trim()
  if (!body) return

  actionLoading.value = true
  try {
    const created = await addNote(ticket.value.id, body)
    if (!created) return
    newNote.value = ''
    await fetchTicket()
    emit('updated')
  } finally {
    actionLoading.value = false
  }
}

const handleReferralSuccess = async () => {
  showReferralModal.value = false
  await fetchTicket()
  emit('updated')
}

const handleOrderSuccess = async () => {
  showOrderModal.value = false
  await fetchTicket()
  emit('updated')
}
</script>
