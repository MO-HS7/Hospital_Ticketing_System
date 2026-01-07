<template>
  <NuxtLayout name="patient">
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('tickets.title') }}</h1>
        <p class="text-[var(--color-text-muted)] text-sm">{{ $t('tickets.subtitle') || 'View and manage all your support tickets.' }}</p>
      </div>
      <NuxtLink to="/patient/tickets/create" class="btn-primary flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        {{ $t('tickets.create') }}
      </NuxtLink>
    </div>

      <!-- Filters & Search -->
    <div class="card p-4 mb-6 flex flex-col sm:flex-row gap-4">
      <div class="relative flex-1">
        <svg class="w-5 h-5 text-[var(--color-text-muted)] absolute left-3 rtl:left-auto rtl:right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input 
          v-model="searchQuery" 
          type="text" 
          :placeholder="$t('tickets.searchPlaceholder')"
          class="input pl-10 rtl:pl-3 rtl:pr-10 w-full"
        />
      </div>
      <div class="flex gap-2 overflow-x-auto pb-1 sm:pb-0">
        <button 
          v-for="status in statuses" 
          :key="status.value"
          @click="activeStatus = status.value"
          class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors"
          :class="activeStatus === status.value 
            ? 'bg-primary-100/50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 border border-primary-200 dark:border-primary-800' 
            : 'text-[var(--color-text-muted)] hover:bg-[var(--color-bg-tertiary)]'"
        >
          {{ status.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="card p-4 flex gap-4">
        <Skeleton class="w-12 h-12 rounded-lg" />
        <div class="flex-1 space-y-2">
          <Skeleton class="w-1/3 h-5" />
          <Skeleton class="w-1/4 h-4" />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredTickets.length === 0" class="py-12">
      <EmptyState 
        :title="$t('tickets.empty')" 
        :description="$t('tickets.emptyFilter')"
      >
        <template #action>
          <button @click="clearFilters" class="text-primary-600 hover:text-primary-700 font-medium">
            {{ $t('filters.clear') }}
          </button>
        </template>
      </EmptyState>
    </div>

    <!-- Tickets List -->
    <div v-else class="space-y-4">
      <div 
        v-for="ticket in paginatedTickets" 
        :key="ticket.id" 
        class="card p-4 hover:border-primary-200 dark:hover:border-primary-800 transition-colors group cursor-pointer"
        @click="navigateTo(`/patient/tickets/${ticket.id}`)"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex gap-4">
            <div 
              class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 font-bold text-lg"
              :class="getStatusBg(ticket.status)"
            >
              {{ ticket.subject.charAt(0).toUpperCase() }}
            </div>
            <div>
              <h3 class="font-semibold text-[var(--color-text-primary)] group-hover:text-primary-600 transition-colors">
                {{ ticket.subject }}
              </h3>
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm text-[var(--color-text-muted)]">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                  {{ locale === 'ar' ? ticket.department?.name_ar : ticket.department?.name_en }}
                </span>
                <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  {{ formatDate(ticket.created_at) }}
                </span>
              </div>
            </div>
          </div>
          <div class="flex flex-col items-end gap-2">
            <Badge :color="getStatusColor(ticket.status)" :label="$t(`ticketStatus.${ticket.status}`)" dot />
            <span class="text-xs text-[var(--color-text-muted)]">#{{ ticket.id }}</span>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center gap-2 mt-8">
        <button 
          @click="page--" 
          :disabled="page === 1"
          class="p-2 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg-tertiary)] disabled:opacity-50"
        >
          <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </button>
        <span class="px-4 py-2 text-sm font-medium text-[var(--color-text-secondary)]">
          {{ page }} / {{ totalPages }}
        </span>
        <button 
          @click="page++" 
          :disabled="page === totalPages"
          class="p-2 rounded-lg border border-[var(--color-border)] hover:bg-[var(--color-bg-tertiary)] disabled:opacity-50"
        >
          <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        </button>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import Skeleton from '~/components/ui/Skeleton.vue'
import EmptyState from '~/components/ui/EmptyState.vue'
import Badge from '~/components/ui/Badge.vue'

definePageMeta({ layout: false, middleware: ['auth'] })

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale, t } = useI18n()

const loading = ref(true)
const tickets = ref<any[]>([])
const searchQuery = ref('')
const activeStatus = ref('all')
const page = ref(1)
const itemsPerPage = 10

const statuses = computed(() => [
  { label: t('filters.all'), value: 'all' },
  { label: t('filters.status') + ' ' + t('ticketStatus.pending'), value: 'pending' },
  { label: t('dashboard.active'), value: 'active' }, // open + in_progress
  { label: t('dashboard.completed'), value: 'completed' }, // resolved + closed
])

const filteredTickets = computed(() => {
  let result = tickets.value

  // Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(t => 
      t.subject.toLowerCase().includes(q) || 
      String(t.id).includes(q)
    )
  }

  // Status Filter
  if (activeStatus.value !== 'all') {
    if (activeStatus.value === 'active') {
      result = result.filter(t => ['in_progress', 'overdue'].includes(t.status))
    } else if (activeStatus.value === 'completed') {
      result = result.filter(t => ['completed', 'closed_late'].includes(t.status))
    } else if (activeStatus.value === 'pending') {
      result = result.filter(t => ['pending', 'assigned', 'awaiting_payment'].includes(t.status))
    } else {
      result = result.filter(t => t.status === activeStatus.value)
    }
  }

  return result
})

const totalPages = computed(() => Math.ceil(filteredTickets.value.length / itemsPerPage))

const paginatedTickets = computed(() => {
  const start = (page.value - 1) * itemsPerPage
  return filteredTickets.value.slice(start, start + itemsPerPage)
})

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

const getStatusBg = (status: string) => {
  switch (status) {
    case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
    case 'assigned': return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'
    case 'awaiting_payment': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
    case 'in_progress': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
    case 'completed': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    case 'closed_late': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
    case 'overdue': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    default: return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
  }
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString(locale.value, {
    month: 'short', day: 'numeric', year: 'numeric'
  })
}

const clearFilters = () => {
  searchQuery.value = ''
  activeStatus.value = 'all'
}

onMounted(async () => {
  loading.value = true
  try {
    const response = await $fetch<{ data: any[] }>(`${config.public.apiBase}/tickets`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    tickets.value = response.data || []
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})

// Debounce search in watch if needed, but computed is fast for client-side list of tickets (<100)
</script>
