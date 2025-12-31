<template>
  <NuxtLayout name="patient">
    <div class="space-y-8">
      <!-- Hero Section -->
      <section class="card p-8 md:p-12 bg-gradient-to-br from-primary-600 to-primary-800 text-white relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
          <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <defs>
              <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
          </svg>
        </div>
        
        <div class="relative z-10 max-w-2xl">
          <h1 class="text-3xl md:text-4xl font-bold mb-4 text-balance">
            {{ $t('chatbot.welcome') }}
          </h1>
          <p class="text-primary-100 text-lg mb-6">
            {{ $t('app.description') }}
          </p>
          <NuxtLink 
            to="/patient/chatbot"
            class="inline-flex items-center gap-2 bg-white text-primary-700 px-6 py-3 rounded-xl font-semibold hover:bg-primary-50 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            {{ $t('tickets.createChatbot') }}
          </NuxtLink>
        </div>
      </section>

      <!-- Quick Stats -->
      <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 text-center">
          <div class="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-1">3</div>
          <div class="text-sm text-[var(--color-text-muted)]">{{ $t('tickets.title') }}</div>
        </div>
        <div class="card p-4 text-center">
          <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 mb-1">1</div>
          <div class="text-sm text-[var(--color-text-muted)]">{{ $t('ticketStatus.pending') }}</div>
        </div>
        <div class="card p-4 text-center">
          <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">1</div>
          <div class="text-sm text-[var(--color-text-muted)]">{{ $t('ticketStatus.in_progress') }}</div>
        </div>
        <div class="card p-4 text-center">
          <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">1</div>
          <div class="text-sm text-[var(--color-text-muted)]">{{ $t('ticketStatus.completed') }}</div>
        </div>
      </section>

      <!-- Recent Tickets -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">
            {{ $t('tickets.title') }}
          </h2>
          <NuxtLink 
            to="/patient/tickets"
            class="text-sm text-primary-600 dark:text-primary-400 hover:underline"
          >
            View all
          </NuxtLink>
        </div>
        
        <div class="space-y-3">
          <!-- Ticket Card -->
          <div 
            v-for="ticket in recentTickets" 
            :key="ticket.id"
            class="card p-4 flex items-center gap-4"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-medium text-[var(--color-text-primary)]">#{{ ticket.id }}</span>
                <TicketStatusBadge :status="ticket.status" />
              </div>
              <p class="text-sm text-[var(--color-text-secondary)] truncate">{{ ticket.department }}</p>
            </div>
            <div class="text-end">
              <p class="text-sm text-[var(--color-text-muted)]">{{ ticket.date }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Departments Grid -->
      <section>
        <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">
          {{ $t('tickets.department') }}
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div 
            v-for="dept in departments" 
            :key="dept.id"
            class="card p-4 text-center hover:shadow-elevated transition-shadow cursor-pointer"
          >
            <div 
              class="w-12 h-12 rounded-xl mx-auto mb-3 flex items-center justify-center"
              :class="dept.bgClass"
            >
              <span class="text-2xl">{{ dept.icon }}</span>
            </div>
            <p class="text-sm font-medium text-[var(--color-text-primary)]">{{ dept.name }}</p>
          </div>
        </div>
      </section>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: ['auth'],
})

// Mock data
const recentTickets = ref([
  { id: '1001', department: 'Cardiology', status: 'completed' as const, date: '2025-01-02' },
  { id: '1002', department: 'Orthopedics', status: 'in_progress' as const, date: '2025-01-05' },
  { id: '1003', department: 'General Medicine', status: 'pending' as const, date: '2025-01-07' },
])

const departments = ref([
  { id: 1, name: 'Cardiology', icon: '🫀', bgClass: 'bg-red-100 dark:bg-red-900/30' },
  { id: 2, name: 'Orthopedics', icon: '🦴', bgClass: 'bg-amber-100 dark:bg-amber-900/30' },
  { id: 3, name: 'Neurology', icon: '🧠', bgClass: 'bg-purple-100 dark:bg-purple-900/30' },
  { id: 4, name: 'Pediatrics', icon: '👶', bgClass: 'bg-pink-100 dark:bg-pink-900/30' },
  { id: 5, name: 'Dermatology', icon: '🩹', bgClass: 'bg-orange-100 dark:bg-orange-900/30' },
  { id: 6, name: 'Ophthalmology', icon: '👁️', bgClass: 'bg-cyan-100 dark:bg-cyan-900/30' },
  { id: 7, name: 'ENT', icon: '👂', bgClass: 'bg-green-100 dark:bg-green-900/30' },
  { id: 8, name: 'General Medicine', icon: '🩺', bgClass: 'bg-blue-100 dark:bg-blue-900/30' },
])
</script>
