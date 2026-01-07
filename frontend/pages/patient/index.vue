<template>
  <NuxtLayout name="patient">
    <!-- Hero Section -->
    <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-primary-600 to-primary-800 text-white shadow-lg relative overflow-hidden">
      <div class="absolute inset-0 bg-[url('/pattern.svg')] opacity-10"></div>
      <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold mb-2">
            {{ getGreeting() }}, {{ user?.name }}! 👋
          </h1>
          <p class="text-primary-100 max-w-xl">
            {{ $t('dashboard.welcomeSub') || 'Manage your appointments and health records efficiently.' }}
          </p>
        </div>
        <div class="flex gap-3 shrink-0">
          <NuxtLink to="/patient/chatbot" class="btn bg-white/10 hover:bg-white/20 text-white border-white/20 backdrop-blur-sm">
            <svg class="w-5 h-5 mr-2 rtl:mr-0 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            {{ $t('nav.chatbot') }}
          </NuxtLink>
          <NuxtLink to="/patient/tickets/create" class="btn bg-white text-primary-700 hover:bg-primary-50 border-none shadow-md">
            <svg class="w-5 h-5 mr-2 rtl:mr-0 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            {{ $t('tickets.create') }}
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard 
        :title="$t('dashboard.totalTickets') || 'Total Tickets'" 
        :value="stats.total" 
        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
        :loading="loading"
      />
      <StatCard 
        :title="$t('dashboard.pending') || 'Pending'" 
        :value="stats.pending" 
        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        :loading="loading"
      />
      <StatCard 
        :title="$t('dashboard.active') || 'In Progress'" 
        :value="stats.active" 
        icon="M13 10V3L4 14h7v7l9-11h-7z"
        :loading="loading"
      />
      <StatCard 
        :title="$t('dashboard.completed') || 'Completed'" 
        :value="stats.completed" 
        icon="M5 13l4 4L19 7"
        :loading="loading"
      />
    </div>

    <!-- Recent Tickets -->
    <div class="card p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-bold text-[var(--color-text-primary)]">
          {{ $t('dashboard.recentTickets') }}
        </h2>
        <NuxtLink to="/patient/tickets" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">
          {{ $t('common.viewAll') }} &rarr;
        </NuxtLink>
      </div>

      <div v-if="loading" class="space-y-4">
        <Skeleton class="h-16 w-full" v-for="i in 3" :key="i" />
      </div>

      <div v-else-if="recentTickets.length === 0">
        <EmptyState 
          :title="$t('tickets.empty') || 'No tickets found'"
          :description="$t('tickets.emptyDesc') || 'You haven\'t created any tickets yet.'"
        >
          <template #action>
            <NuxtLink to="/patient/tickets/create" class="btn-primary">
              {{ $t('tickets.create') }}
            </NuxtLink>
          </template>
        </EmptyState>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-start">
          <thead class="text-xs text-[var(--color-text-muted)] uppercase bg-[var(--color-bg-secondary)] border-y border-[var(--color-border)]">
            <tr>
              <th class="px-4 py-3 font-semibold text-start">ID</th>
              <th class="px-4 py-3 font-semibold text-start">{{ $t('tickets.subject') }}</th>
              <th class="px-4 py-3 font-semibold text-start">{{ $t('tickets.department') }}</th>
              <th class="px-4 py-3 font-semibold text-start">{{ $t('tickets.status') }}</th>
              <th class="px-4 py-3 font-semibold text-start">{{ $t('tickets.updated') }}</th>
              <th class="px-4 py-3 text-end"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-border)]">
            <tr v-for="ticket in recentTickets" :key="ticket.id" class="group hover:bg-[var(--color-bg-tertiary)] transition-colors">
              <td class="px-4 py-3 font-medium">#{{ ticket.id }}</td>
              <td class="px-4 py-3 font-medium text-[var(--color-text-primary)]">{{ ticket.subject }}</td>
              <td class="px-4 py-3 text-[var(--color-text-secondary)]">
                {{ locale === 'ar' ? ticket.department?.name_ar || ticket.department?.name : ticket.department?.name_en || ticket.department?.name }}
              </td>
              <td class="px-4 py-3">
                <Badge :color="getStatusColor(ticket.status)" :label="$t(`ticketStatus.${ticket.status}`)" dot />
              </td>
              <td class="px-4 py-3 text-[var(--color-text-muted)] text-xs">
                {{ formatDate(ticket.updated_at) }}
              </td>
              <td class="px-4 py-3 text-end">
                <NuxtLink :to="`/patient/tickets/${ticket.id}`" class="text-primary-600 hover:text-primary-700 font-medium text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                  {{ $t('common.details') }}
                </NuxtLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import StatCard from '~/components/ui/StatCard.vue'
import Skeleton from '~/components/ui/Skeleton.vue'
import EmptyState from '~/components/ui/EmptyState.vue'
import Badge from '~/components/ui/Badge.vue'

definePageMeta({ layout: false, middleware: ['auth'] })

const { user } = useAuth()
const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()

const loading = ref(true)
const tickets = ref<any[]>([])
const recentTickets = computed(() => tickets.value.slice(0, 5))

// Calculated stats
const stats = computed(() => {
  const all = tickets.value
  return {
    total: all.length,
    pending: all.filter(t => ['pending', 'assigned', 'awaiting_payment'].includes(t.status)).length,
    active: all.filter(t => ['in_progress', 'overdue'].includes(t.status)).length,
    completed: all.filter(t => ['completed', 'closed_late'].includes(t.status)).length
  }
})

const getGreeting = () => {
  const hour = new Date().getHours()
  if (hour < 12) return locale.value === 'ar' ? 'صباح الخير' : 'Good Morning'
  if (hour < 18) return locale.value === 'ar' ? 'طاب مساؤك' : 'Good Afternoon'
  return locale.value === 'ar' ? 'مساء الخير' : 'Good Evening'
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending': return 'warning'
    case 'assigned': return 'primary'
    case 'awaiting_payment': return 'warning'
    case 'in_progress': return 'primary'
    case 'completed': return 'success'
    case 'closed_late': return 'warning'
    case 'overdue': return 'danger'
    default: return 'gray'
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString(locale.value, {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// Fetch tickets
onMounted(async () => {
  loading.value = true
  try {
    const response = await $fetch<{ data: any[] }>(`${config.public.apiBase}/tickets`, {
      method: 'GET',
      headers: { Authorization: `Bearer ${token.value}` }
    })
    tickets.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch tickets:', err)
  } finally {
    loading.value = false
  }
})
</script>
