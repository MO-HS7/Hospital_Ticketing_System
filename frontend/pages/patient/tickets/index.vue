<template>
  <NuxtLayout name="patient">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">
            {{ $t('tickets.title') }}
          </h1>
          <p class="text-[var(--color-text-muted)]">
            Manage your appointments and track ticket status
          </p>
        </div>
        <NuxtLink 
          to="/patient/tickets/create"
          class="btn-primary"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ $t('tickets.createManual') }}
        </NuxtLink>
      </div>

      <!-- Filters -->
      <div class="card p-4">
        <div class="flex flex-wrap gap-3">
          <select class="input max-w-[160px]">
            <option value="">{{ $t('filters.status') }}</option>
            <option value="pending">{{ $t('ticketStatus.pending') }}</option>
            <option value="in_progress">{{ $t('ticketStatus.in_progress') }}</option>
            <option value="completed">{{ $t('ticketStatus.completed') }}</option>
          </select>
          <select class="input max-w-[160px]">
            <option value="">{{ $t('filters.department') }}</option>
            <option value="cardiology">Cardiology</option>
            <option value="orthopedics">Orthopedics</option>
            <option value="neurology">Neurology</option>
          </select>
          <button class="btn-ghost text-sm">
            {{ $t('filters.clear') }}
          </button>
        </div>
      </div>

      <!-- Tickets Table -->
      <div class="card overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full">
            <thead class="bg-[var(--color-bg-tertiary)]">
              <tr>
                <th class="px-4 py-3 text-start text-sm font-medium text-[var(--color-text-secondary)]">ID</th>
                <th class="px-4 py-3 text-start text-sm font-medium text-[var(--color-text-secondary)]">{{ $t('tickets.department') }}</th>
                <th class="px-4 py-3 text-start text-sm font-medium text-[var(--color-text-secondary)]">{{ $t('tickets.doctor') }}</th>
                <th class="px-4 py-3 text-start text-sm font-medium text-[var(--color-text-secondary)]">{{ $t('tickets.date') }}</th>
                <th class="px-4 py-3 text-start text-sm font-medium text-[var(--color-text-secondary)]">{{ $t('tickets.status') }}</th>
                <th class="px-4 py-3 text-end text-sm font-medium text-[var(--color-text-secondary)]">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--color-border)]">
              <tr 
                v-for="ticket in tickets" 
                :key="ticket.id"
                class="hover:bg-[var(--color-bg-tertiary)] transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-[var(--color-text-primary)]">#{{ ticket.id }}</td>
                <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)]">{{ ticket.department }}</td>
                <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)]">{{ ticket.doctor }}</td>
                <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)]">{{ ticket.date }}</td>
                <td class="px-4 py-3">
                  <TicketStatusBadge :status="ticket.status" />
                </td>
                <td class="px-4 py-3 text-end">
                  <button class="btn-ghost text-sm">
                    {{ $t('tickets.view') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden divide-y divide-[var(--color-border)]">
          <div 
            v-for="ticket in tickets" 
            :key="ticket.id"
            class="p-4"
          >
            <div class="flex items-start justify-between mb-2">
              <div>
                <span class="font-medium text-[var(--color-text-primary)]">#{{ ticket.id }}</span>
                <p class="text-sm text-[var(--color-text-secondary)]">{{ ticket.department }}</p>
              </div>
              <TicketStatusBadge :status="ticket.status" />
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-[var(--color-text-muted)]">{{ ticket.doctor }}</span>
              <span class="text-[var(--color-text-muted)]">{{ ticket.date }}</span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <EmptyState
          v-if="tickets.length === 0"
          :title="$t('tickets.noTickets')"
          :description="$t('tickets.noTicketsDesc')"
        >
          <template #action>
            <NuxtLink to="/patient/tickets/create" class="btn-primary">
              {{ $t('tickets.create') }}
            </NuxtLink>
          </template>
        </EmptyState>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-[var(--color-text-muted)]">
          Showing 1-5 of 5 tickets
        </p>
        <div class="flex gap-2">
          <button class="btn-ghost" disabled>Previous</button>
          <button class="btn-ghost" disabled>Next</button>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

// Mock data
const tickets = ref([
  { id: '1001', department: 'Cardiology', doctor: 'Dr. Ahmed Hassan', status: 'completed' as const, date: '2025-01-02' },
  { id: '1002', department: 'Orthopedics', doctor: 'Dr. Sara Mohamed', status: 'in_progress' as const, date: '2025-01-05' },
  { id: '1003', department: 'General Medicine', doctor: 'Dr. Khaled Ali', status: 'pending' as const, date: '2025-01-07' },
  { id: '1004', department: 'Dermatology', doctor: 'Dr. Fatima Omar', status: 'completed' as const, date: '2024-12-28' },
  { id: '1005', department: 'ENT', doctor: 'Dr. Youssef Ibrahim', status: 'pending' as const, date: '2025-01-10' },
])
</script>
