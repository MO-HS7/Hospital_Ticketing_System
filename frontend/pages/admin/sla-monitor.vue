<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('commandCenter.slaMonitor') }}</h1>
          <p class="text-sm text-[var(--color-text-muted)] mt-1">{{ $t('commandCenter.slaMonitorSubtitle') }}</p>
        </div>
        <button @click="fetchData" class="btn-ghost p-2" :disabled="loading">
          <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>

      <!-- Stats Row -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-red-600">{{ stats.critical }}</p>
              <p class="text-xs text-[var(--color-text-muted)]">Critical (< 15min)</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-amber-600">{{ stats.warning }}</p>
              <p class="text-xs text-[var(--color-text-muted)]">Warning (< 30min)</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-blue-600">{{ stats.onTrack }}</p>
              <p class="text-xs text-[var(--color-text-muted)]">On Track</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-600">{{ stats.breached }}</p>
              <p class="text-xs text-[var(--color-text-muted)]">SLA Breached</p>
            </div>
          </div>
        </div>
      </div>

      <!-- At-Risk Tickets Table -->
      <div class="card overflow-hidden">
        <div class="p-4 border-b border-[var(--color-border)]">
          <h3 class="font-semibold">{{ $t('commandCenter.ticketsAtRisk') }}</h3>
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
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.department') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.subject') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.deadline') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">SLA</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-border)]">
            <tr v-for="ticket in atRiskTickets" :key="ticket.id" class="hover:bg-[var(--color-bg-tertiary)]">
              <td class="px-4 py-3 text-sm font-medium">#{{ ticket.id }}</td>
              <td class="px-4 py-3 text-sm">{{ getDeptName(ticket.department) }}</td>
              <td class="px-4 py-3 text-sm truncate max-w-xs">{{ ticket.subject }}</td>
              <td class="px-4 py-3 text-sm">{{ formatDateTime(ticket.deadline) }}</td>
              <td class="px-4 py-3">
                <SLAIndicator :deadline="ticket.deadline" />
              </td>
            </tr>
            <tr v-if="atRiskTickets.length === 0">
              <td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">
                {{ $t('commandCenter.noRisks') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import SLAIndicator from '~/components/admin/SLAIndicator.vue'

definePageMeta({ layout: false, middleware: ['auth'] })

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()

const loading = ref(false)
const atRiskTickets = ref<any[]>([])
const stats = ref({ critical: 0, warning: 0, onTrack: 0, breached: 0 })

const getDeptName = (dept: any) => 
  locale.value === 'ar' ? (dept?.name_ar || dept?.name_en || '-') : (dept?.name_en || dept?.name_ar || '-')

const formatDateTime = (date: string) => 
  new Date(date).toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', { 
    dateStyle: 'short', 
    timeStyle: 'short' 
  })

const fetchData = async () => {
  loading.value = true
  try {
    // Fetch metrics which includes overdue tickets
    const res = await $fetch<any>(`${config.public.apiBase}/admin/metrics`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    
    atRiskTickets.value = res.overdue_tickets || []
    
    // Calculate stats from insights
    const insights = await $fetch<any>(`${config.public.apiBase}/admin/insights`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    
    stats.value = {
      critical: insights.emergency_pending?.count || 0,
      warning: insights.at_risk?.count || 0,
      onTrack: (res.tickets?.total || 0) - (res.tickets?.overdue || 0) - (insights.at_risk?.count || 0),
      breached: res.tickets?.overdue || 0,
    }
  } catch (e) {
    console.error('Failed to fetch SLA data:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
