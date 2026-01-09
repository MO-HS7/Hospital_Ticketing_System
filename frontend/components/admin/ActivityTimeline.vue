<template>
  <div class="activity-timeline">
    <!-- Filters -->
    <div v-if="showFilters" class="flex flex-wrap gap-2 mb-4">
      <select v-model="filters.source" class="input text-sm py-1.5 px-3">
        <option value="">{{ $t('filters.all') }} {{ $t('commandCenter.sources') }}</option>
        <option value="manual">{{ $t('commandCenter.manual') }}</option>
        <option value="chatbot">{{ $t('commandCenter.chatbot') }}</option>
      </select>
      <select v-if="departments.length > 0" v-model="filters.department_id" class="input text-sm py-1.5 px-3">
        <option value="">{{ $t('filters.all') }} {{ $t('nav.departments') }}</option>
        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ getName(dept) }}</option>
      </select>
      <select v-model="filters.event_type" class="input text-sm py-1.5 px-3">
        <option value="">{{ $t('audit.allEvents') }}</option>
        <option value="created">{{ $t('audit.events.created') }}</option>
        <option value="accepted">{{ $t('audit.events.accepted') }}</option>
        <option value="completed">{{ $t('audit.events.completed') }}</option>
        <option value="status_changed">{{ $t('audit.events.status_changed') }}</option>
      </select>
    </div>

    <!-- Timeline -->
    <div class="space-y-3">
      <TransitionGroup name="list">
        <div 
          v-for="event in events" 
          :key="event.id"
          class="flex gap-3 p-3 rounded-lg bg-[var(--color-bg-secondary)] hover:bg-[var(--color-bg-tertiary)] transition-colors"
        >
          <!-- Event icon -->
          <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center" :class="getEventIconBg(event.event_type)">
            <component :is="getEventIcon(event.event_type)" class="w-4 h-4" :class="getEventIconColor(event.event_type)" />
          </div>
          
          <!-- Event content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-medium text-sm">{{ event.user?.name || $t('audit.system') }}</span>
              <span class="text-xs text-[var(--color-text-muted)]">{{ $t(`audit.events.${event.event_type}`) }}</span>
              <SourceBadge 
                v-if="event.ticket?.source" 
                :manual="event.ticket.source === 'manual' ? 1 : 0" 
                :chatbot="event.ticket.source === 'chatbot' ? 1 : 0" 
                size="sm"
              />
            </div>
            <p class="text-sm text-[var(--color-text-secondary)] truncate mt-0.5">
              {{ $t('audit.ticket') }} #{{ event.ticket_id }}
              <template v-if="event.ticket?.subject">— {{ event.ticket.subject }}</template>
            </p>
            <p class="text-xs text-[var(--color-text-muted)] mt-1">
              {{ formatRelativeTime(event.created_at) }}
              <template v-if="event.ticket?.department">
                · {{ locale === 'ar' ? event.ticket.department.name_ar : event.ticket.department.name_en }}
              </template>
            </p>
          </div>
        </div>
      </TransitionGroup>
      
      <!-- Empty state -->
      <div v-if="!loading && events.length === 0" class="text-center py-8 text-[var(--color-text-muted)]">
        {{ $t('doctorPortal.noActivity') }}
      </div>
      
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-4">
        <svg class="animate-spin w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>
      
      <!-- Load more -->
      <button 
        v-if="hasMore && !loading" 
        @click="loadMore" 
        class="btn-ghost w-full text-sm"
      >
        {{ $t('common.viewAll') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import SourceBadge from './SourceBadge.vue'

interface Event {
  id: number
  ticket_id: number
  user_id: number | null
  event_type: string
  meta: any
  created_at: string
  user?: { id: number; name: string }
  ticket?: {
    id: number
    subject: string
    type: string
    department_id: string
    source?: string
    department?: { id: string; name_en: string; name_ar: string }
  }
}

interface Props {
  showFilters?: boolean
}

withDefaults(defineProps<Props>(), {
  showFilters: true,
})

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()
const { departments, fetchDepartments, getName } = useDepartments()

const events = ref<Event[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)

const filters = reactive({
  source: '',
  department_id: '',
  event_type: '',
})

const fetchEvents = async (reset = false) => {
  if (reset) {
    page.value = 1
    events.value = []
  }
  
  loading.value = true
  try {
    const params = new URLSearchParams({ page: String(page.value), per_page: '10' })
    if (filters.source) params.append('source', filters.source)
    if (filters.department_id) params.append('department_id', filters.department_id)
    if (filters.event_type) params.append('event_type', filters.event_type)
    
    const res = await $fetch<any>(`${config.public.apiBase}/admin/activity-feed?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    
    if (reset) {
      events.value = res.data
    } else {
      events.value.push(...res.data)
    }
    
    hasMore.value = res.current_page < res.last_page
  } catch (e) {
    console.error('Failed to fetch activity feed:', e)
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  page.value++
  fetchEvents()
}

// Debounced filter watch
let filterTimer: ReturnType<typeof setTimeout>
watch(filters, () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => fetchEvents(true), 300)
})

const formatRelativeTime = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffMins < 1) return locale.value === 'ar' ? 'الآن' : 'Just now'
  if (diffMins < 60) return locale.value === 'ar' ? `منذ ${diffMins} دقيقة` : `${diffMins}m ago`
  if (diffHours < 24) return locale.value === 'ar' ? `منذ ${diffHours} ساعة` : `${diffHours}h ago`
  return locale.value === 'ar' ? `منذ ${diffDays} يوم` : `${diffDays}d ago`
}

// Icon helpers
const CreatedIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>` }
const AcceptedIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>` }
const CompletedIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>` }
const StatusIcon = { template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>` }

const getEventIcon = (type: string) => {
  switch (type) {
    case 'created': return CreatedIcon
    case 'accepted': return AcceptedIcon
    case 'completed': return CompletedIcon
    default: return StatusIcon
  }
}

const getEventIconBg = (type: string) => ({
  'bg-green-100 dark:bg-green-900/30': type === 'created',
  'bg-blue-100 dark:bg-blue-900/30': type === 'accepted',
  'bg-emerald-100 dark:bg-emerald-900/30': type === 'completed',
  'bg-amber-100 dark:bg-amber-900/30': !['created', 'accepted', 'completed'].includes(type),
})

const getEventIconColor = (type: string) => ({
  'text-green-600': type === 'created',
  'text-blue-600': type === 'accepted',
  'text-emerald-600': type === 'completed',
  'text-amber-600': !['created', 'accepted', 'completed'].includes(type),
})

onMounted(async () => {
  await fetchDepartments()
  await fetchEvents()
})
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.list-leave-to {
  opacity: 0;
  transform: translateX(10px);
}
</style>
