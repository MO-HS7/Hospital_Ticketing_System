<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('dashboard.title') }}</h1>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.total }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.totalTickets') }}</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.pending }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.pendingTickets') }}</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.overdue }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.overdueTickets') }}</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.completed }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.completedTickets') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid md:grid-cols-2 gap-6">
        <div class="card p-6">
          <h3 class="font-semibold mb-4">{{ $t('dashboard.ticketsByDept') }}</h3>
          <div class="space-y-3">
            <div v-for="d in deptStats" :key="d.name" class="flex items-center gap-3">
              <span class="w-24 text-sm text-[var(--color-text-secondary)]">{{ d.name }}</span>
              <div class="flex-1 h-4 bg-[var(--color-bg-tertiary)] rounded-full overflow-hidden">
                <div class="h-full bg-primary-600 rounded-full" :style="{ width: `${(d.count / 50) * 100}%` }" />
              </div>
              <span class="text-sm font-medium w-8">{{ d.count }}</span>
            </div>
          </div>
        </div>
        <div class="card p-6">
          <h3 class="font-semibold mb-4">{{ $t('dashboard.staffCount') }}</h3>
          <div class="grid grid-cols-3 gap-4 text-center">
            <div><p class="text-3xl font-bold text-cyan-600">12</p><p class="text-sm text-[var(--color-text-muted)]">Doctors</p></div>
            <div><p class="text-3xl font-bold text-amber-600">5</p><p class="text-sm text-[var(--color-text-muted)]">Maintenance</p></div>
            <div><p class="text-3xl font-bold text-rose-600">4</p><p class="text-sm text-[var(--color-text-muted)]">Reception</p></div>
          </div>
        </div>
      </div>

      <!-- Overdue Table -->
      <div class="card overflow-hidden">
        <div class="p-4 border-b border-[var(--color-border)] flex items-center justify-between">
          <h3 class="font-semibold">{{ $t('dashboard.recentOverdue') }}</h3>
          <select class="input w-auto text-sm">
            <option>All Departments</option>
            <option>Cardiology</option>
            <option>Orthopedics</option>
          </select>
        </div>
        <table class="w-full">
          <thead class="bg-[var(--color-bg-tertiary)]">
            <tr>
              <th class="px-4 py-3 text-start text-sm font-medium">ID</th>
              <th class="px-4 py-3 text-start text-sm font-medium">Department</th>
              <th class="px-4 py-3 text-start text-sm font-medium">Type</th>
              <th class="px-4 py-3 text-start text-sm font-medium">Deadline</th>
              <th class="px-4 py-3 text-start text-sm font-medium">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-border)]">
            <tr v-for="t in overdueTickets" :key="t.id" class="hover:bg-[var(--color-bg-tertiary)]">
              <td class="px-4 py-3 text-sm font-medium">#{{ t.id }}</td>
              <td class="px-4 py-3 text-sm">{{ t.dept }}</td>
              <td class="px-4 py-3 text-sm">{{ t.type }}</td>
              <td class="px-4 py-3 text-sm text-red-600">{{ t.deadline }}</td>
              <td class="px-4 py-3"><TicketStatusBadge :status="t.status" /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const metrics = { total: 156, pending: 23, overdue: 5, completed: 128 }
const deptStats = [
  { name: 'Cardiology', count: 42 },
  { name: 'Orthopedics', count: 35 },
  { name: 'Neurology', count: 28 },
  { name: 'Pediatrics', count: 31 },
  { name: 'General', count: 20 },
]
const overdueTickets = [
  { id: '1005', dept: 'Cardiology', type: 'Appointment', deadline: '2025-01-08', status: 'overdue' as const },
  { id: '1012', dept: 'Orthopedics', type: 'Appointment', deadline: '2025-01-09', status: 'overdue' as const },
  { id: 'M-1003', dept: 'IT', type: 'Maintenance', deadline: '2025-01-09', status: 'overdue' as const },
]
</script>
