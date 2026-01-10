<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: ['auth'] })

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const { token } = useAuth()

// Reactive filters
const filters = reactive({
  department: route.query.department as string || '',
  status: route.query.status as string || '',
  priority: route.query.priority as string || '',
  source: route.query.source as string || '',
  sla: route.query.sla as string || '',
  search: route.query.search as string || ''
})

// Data
const tickets = ref<any[]>([])
const departments = ref<any[]>([])
const loading = ref(true)
const error = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

// Options
const statusOptions = computed(() => [
  { value: '', label: t('adminTickets.allStatuses') },
  { value: 'pending', label: t('ticketStatus.pending') },
  { value: 'in_progress', label: t('ticketStatus.in_progress') },
  { value: 'completed', label: t('ticketStatus.completed') },
  { value: 'overdue', label: t('ticketStatus.overdue') },
  { value: 'awaiting_payment', label: t('ticketStatus.awaiting_payment') }
])

const priorityOptions = computed(() => [
  { value: '', label: t('adminTickets.allPriorities') },
  { value: 'low', label: t('priority.low') },
  { value: 'medium', label: t('priority.medium') },
  { value: 'high', label: t('priority.high') },
  { value: 'urgent', label: t('priority.urgent') }
])

const sourceOptions = computed(() => [
  { value: '', label: t('adminTickets.allSources') },
  { value: 'manual', label: t('commandCenter.manual') },
  { value: 'chatbot', label: t('commandCenter.chatbot') }
])

const slaOptions = computed(() => [
  { value: '', label: t('adminTickets.allSLA') },
  { value: 'at_risk', label: t('adminTickets.atRisk') },
  { value: 'breached', label: t('adminTickets.breached') }
])

const hasActiveFilters = computed(() => 
  filters.department || filters.status || filters.priority || 
  filters.source || filters.sla || filters.search
)

// Dynamic title
const pageTitle = computed(() => {
  const parts: string[] = []
  if (filters.sla === 'at_risk') parts.push(t('adminTickets.slaAtRisk'))
  else if (filters.sla === 'breached') parts.push(t('adminTickets.slaBreached'))
  if (filters.status) parts.push(t(`ticketStatus.${filters.status}`))
  if (filters.priority === 'urgent') parts.push(t('adminTickets.urgentTickets'))
  if (filters.source) parts.push(t(`commandCenter.${filters.source}`))
  if (filters.department) {
    const dept = departments.value.find((d: any) => d.id === filters.department)
    if (dept) parts.push(locale.value === 'ar' ? dept.name_ar : dept.name_en)
  }
  return parts.length ? parts.join(' – ') : t('adminTickets.allTickets')
})

// Load tickets
const loadTickets = async (page = 1) => {
  loading.value = true
  error.value = ''
  
  try {
    const params = new URLSearchParams()
    if (filters.department) params.append('department', filters.department)
    if (filters.status) params.append('status', filters.status)
    if (filters.priority) params.append('priority', filters.priority)
    if (filters.source) params.append('source', filters.source)
    if (filters.sla) params.append('sla', filters.sla)
    if (filters.search) params.append('search', filters.search)
    params.append('page', String(page))
    
    const res = await $fetch<any>(`${config.public.apiBase}/tickets?${params.toString()}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    
    tickets.value = res?.data || []
    pagination.value = {
      current_page: res?.current_page || 1,
      last_page: res?.last_page || 1,
      total: res?.total || 0
    }
  } catch (e: any) {
    error.value = e.message || t('adminTickets.loadError')
  } finally {
    loading.value = false
  }
}

// Load departments
const loadDepartments = async () => {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/departments`)
    departments.value = res?.data || res || []
  } catch (e) {
    departments.value = []
  }
}

// Filter change handler - updates URL and reloads
const onFilterChange = () => {
  const query: Record<string, string> = {}
  if (filters.department) query.department = filters.department
  if (filters.status) query.status = filters.status
  if (filters.priority) query.priority = filters.priority
  if (filters.source) query.source = filters.source
  if (filters.sla) query.sla = filters.sla
  if (filters.search) query.search = filters.search
  router.replace({ query })
  loadTickets()
}

// Reset all filters
const resetFilters = () => {
  filters.department = ''
  filters.status = ''
  filters.priority = ''
  filters.source = ''
  filters.sla = ''
  filters.search = ''
  router.replace({ query: {} })
  loadTickets()
}

// Navigate to ticket details
const viewTicket = (id: string | number) => {
  router.push(`/admin/tickets/${id}`)
}

// Helpers
const getStatusClass = (status: string) => {
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    overdue: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    awaiting_payment: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
  }
  return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
}

const getPriorityClass = (priority: string) => {
  const map: Record<string, string> = { urgent: 'text-red-600', high: 'text-amber-600', medium: 'text-blue-600' }
  return map[priority] || 'text-gray-500'
}

const getSourceIcon = (source: string) => source === 'chatbot' ? 'robot' : 'user'

const formatDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const getSlaStatus = (ticket: any) => {
  if (!ticket.deadline || ticket.completed_at) return null
  const diff = new Date(ticket.deadline).getTime() - Date.now()
  const mins = Math.floor(diff / 60000)
  if (mins < 0) return { label: t('adminTickets.breached'), class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }
  if (mins <= 30) return { label: `${mins}m`, class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' }
  return null
}

onMounted(async () => {
  await loadDepartments()
  await loadTickets()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-[var(--color-text-primary)]">{{ pageTitle }}</h1>
        <p class="text-sm text-[var(--color-text-muted)]">{{ pagination.total }} {{ t('nav.tickets') }}</p>
      </div>
      <button @click="loadTickets()" class="btn-ghost text-sm">
        <Icon name="arrows-rotate" size="sm" class="me-1" />
        {{ t('common.refresh') }}
      </button>
    </div>
    
    <!-- Single-Row Filter Bar -->
    <div class="card px-3 py-2.5 flex flex-wrap items-center gap-2">
      <!-- Search -->
      <div class="relative">
        <Icon name="magnifying-glass" size="xs" class="absolute start-2 top-1/2 -translate-y-1/2 text-[var(--color-text-muted)]" />
        <input 
          v-model="filters.search"
          type="text"
          :placeholder="t('common.search')"
          class="ps-7 pe-2 py-1 w-36 rounded border border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-sm"
          @keyup.enter="onFilterChange"
        />
      </div>
      
      <!-- Department -->
      <select 
        v-model="filters.department"
        class="py-1 px-2 rounded border border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-sm"
        @change="onFilterChange"
      >
        <option value="">{{ t('adminTickets.allDepartments') }}</option>
        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
          {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
        </option>
      </select>
      
      <!-- Status -->
      <select v-model="filters.status" class="py-1 px-2 rounded border border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-sm" @change="onFilterChange">
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      
      <!-- Priority -->
      <select v-model="filters.priority" class="py-1 px-2 rounded border border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-sm" @change="onFilterChange">
        <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      
      <!-- Source -->
      <select v-model="filters.source" class="py-1 px-2 rounded border border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-sm" @change="onFilterChange">
        <option v-for="opt in sourceOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      
      <!-- SLA -->
      <select v-model="filters.sla" class="py-1 px-2 rounded border border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-sm" @change="onFilterChange">
        <option v-for="opt in slaOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      
      <!-- Reset -->
      <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-red-600 hover:underline ms-auto">
        {{ t('adminTickets.resetFilters') }}
      </button>
    </div>
    
    <!-- Table -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="p-6 space-y-3">
        <div v-for="i in 5" :key="i" class="animate-pulse flex items-center gap-3">
          <div class="h-6 w-6 bg-[var(--color-bg-tertiary)] rounded"></div>
          <div class="flex-1 h-4 bg-[var(--color-bg-tertiary)] rounded"></div>
          <div class="w-16 h-4 bg-[var(--color-bg-tertiary)] rounded"></div>
        </div>
      </div>
      
      <div v-else-if="error" class="p-8 text-center">
        <Icon name="exclamation-triangle" size="lg" class="text-red-500 mb-2" />
        <p class="text-[var(--color-text-muted)]">{{ error }}</p>
        <button @click="loadTickets()" class="btn-primary mt-3 text-sm">{{ t('common.retry') }}</button>
      </div>
      
      <div v-else-if="tickets.length === 0" class="p-8 text-center">
        <Icon name="inbox" size="xl" class="text-[var(--color-text-muted)] opacity-40 mb-2" />
        <p class="font-medium text-[var(--color-text-primary)]">{{ t('adminTickets.noTickets') }}</p>
        <p class="text-sm text-[var(--color-text-muted)]">{{ t('adminTickets.noTicketsDesc') }}</p>
      </div>
      
      <table v-else class="w-full text-sm">
        <thead class="bg-[var(--color-bg-tertiary)] text-xs text-[var(--color-text-muted)] uppercase">
          <tr>
            <th class="px-3 py-2 text-start">ID</th>
            <th class="px-3 py-2 text-start">{{ t('tickets.subject') }}</th>
            <th class="px-3 py-2 text-start">{{ t('tickets.department') }}</th>
            <th class="px-3 py-2 text-start">{{ t('tickets.status') }}</th>
            <th class="px-3 py-2 text-start">{{ t('tickets.priority') }}</th>
            <th class="px-3 py-2 text-start">SLA</th>
            <th class="px-3 py-2 text-start">{{ t('tickets.createdAt') }}</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--color-border)]">
          <tr 
            v-for="ticket in tickets" 
            :key="ticket.id"
            class="hover:bg-[var(--color-bg-tertiary)]/50 cursor-pointer"
            @click="viewTicket(ticket.id)"
          >
            <td class="px-3 py-2">
              <span class="flex items-center gap-1 text-xs">
                <Icon :name="getSourceIcon(ticket.source)" size="xs" class="text-[var(--color-text-muted)]" />
                #{{ String(ticket.id).slice(-4) }}
              </span>
            </td>
            <td class="px-3 py-2 font-medium text-[var(--color-text-primary)] max-w-[200px] truncate">{{ ticket.subject }}</td>
            <td class="px-3 py-2 text-[var(--color-text-secondary)]">{{ locale === 'ar' ? ticket.department?.name_ar : ticket.department?.name_en }}</td>
            <td class="px-3 py-2">
              <span :class="['px-1.5 py-0.5 rounded text-xs font-medium', getStatusClass(ticket.status)]">
                {{ t(`ticketStatus.${ticket.status}`) }}
              </span>
            </td>
            <td class="px-3 py-2">
              <span :class="['flex items-center gap-1', getPriorityClass(ticket.priority)]">
                <Icon :name="ticket.priority === 'urgent' ? 'bolt' : 'flag'" size="xs" />
                {{ t(`priority.${ticket.priority}`) }}
              </span>
            </td>
            <td class="px-3 py-2">
              <span v-if="getSlaStatus(ticket)" :class="['px-1.5 py-0.5 rounded text-xs font-medium', getSlaStatus(ticket)?.class]">
                {{ getSlaStatus(ticket)?.label }}
              </span>
              <span v-else class="text-[var(--color-text-muted)]">—</span>
            </td>
            <td class="px-3 py-2 text-[var(--color-text-muted)]">{{ formatDate(ticket.created_at) }}</td>
            <td class="px-3 py-2 text-end">
              <button class="btn-ghost text-xs px-2 py-0.5" @click.stop="viewTicket(ticket.id)">
                {{ t('common.view') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="!loading && pagination.last_page > 1" class="flex items-center justify-between px-3 py-2 border-t border-[var(--color-border)] text-sm">
        <span class="text-[var(--color-text-muted)]">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <div class="flex gap-1">
          <button :disabled="pagination.current_page === 1" class="btn-ghost text-xs px-2 disabled:opacity-40" @click="loadTickets(pagination.current_page - 1)">
            {{ t('common.previous') }}
          </button>
          <button :disabled="pagination.current_page === pagination.last_page" class="btn-ghost text-xs px-2 disabled:opacity-40" @click="loadTickets(pagination.current_page + 1)">
            {{ t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
