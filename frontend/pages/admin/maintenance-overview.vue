<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('commandCenter.maintenanceOverview') }}</h1>
        <p class="text-sm text-[var(--color-text-muted)] mt-1">{{ $t('commandCenter.maintenanceSubtitle') }}</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4">
          <p class="text-2xl font-bold text-primary-600">{{ stats.total }}</p>
          <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.totalTickets') }}</p>
        </div>
        <div class="card p-4">
          <p class="text-2xl font-bold text-amber-600">{{ stats.open }}</p>
          <p class="text-sm text-[var(--color-text-muted)]">{{ $t('commandCenter.activeTickets') }}</p>
        </div>
        <div class="card p-4">
          <p class="text-2xl font-bold text-green-600">{{ stats.resolved }}</p>
          <p class="text-sm text-[var(--color-text-muted)]">{{ $t('commandCenter.resolvedToday') }}</p>
        </div>
        <div class="card p-4">
          <p class="text-2xl font-bold text-blue-600">{{ stats.avgTime }}</p>
          <p class="text-sm text-[var(--color-text-muted)]">{{ $t('commandCenter.avgResolutionTime') }}</p>
        </div>
      </div>

      <!-- Maintenance Tickets Table -->
      <div class="card overflow-hidden">
        <div class="p-4 border-b border-[var(--color-border)] flex items-center justify-between">
          <h3 class="font-semibold">{{ $t('tickets.title') }}</h3>
          <select v-model="statusFilter" class="input text-sm py-1.5 w-40">
            <option value="">{{ $t('filters.all') }}</option>
            <option value="pending">{{ $t('ticketStatus.pending') }}</option>
            <option value="in_progress">{{ $t('ticketStatus.in_progress') }}</option>
            <option value="completed">{{ $t('ticketStatus.completed') }}</option>
          </select>
        </div>
        <div v-if="loading" class="p-8 text-center">
          <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>
        <table v-else class="w-full">
          <thead class="bg-[var(--color-bg-tertiary)]">
            <tr>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('table.id') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.subject') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.status') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.priority') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.createdAt') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-border)]">
            <tr v-for="ticket in filteredTickets" :key="ticket.id" class="hover:bg-[var(--color-bg-tertiary)]">
              <td class="px-4 py-3 text-sm font-medium">#{{ ticket.id }}</td>
              <td class="px-4 py-3 text-sm truncate max-w-xs">{{ ticket.subject }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="getStatusClass(ticket.status)">
                  {{ $t(`ticketStatus.${ticket.status}`) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="text-sm" :class="getPriorityClass(ticket.priority)">
                  {{ $t(`priority.${ticket.priority || 'medium'}`) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-[var(--color-text-muted)]">
                {{ new Date(ticket.created_at).toLocaleDateString() }}
              </td>
            </tr>
            <tr v-if="filteredTickets.length === 0">
              <td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">
                {{ $t('tickets.noTickets') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const config = useRuntimeConfig()
const { token } = useAuth()

const loading = ref(false)
const tickets = ref<any[]>([])
const statusFilter = ref('')

const stats = ref({ total: 0, open: 0, resolved: 0, avgTime: '0h' })

const filteredTickets = computed(() => 
  statusFilter.value 
    ? tickets.value.filter(t => t.status === statusFilter.value)
    : tickets.value
)

const getStatusClass = (status: string) => ({
  'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400': status === 'pending',
  'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': status === 'in_progress',
  'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': status === 'completed',
  'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400': status === 'overdue',
})

const getPriorityClass = (priority: string) => ({
  'text-gray-600': priority === 'low',
  'text-amber-600': priority === 'medium',
  'text-red-600': priority === 'high' || priority === 'urgent',
})

const fetchData = async () => {
  loading.value = true
  try {
    // Fetch maintenance tickets
    const res = await $fetch<any>(`${config.public.apiBase}/tickets?type=maintenance&per_page=50`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    tickets.value = res.data || []
    
    // Calculate stats
    const open = tickets.value.filter(t => !['completed', 'closed_late'].includes(t.status)).length
    const today = new Date().toDateString()
    const resolved = tickets.value.filter(t => 
      t.status === 'completed' && new Date(t.completed_at).toDateString() === today
    ).length
    
    stats.value = {
      total: tickets.value.length,
      open,
      resolved,
      avgTime: '2.5h', // Placeholder - would need backend calculation
    }
  } catch (e) {
    console.error('Failed to fetch maintenance tickets:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
