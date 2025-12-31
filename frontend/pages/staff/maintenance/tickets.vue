<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Maintenance Tickets</h1>
        <p class="text-[var(--color-text-muted)]">Manage assigned maintenance requests</p>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-amber-600">3</div><div class="text-sm text-[var(--color-text-muted)]">Pending</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-blue-600">2</div><div class="text-sm text-[var(--color-text-muted)]">In Progress</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-green-600">12</div><div class="text-sm text-[var(--color-text-muted)]">Completed</div></div>
      </div>

      <div class="card overflow-hidden">
        <div class="divide-y divide-[var(--color-border)]">
          <div v-for="t in tickets" :key="t.id" class="p-4">
            <div class="flex items-start justify-between mb-2">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-medium">#{{ t.id }}</span>
                  <TicketStatusBadge :status="t.status" />
                  <span :class="['badge', t.priority === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400']">{{ t.priority }}</span>
                </div>
                <p class="text-sm text-[var(--color-text-secondary)]">{{ t.description }}</p>
                <p class="text-xs text-[var(--color-text-muted)] mt-1">Created by: {{ t.createdBy }} • {{ t.date }}</p>
              </div>
              <div class="flex gap-2">
                <button v-if="t.status === 'pending'" @click="t.status = 'in_progress'" class="btn-primary text-sm">Accept</button>
                <button v-if="t.status === 'in_progress'" @click="showComplete(t)" class="btn-primary text-sm">Complete</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Modal -->
    <Modal v-model="showModal" title="Complete Ticket">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Resolution Notes</label>
          <textarea v-model="notes" class="input" rows="3" placeholder="Describe what was done..." />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Time Spent (minutes)</label>
          <input v-model="timeSpent" type="number" class="input" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showModal = false" class="btn-ghost">Cancel</button>
          <button @click="complete" class="btn-primary">Complete</button>
        </div>
      </template>
    </Modal>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })
const showModal = ref(false)
const notes = ref('')
const timeSpent = ref(30)

type TicketStatus = 'pending' | 'in_progress' | 'completed' | 'overdue'
interface MaintenanceTicket { id: string; description: string; priority: string; status: TicketStatus; createdBy: string; date: string }
const selectedTicket = ref<MaintenanceTicket | null>(null)

const tickets = ref<MaintenanceTicket[]>([
  { id: 'M-1001', description: 'AC not working in Room 201', priority: 'high', status: 'pending', createdBy: 'Dr. Ahmed', date: '2025-01-10' },
  { id: 'M-1002', description: 'Light bulb replacement in OR-3', priority: 'medium', status: 'in_progress', createdBy: 'Dr. Sara', date: '2025-01-09' },
  { id: 'M-1003', description: 'Computer not starting in Reception', priority: 'urgent', status: 'pending', createdBy: 'Reception', date: '2025-01-10' },
])

const showComplete = (t: MaintenanceTicket) => { selectedTicket.value = t; showModal.value = true }
const complete = () => { if (selectedTicket.value) selectedTicket.value.status = 'completed'; showModal.value = false }
</script>
