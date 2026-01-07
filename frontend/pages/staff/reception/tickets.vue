<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('reception.dashboard') }}</h1>
          <p class="text-[var(--color-text-muted)]">{{ $t('reception.dashboardDesc') }}</p>
        </div>
        <button @click="openModal" class="btn-primary">
          <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ $t('reception.createTicket') }}
        </button>
      </div>

      <div
        v-if="ticketsError"
        class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm"
      >
        {{ ticketsError }}
      </div>

      <div
        v-if="confirmError"
        class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm"
      >
        {{ confirmError }}
      </div>

      <div class="card overflow-hidden">
        <div class="p-4 border-b border-[var(--color-border)] bg-[var(--color-bg-tertiary)]">
          <h2 class="font-semibold">{{ $t('reception.myTickets') }}</h2>
        </div>
        <div v-if="listLoading" class="p-8 text-center">
          <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
          <p class="mt-2 text-[var(--color-text-muted)]">{{ $t('common.loading') }}</p>
        </div>

        <div v-else class="divide-y divide-[var(--color-border)]">
          <div v-for="t in tickets" :key="t.id" class="p-4 flex items-center gap-4 hover:bg-[var(--color-bg-tertiary)] transition-colors">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-medium">#{{ t.id }}</span>
                <TicketStatusBadge :status="t.status" />
              </div>
              <p class="font-medium text-[var(--color-text-primary)]">{{ t.subject }}</p>
              <p class="text-sm text-[var(--color-text-secondary)]">{{ patientLabel(t) }} → {{ departmentLabel(t) }}</p>
              <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ dateTimeLabel(t) }}</p>
            </div>

            <div class="flex items-center gap-2">
              <button
                v-if="t.status === 'awaiting_payment' && t.encounter_id"
                class="btn-primary text-sm"
                :disabled="confirmLoadingId === t.id"
                @click="confirmPayment(t)"
              >
                {{ confirmLoadingId === t.id ? $t('common.loading') : $t('payment.confirmPayment') }}
              </button>
              <button class="btn-ghost text-sm" @click="openDetails(t.id)">{{ $t('reception.view') }}</button>
            </div>
          </div>
        </div>
        <EmptyState v-if="!listLoading && tickets.length === 0" :title="$t('reception.noTickets')" :description="$t('reception.noTicketsDesc')" />
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <Modal v-model="showModal" :title="$t('reception.form.title')" size="lg">
      <form @submit.prevent="handleCreateTicket" class="space-y-4">
        <!-- Subject -->
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('reception.form.subject') }}</label>
          <input
            v-model="form.subject"
            type="text"
            class="input"
            required
            :placeholder="$t('reception.form.subject')"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Patient Search -->
          <div>
            <label class="block text-sm font-medium mb-1">
              {{ $t('reception.form.patient') }}
              <span v-if="selectedPatient" class="text-green-600 dark:text-green-400 text-xs ltr:ml-2 rtl:mr-2">(Selected)</span>
            </label>
            <div class="relative">
              <input
                v-model="patientSearch"
                type="text"
                class="input ltr:pr-8 rtl:pl-8"
                :class="{'!border-green-500': selectedPatient}"
                :placeholder="$t('reception.form.patientPlaceholder')"
                @input="onPatientInput"
                :disabled="!!selectedPatient"
              />
              <button 
                v-if="selectedPatient || patientSearch" 
                type="button" 
                class="absolute ltr:right-2 rtl:left-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                @click="clearPatient"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <div
              v-if="patientOptions.length > 0"
              class="mt-2 border border-[var(--color-border)] rounded-lg overflow-hidden max-h-48 overflow-y-auto shadow-lg"
            >
              <button
                v-for="p in patientOptions"
                :key="p.id"
                type="button"
                class="w-full text-start px-3 py-2 hover:bg-[var(--color-bg-tertiary)] border-b last:border-0 border-[var(--color-border)]"
                @click="selectPatient(p)"
              >
                <div class="text-sm font-medium">{{ p.name }}</div>
                <div class="text-xs text-[var(--color-text-muted)]">{{ p.email || p.phone || '-' }}</div>
              </button>
            </div>
          </div>

          <!-- Contact Details (Read Only) -->
          <div>
            <label class="block text-sm font-medium mb-1 text-[var(--color-text-muted)]">{{ $t('reception.form.contactDetails') }}</label>
            <input 
              :value="selectedPatient ? (selectedPatient.email || selectedPatient.phone || '-') : '-'" 
              type="text" 
              class="input bg-[var(--color-bg-tertiary)] text-[var(--color-text-muted)] cursor-not-allowed" 
              disabled 
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('reception.form.department') }}</label>
          <select v-model="form.department_id" class="input" required>
            <option value="">{{ $t('reception.form.select') }}</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ getName(d) }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('reception.form.doctor') }}</label>
          <select v-model.number="form.assigned_to" class="input" :disabled="!form.department_id">
            <option :value="null">{{ $t('reception.form.select') }}</option>
            <option v-for="doc in doctorOptions" :key="doc.id" :value="doc.id">{{ doc.name }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('reception.form.date') }}</label>
            <input v-model="form.date" type="date" class="input" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('reception.form.time') }}</label>
            <select v-model="form.time" class="input" required>
              <option value="">{{ $t('reception.form.select') }}</option>
              <option v-for="t in times" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex flex-col gap-2">
          <!-- Validation feedback -->
          <div v-if="!canCreate" class="text-sm text-amber-600 dark:text-amber-400">
            <span v-if="!selectedPatient">{{ $t('reception.form.selectPatientFirst') || 'Please search and select a patient first' }}</span>
            <span v-else-if="!form.subject">{{ $t('reception.form.enterSubject') || 'Please enter a subject' }}</span>
            <span v-else-if="!form.department_id">{{ $t('reception.form.selectDepartment') || 'Please select a department' }}</span>
            <span v-else-if="!form.date || !form.time">{{ $t('reception.form.selectDateTime') || 'Please select date and time' }}</span>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showModal = false" class="btn-ghost">{{ $t('reception.form.cancel') }}</button>
            <button 
              @click="handleCreateTicket" 
              class="btn-primary" 
              :disabled="createLoading || !canCreate"
              :class="{ 'opacity-50 cursor-not-allowed': !canCreate }"
            >
              {{ createLoading ? $t('common.loading') : $t('reception.form.create') }}
            </button>
          </div>
        </div>
      </template>
    </Modal>

    <TicketDetailsModal v-model="detailsOpen" :ticket-id="selectedTicketId" @updated="handleTicketUpdated" />
  </NuxtLayout>
</template>

<script setup lang="ts">
import { useTickets, type CreateTicketPayload, type TicketBase } from '~/composables/useTickets'

definePageMeta({ layout: false, middleware: ['auth'] })

type PatientOption = { id: number; name: string; email?: string; phone?: string | null }
type DoctorOption = { id: number; name: string; email?: string; phone?: string | null; department_id: string | null }

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()
const { departments, fetchDepartments, getName } = useDepartments()
const { fetchTickets, createTicket, error: ticketsError } = useTickets()

const showModal = ref(false)
const form = reactive({ 
  subject: '',
  department_id: '', 
  assigned_to: null as number | null, 
  date: '', 
  time: '' 
})

const patientSearch = ref('')
const patientOptions = ref<PatientOption[]>([])
const selectedPatient = ref<PatientOption | null>(null)
let patientSearchTimer: ReturnType<typeof setTimeout>

const doctorOptions = ref<DoctorOption[]>([])
const times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00']

const tickets = ref<TicketBase[]>([])
const listLoading = ref(false)
const createLoading = ref(false)
const confirmLoadingId = ref<number | null>(null)
const confirmError = ref<string | null>(null)

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
  if (departments.value.length === 0) {
    await fetchDepartments()
  }
  await loadTickets()
})

const patientLabel = (t: TicketBase) => t.patient?.name || '-'

const departmentLabel = (t: TicketBase) => {
  if (!t.department) return t.department_id
  return locale.value === 'ar' ? t.department.name_ar : t.department.name_en
}

const dateTimeLabel = (t: TicketBase) => {
  const raw = t.scheduled_at || t.created_at
  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return raw
  const lang = locale.value === 'ar' ? 'ar' : 'en'
  return date.toLocaleString(lang)
}

const detailsOpen = ref(false)
const selectedTicketId = ref<number | null>(null)

const openDetails = (ticketId: number) => {
  selectedTicketId.value = ticketId
  detailsOpen.value = true
}

const openModal = () => {
  showModal.value = true
  // Reset form but keep simple defaults if any
}

const confirmPayment = async (t: TicketBase) => {
  if (!t.encounter_id) return

  confirmLoadingId.value = t.id
  confirmError.value = null

  try {
    await $fetch(`${config.public.apiBase}/payments/encounters/${t.encounter_id}/confirm-at-hospital`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
    })
    await loadTickets()
  } catch (e: any) {
    confirmError.value = e.data?.message || e.message || 'Failed to confirm payment'
  } finally {
    confirmLoadingId.value = null
  }
}

const handleTicketUpdated = async () => {
  await loadTickets()
}

const onPatientInput = () => {
  // If user types, we are searching, so selectedPatient should be null unless they just selected it.
  // Actually, if we disable the input when selected, this won't fire when selected.
  // But if we allow edits, we need to clear selection. 
  // Here I disabled the input when selected, so this only fires when searching.
  clearTimeout(patientSearchTimer)
  patientSearchTimer = setTimeout(fetchPatients, 300)
}

const fetchPatients = async () => {
  const q = patientSearch.value.trim()
  if (q.length < 2) {
    patientOptions.value = []
    return
  }

  try {
    const params = new URLSearchParams({ search: q })
    const res = await $fetch<PatientOption[]>(`${config.public.apiBase}/staff/patients?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    patientOptions.value = res
  } catch {
    patientOptions.value = []
  }
}

const selectPatient = (p: PatientOption) => {
  selectedPatient.value = p
  patientSearch.value = p.name
  patientOptions.value = []
  
  // Auto-fill subject if empty
  if (!form.subject) {
    form.subject = `Appointment with ${p.name}`
  }
}

const clearPatient = () => {
  selectedPatient.value = null
  patientSearch.value = ''
  patientOptions.value = []
}

const fetchDoctors = async (departmentId: string) => {
  try {
    const params = new URLSearchParams({ department_id: departmentId })
    const res = await $fetch<DoctorOption[]>(`${config.public.apiBase}/staff/doctors?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    doctorOptions.value = res
  } catch {
    doctorOptions.value = []
  }
}

watch(
  () => form.department_id,
  async (id) => {
    form.assigned_to = null
    doctorOptions.value = []
    if (!id) return
    await fetchDoctors(id)
  }
)

const canCreate = computed(() => {
  return !!selectedPatient.value && !!form.department_id && !!form.date && !!form.time && !!form.subject
})

const resetForm = () => {
  clearPatient()
  doctorOptions.value = []
  Object.assign(form, { subject: '', department_id: '', assigned_to: null, date: '', time: '' })
}

const handleCreateTicket = async () => {
  if (!selectedPatient.value) return
  if (!canCreate.value) return

  const scheduledAt = form.date && form.time ? `${form.date}T${form.time}:00` : null
  const payload: CreateTicketPayload = {
    patient_id: selectedPatient.value.id,
    department_id: form.department_id,
    assigned_to: form.assigned_to,
    type: 'appointment',
    subject: form.subject,
    description: null,
    scheduled_at: scheduledAt,
    priority: 'medium',
  }

  createLoading.value = true
  try {
    const created = await createTicket(payload)
    if (!created) return
    showModal.value = false
    resetForm()
    await loadTickets()
  } finally {
    createLoading.value = false
  }
}
</script>
