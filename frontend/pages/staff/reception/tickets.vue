<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Reception Dashboard</h1>
          <p class="text-[var(--color-text-muted)]">Create and manage patient tickets</p>
        </div>
        <button @click="showModal = true" class="btn-primary">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Ticket for Patient
        </button>
      </div>

      <div class="card overflow-hidden">
        <div class="p-4 border-b border-[var(--color-border)] bg-[var(--color-bg-tertiary)]">
          <h2 class="font-semibold">My Created Tickets</h2>
        </div>
        <div class="divide-y divide-[var(--color-border)]">
          <div v-for="t in tickets" :key="t.id" class="p-4 flex items-center gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-medium">#{{ t.id }}</span>
                <TicketStatusBadge :status="t.status" />
              </div>
              <p class="text-sm text-[var(--color-text-secondary)]">{{ t.patient }} → {{ t.department }}</p>
              <p class="text-xs text-[var(--color-text-muted)]">{{ t.date }} {{ t.time }}</p>
            </div>
          </div>
        </div>
        <EmptyState v-if="tickets.length === 0" title="No tickets" description="Create a ticket for a patient to get started" />
      </div>
    </div>

    <!-- Create Ticket Modal -->
    <Modal v-model="showModal" title="Create Ticket for Patient" size="lg">
      <form @submit.prevent="createTicket" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Patient Name</label>
            <input v-model="form.patientName" type="text" class="input" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Patient Email/Phone</label>
            <input v-model="form.patientContact" type="text" class="input" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Department</label>
          <select v-model="form.department" class="input" required>
            <option value="">Select</option>
            <option v-for="d in depts" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Doctor</label>
          <select v-model="form.doctor" class="input" required>
            <option value="">Select</option>
            <option v-for="doc in doctors" :key="doc" :value="doc">{{ doc }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Date</label>
            <input v-model="form.date" type="date" class="input" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Time</label>
            <select v-model="form.time" class="input" required>
              <option value="">Select</option>
              <option v-for="t in times" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showModal = false" class="btn-ghost">Cancel</button>
          <button @click="createTicket" class="btn-primary">Create Ticket</button>
        </div>
      </template>
    </Modal>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })
const showModal = ref(false)
const form = reactive({ patientName: '', patientContact: '', department: '', doctor: '', date: '', time: '' })
const depts = ['Cardiology', 'Orthopedics', 'Neurology', 'Pediatrics', 'Dermatology', 'General Medicine']
const doctors = ['Dr. Ahmed Hassan', 'Dr. Sara Mohamed', 'Dr. Khaled Ali']
const times = ['09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00']

const tickets = ref([
  { id: '1010', patient: 'Mohammad Omar', department: 'Cardiology', status: 'pending' as const, date: '2025-01-10', time: '09:30' },
  { id: '1011', patient: 'Laila Ahmed', department: 'Pediatrics', status: 'in_progress' as const, date: '2025-01-10', time: '10:00' },
])

const createTicket = () => {
  tickets.value.unshift({
    id: String(1012 + tickets.value.length),
    patient: form.patientName,
    department: form.department,
    status: 'pending',
    date: form.date,
    time: form.time
  })
  showModal.value = false
  Object.assign(form, { patientName: '', patientContact: '', department: '', doctor: '', date: '', time: '' })
}
</script>
