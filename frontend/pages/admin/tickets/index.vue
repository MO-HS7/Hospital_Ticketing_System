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
  <div class="space-y-4 md:space-y-6">
    <!-- Page Header: Dynamic Title (contextual) + Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <p class="text-sm text-slate-500 dark:text-white/50 truncate">{{ pageTitle }}</p>
        <span class="text-xs text-slate-500 dark:text-white/40 bg-slate-100 dark:bg-white/10 px-2.5 py-1 rounded-lg whitespace-nowrap">
          {{ pagination.total }} {{ t('nav.tickets') }}
        </span>
      </div>
      <button @click="loadTickets()" class="h-9 px-3 rounded-lg flex items-center gap-1.5 text-sm text-slate-600 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/10 transition shrink-0">
        <Icon name="arrows-rotate" size="sm" />
        {{ t('common.refresh') }}
      </button>
    </div>
    
    <!-- Filter Bar Card -->
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3">
        <!-- Search -->
        <div class="sm:col-span-2 md:col-span-3 relative">
          <Icon name="magnifying-glass" size="sm" class="absolute start-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-white/40" />
          <input 
            v-model="filters.search"
            type="text"
            :placeholder="t('common.search')"
            class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 ps-10 pe-4 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-white/40 shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition"
            @keyup.enter="onFilterChange"
          />
        </div>
        
        <!-- Department -->
        <div class="md:col-span-2">
          <select 
            v-model="filters.department"
            class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none"
            @change="onFilterChange"
          >
            <option value="">{{ t('adminTickets.allDepartments') }}</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
            </option>
          </select>
        </div>
        
        <!-- Status -->
        <div class="md:col-span-2">
          <select 
            v-model="filters.status" 
            class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none" 
            @change="onFilterChange"
          >
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        
        <!-- Priority -->
        <div class="md:col-span-2">
          <select 
            v-model="filters.priority" 
            class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none" 
            @change="onFilterChange"
          >
            <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        
        <!-- Source -->
        <div class="md:col-span-1">
          <select 
            v-model="filters.source" 
            class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none" 
            @change="onFilterChange"
          >
            <option v-for="opt in sourceOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        
        <!-- SLA -->
        <div class="md:col-span-2">
          <select 
            v-model="filters.sla" 
            class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none" 
            @change="onFilterChange"
          >
            <option v-for="opt in slaOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
      </div>
      
      <!-- Reset Filters -->
      <div v-if="hasActiveFilters" class="flex justify-end mt-3 pt-3 border-t border-slate-100 dark:border-white/5">
        <button @click="resetFilters" class="h-9 px-3 rounded-lg text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition flex items-center gap-1.5">
          <Icon name="x" size="sm" />
          {{ t('adminTickets.resetFilters') }}
        </button>
      </div>
    </div>
    
    <!-- Table Card -->
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 overflow-hidden">
      <!-- Loading -->
      <div v-if="loading" class="p-8 space-y-3">
        <div v-for="i in 5" :key="i" class="animate-pulse flex items-center gap-3">
          <div class="h-6 w-6 bg-slate-200 dark:bg-white/10 rounded"></div>
          <div class="flex-1 h-4 bg-slate-200 dark:bg-white/10 rounded"></div>
          <div class="w-16 h-4 bg-slate-200 dark:bg-white/10 rounded"></div>
        </div>
      </div>
      
      <!-- Error -->
      <div v-else-if="error" class="py-16 px-4 text-center">
        <Icon name="exclamation-triangle" size="lg" class="text-red-500 mb-3" />
        <p class="text-slate-500 dark:text-white/50 mb-4">{{ error }}</p>
        <button @click="loadTickets()" class="h-10 px-4 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium transition">{{ t('common.retry') }}</button>
      </div>
      
      <!-- Empty -->
      <div v-else-if="tickets.length === 0" class="py-16 px-4 text-center">
        <Icon name="inbox" size="xl" class="text-slate-300 dark:text-white/20 mb-4" />
        <p class="font-medium text-slate-700 dark:text-white mb-1">{{ t('adminTickets.noTickets') }}</p>
        <p class="text-sm text-slate-500 dark:text-white/50">{{ t('adminTickets.noTicketsDesc') }}</p>
      </div>
      
      <!-- Table -->
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
          <tr>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider">ID</th>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider">{{ t('tickets.subject') }}</th>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider hidden md:table-cell">{{ t('tickets.department') }}</th>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider">{{ t('tickets.status') }}</th>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider hidden lg:table-cell">{{ t('tickets.priority') }}</th>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider hidden lg:table-cell">SLA</th>
            <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider hidden xl:table-cell">{{ t('tickets.createdAt') }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
          <tr 
            v-for="ticket in tickets" 
            :key="ticket.id"
            class="hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer transition"
            @click="viewTicket(ticket.id)"
          >
            <td class="px-4 py-3.5">
              <span class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-white/50">
                <Icon :name="getSourceIcon(ticket.source)" size="xs" />
                #{{ String(ticket.id).slice(-4) }}
              </span>
            </td>
            <td class="px-4 py-3.5 font-medium text-slate-900 dark:text-white max-w-[200px] truncate">{{ ticket.subject }}</td>
            <td class="px-4 py-3.5 text-slate-500 dark:text-white/50 hidden md:table-cell">{{ locale === 'ar' ? ticket.department?.name_ar : ticket.department?.name_en }}</td>
            <td class="px-4 py-3.5">
              <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', getStatusClass(ticket.status)]">
                {{ t(`ticketStatus.${ticket.status}`) }}
              </span>
            </td>
            <td class="px-4 py-3.5 hidden lg:table-cell">
              <span :class="['flex items-center gap-1', getPriorityClass(ticket.priority)]">
                <Icon :name="ticket.priority === 'urgent' ? 'bolt' : 'flag'" size="xs" />
                {{ t(`priority.${ticket.priority}`) }}
              </span>
            </td>
            <td class="px-4 py-3.5 hidden lg:table-cell">
              <span v-if="getSlaStatus(ticket)" :class="['px-2 py-0.5 rounded text-xs font-medium', getSlaStatus(ticket)?.class]">
                {{ getSlaStatus(ticket)?.label }}
              </span>
              <span v-else class="text-slate-400 dark:text-white/30">—</span>
            </td>
            <td class="px-4 py-3.5 text-slate-500 dark:text-white/50 hidden xl:table-cell">{{ formatDate(ticket.created_at) }}</td>
            <td class="px-4 py-3.5 text-end">
              <button class="h-8 px-2.5 rounded-lg text-xs font-medium text-slate-600 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/10 transition" @click.stop="viewTicket(ticket.id)">
                {{ t('common.view') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="!loading && pagination.last_page > 1" class="p-4 border-t border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50 dark:bg-white/5">
        <span class="text-sm text-slate-500 dark:text-white/50">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="pagination.current_page === 1" class="h-9 px-3 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition" @click="loadTickets(pagination.current_page - 1)">
            {{ t('common.previous') }}
          </button>
          <button :disabled="pagination.current_page === pagination.last_page" class="h-9 px-3 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition" @click="loadTickets(pagination.current_page + 1)">
            {{ t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
