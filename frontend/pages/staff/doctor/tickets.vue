<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">My Tickets</h1>
          <p class="text-[var(--color-text-muted)]">Manage your assigned appointments</p>
        </div>
        <NuxtLink to="/staff/doctor/maintenance" class="btn-secondary">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Report Issue
        </NuxtLink>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-amber-600">2</div><div class="text-sm text-[var(--color-text-muted)]">Pending</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-blue-600">1</div><div class="text-sm text-[var(--color-text-muted)]">In Progress</div></div>
        <div class="card p-4 text-center"><div class="text-2xl font-bold text-green-600">5</div><div class="text-sm text-[var(--color-text-muted)]">Completed</div></div>
      </div>

      <div class="card overflow-hidden">
        <div class="divide-y divide-[var(--color-border)]">
          <div v-for="t in tickets" :key="t.id" class="p-4 flex items-center gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-medium">#{{ t.id }}</span>
                <TicketStatusBadge :status="t.status" />
              </div>
              <p class="text-sm text-[var(--color-text-secondary)]">{{ t.patient }} • {{ t.date }} {{ t.time }}</p>
            </div>
            <div class="flex gap-2">
              <button v-if="t.status === 'pending'" @click="t.status = 'in_progress'" class="btn-primary text-sm">Accept</button>
              <button v-if="t.status === 'in_progress'" @click="t.status = 'completed'" class="btn-primary text-sm">Complete</button>
              <button class="btn-ghost text-sm">View</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })
type TicketStatus = 'pending' | 'in_progress' | 'completed' | 'overdue'
interface Ticket { id: string; patient: string; date: string; time: string; status: TicketStatus }
const tickets = ref<Ticket[]>([
  { id: '1001', patient: 'Mohamed Ali', date: '2025-01-10', time: '09:00', status: 'pending' },
  { id: '1002', patient: 'Fatima Hassan', date: '2025-01-10', time: '09:30', status: 'pending' },
  { id: '1003', patient: 'Ahmed Youssef', date: '2025-01-10', time: '10:00', status: 'in_progress' },
])
</script>
