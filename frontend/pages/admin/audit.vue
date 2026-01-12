<template>
  <NuxtLayout name="admin">
    <div class="space-y-4 md:space-y-6">
      <!-- Page Header: Subtitle + Actions (Title from layout) -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <p class="text-sm text-slate-500 dark:text-white/50">{{ $t('audit.subtitle') }}</p>
          <span class="text-xs text-slate-500 dark:text-white/40 bg-slate-100 dark:bg-white/10 px-2.5 py-1 rounded-lg whitespace-nowrap">
            {{ (meta?.total ?? 0).toLocaleString() }} {{ $t('audit.totalRecords') }}
          </span>
        </div>
        
        <!-- Actions Section -->
        <div class="flex items-center gap-2 shrink-0" :class="{ 'flex-row-reverse': isRtl }">
          <!-- Export Button -->
          <button 
            @click="exportCsv"
            class="h-9 w-9 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-white/10 hover:bg-slate-200 dark:hover:bg-white/20 text-slate-600 dark:text-white/60 hover:text-slate-900 dark:hover:text-white transition"
            :disabled="exporting || !filters.from || !filters.to"
            :title="$t('audit.export.button')"
          >
            <Icon v-if="exporting" name="refresh" class="animate-spin" size="sm" />
            <Icon v-else name="download" size="sm" />
          </button>
          
          <!-- Refresh Button -->
          <button 
            @click="fetchEvents"
            class="h-9 w-9 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-white/10 hover:bg-slate-200 dark:hover:bg-white/20 text-slate-600 dark:text-white/60 hover:text-slate-900 dark:hover:text-white transition"
            :disabled="loading"
            :title="$t('audit.refresh')"
          >
            <Icon name="refresh" size="sm" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>

      <!-- Filter Bar Card -->
      <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
        <!-- 12-Column Responsive Grid -->
        <div class="grid grid-cols-12 gap-3">
          <!-- Search (spans 12 on mobile, 6 on md, 3 on lg) -->
          <div class="col-span-12 md:col-span-6 lg:col-span-3">
            <input
              v-model="filters.q"
              type="text"
              :placeholder="$t('audit.searchPlaceholder')"
              class="h-11 w-full min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-4 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-white/40 shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition"
            />
          </div>
          
          <!-- Action Type (spans 6 on mobile, 3 on md, 2 on lg) -->
          <div class="col-span-6 md:col-span-3 lg:col-span-2">
            <select 
              v-model="filters.eventType"
              class="h-11 w-full min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none"
              @change="applyFilters"
            >
              <option value="">{{ $t('audit.filters.allActions') }}</option>
              <option value="created">{{ $t('audit.events.created') }}</option>
              <option value="accepted">{{ $t('audit.events.accepted') }}</option>
              <option value="started">{{ $t('audit.events.started') }}</option>
              <option value="completed">{{ $t('audit.events.completed') }}</option>
              <option value="status_changed">{{ $t('audit.events.status_changed') }}</option>
              <option value="note_added">{{ $t('audit.events.note_added') }}</option>
              <option value="priority_changed">{{ $t('audit.events.priority_changed') }}</option>
              <option value="assigned">{{ $t('audit.events.assigned') }}</option>
              <option value="cancelled">{{ $t('audit.events.cancelled') }}</option>
            </select>
          </div>
          
          <!-- Role (spans 6 on mobile, 3 on md, 2 on lg) -->
          <div class="col-span-6 md:col-span-3 lg:col-span-2">
            <select 
              v-model="filters.role"
              class="h-11 w-full min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none"
              @change="applyFilters"
            >
              <option value="">{{ $t('audit.allRoles') }}</option>
              <option value="admin">{{ $t('roles.admin') }}</option>
              <option value="doctor">{{ $t('roles.doctor') }}</option>
              <option value="reception">{{ $t('roles.reception') }}</option>
              <option value="maintenance">{{ $t('roles.maintenance') }}</option>
              <option value="patient">{{ $t('roles.patient') }}</option>
            </select>
          </div>
          
          <!-- Department (spans 12 on mobile, 6 on md, 2 on lg) -->
          <div class="col-span-12 md:col-span-6 lg:col-span-2">
            <select 
              v-model="filters.departmentId"
              class="h-11 w-full min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none"
              :disabled="departmentsLoading"
              @change="applyFilters"
            >
              <option value="">{{ departmentsLoading ? $t('common.loading') : $t('audit.allDepartments') }}</option>
              <option v-if="!departmentsLoading && departments.length === 0" value="" disabled>{{ $t('common.noResults') }}</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
              </option>
            </select>
          </div>
          
          <!-- Date Range Group (From + To together) (spans 12 on mobile, 6 on md, 3 on lg) -->
          <div class="col-span-12 md:col-span-6 lg:col-span-3">
            <div class="flex gap-2">
              <input
                v-model="filters.from"
                type="date"
                :title="$t('audit.dateFrom')"
                class="h-11 w-full min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition"
                @change="applyFilters"
              />
              <input
                v-model="filters.to"
                type="date"
                :title="$t('audit.dateTo')"
                class="h-11 w-full min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition"
                @change="applyFilters"
              />
            </div>
          </div>
        </div>
        
        <!-- Filter Actions Row -->
        <div class="flex flex-wrap items-center gap-3 mt-3 pt-3 border-t border-slate-100 dark:border-white/5">
          <!-- Has Diff Checkbox -->
          <label class="flex items-center gap-2 text-sm text-slate-500 dark:text-white/50 cursor-pointer hover:text-slate-700 dark:hover:text-white transition">
            <input 
              type="checkbox" 
              v-model="filters.hasDiff"
              class="w-4 h-4 rounded border-slate-300 dark:border-white/20 bg-white dark:bg-black/20 text-primary-600 focus:ring-primary-500 focus:ring-offset-0"
              @change="applyFilters"
            />
            {{ $t('audit.onlyWithChanges') }}
          </label>
          
          <!-- Spacer -->
          <div class="flex-1" />
          
          <!-- Clear Filters Button -->
          <button 
            v-if="hasActiveFilters" 
            @click="resetFilters"
            class="h-9 flex items-center gap-1.5 px-3 rounded-lg text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
          >
            <Icon name="x" size="sm" />
            {{ $t('audit.clearFilters') }}
          </button>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 flex items-center gap-3">
        <Icon name="alert-circle" />
        <span class="flex-1 text-sm">{{ error }}</span>
        <button @click="fetchEvents" class="text-sm font-medium hover:underline">{{ $t('common.retry') }}</button>
      </div>

      <!-- Events List Card -->
      <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 overflow-hidden">
        <!-- Loading Skeleton -->
        <div v-if="loading && events.length === 0" class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="i in 5" :key="i" class="p-4 animate-pulse">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-white/10 shrink-0" />
              <div class="flex-1 min-w-0 space-y-2">
                <div class="h-4 w-24 bg-slate-200 dark:bg-white/10 rounded" />
                <div class="h-3 w-48 bg-slate-200 dark:bg-white/10 rounded" />
                <div class="h-3 w-32 bg-slate-200 dark:bg-white/10 rounded" />
              </div>
              <div class="h-3 w-16 bg-slate-200 dark:bg-white/10 rounded shrink-0" />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="events.length === 0 && !loading" class="py-16 px-4 text-center">
          <Icon name="clipboard-list" size="xl" class="mx-auto mb-4 text-slate-300 dark:text-white/20" />
          <p class="text-slate-700 dark:text-white font-medium mb-1">{{ $t('common.noResults') }}</p>
          <p v-if="hasActiveFilters" class="text-sm text-slate-500 dark:text-white/50">
            {{ $t('audit.tryAdjustFilters') }}
          </p>
        </div>

        <!-- Event Rows -->
        <div v-else class="divide-y divide-slate-100 dark:divide-white/5">
          <AdminAuditEventRow 
            v-for="event in events" 
            :key="event.id"
            :event="event"
            @click="openDrawer(event)"
          />
        </div>

        <!-- Pagination -->
        <div 
          v-if="(meta?.last_page ?? 1) > 1" 
          class="p-4 border-t border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50 dark:bg-white/5"
        >
          <!-- Info Section -->
          <div class="flex items-center gap-3 text-sm text-slate-500 dark:text-white/50">
            <span>{{ $t('audit.showing') }} {{ events.length }} {{ $t('audit.of') }} {{ meta?.total ?? 0 }}</span>
            <span class="hidden sm:inline text-slate-300 dark:text-white/20">|</span>
            <label class="hidden sm:flex items-center gap-2">
              {{ $t('audit.perPage') }}:
              <select 
                :value="meta?.per_page ?? 25" 
                @change="setPerPage(Number(($event.target as HTMLSelectElement).value))"
                class="h-8 px-2 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 text-sm text-slate-900 dark:text-white outline-none"
              >
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </label>
          </div>
          
          <!-- Page Navigation -->
          <div class="flex items-center gap-2" :class="{ 'flex-row-reverse': isRtl }">
            <button 
              @click="prevPage" 
              :disabled="(meta?.current_page ?? 1) <= 1" 
              class="h-9 px-3 flex items-center gap-1.5 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
              <Icon name="chevron-left" size="sm" :class="{ 'rotate-180': isRtl }" />
              <span class="hidden sm:inline">{{ $t('common.previous') }}</span>
            </button>
            <span class="px-3 text-sm text-slate-500 dark:text-white/50">
              {{ meta?.current_page ?? 1 }} / {{ meta?.last_page ?? 1 }}
            </span>
            <button 
              @click="nextPage" 
              :disabled="(meta?.current_page ?? 1) >= (meta?.last_page ?? 1)" 
              class="h-9 px-3 flex items-center gap-1.5 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
              <span class="hidden sm:inline">{{ $t('common.next') }}</span>
              <Icon name="chevron-right" size="sm" :class="{ 'rotate-180': isRtl }" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Drawer -->
    <AdminAuditEventDrawer 
      :event="selectedEvent"
      :open="drawerOpen"
      @close="closeDrawer"
    />
  </NuxtLayout>
</template>

<script setup lang="ts">
import type { AuditEvent } from '~/composables/useAuditLog'

definePageMeta({ layout: false, middleware: ['auth'] })

const { locale, t } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()

const isRtl = computed(() => locale.value === 'ar')

// Use audit log composable
const {
  events,
  loading,
  error,
  meta,
  filters,
  hasActiveFilters,
  fetchEvents,
  applyFilters,
  resetFilters,
  nextPage,
  prevPage,
  setPerPage,
} = useAuditLog()

// Departments for filter
const departments = ref<Array<{ id: string; name_en: string; name_ar: string }>>([])
const departmentsLoading = ref(false)

const fetchDepartments = async () => {
  departmentsLoading.value = true
  try {
    const res = await $fetch<any>(
      `${config.public.apiBase}/admin/departments`,
      { headers: { Authorization: `Bearer ${token.value}` } }
    )
    // Handle both direct array and { data: [...] } response shapes
    departments.value = Array.isArray(res) ? res : (res.data || [])
  } catch (e) {
    console.error('[audit] Failed to fetch departments:', e)
    departments.value = []
  } finally {
    departmentsLoading.value = false
  }
}

// Drawer state
const selectedEvent = ref<AuditEvent | null>(null)
const drawerOpen = ref(false)

const openDrawer = (event: AuditEvent) => {
  selectedEvent.value = event
  drawerOpen.value = true
}

const closeDrawer = () => {
  drawerOpen.value = false
}

// Export functionality
const exporting = ref(false)

const exportCsv = async () => {
  if (!filters.from || !filters.to) {
    alert(t('audit.export.dateRangeRequired'))
    return
  }
  
  exporting.value = true
  try {
    const params = new URLSearchParams()
    params.set('from', filters.from)
    params.set('to', filters.to)
    if (filters.eventType) params.set('event_type', filters.eventType)
    if (filters.actorId) params.set('actor_id', filters.actorId)
    if (filters.role) params.set('role', filters.role)
    if (filters.departmentId) params.set('department_id', filters.departmentId)
    
    const response = await fetch(
      `${config.public.apiBase}/admin/audit-log/export?${params}`,
      {
        headers: { Authorization: `Bearer ${token.value}` },
      }
    )
    
    if (!response.ok) {
      const err = await response.json()
      throw new Error(err.error || 'Export failed')
    }
    
    // Download the CSV
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `audit_log_${filters.from}_${filters.to}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (e: any) {
    console.error('[audit] Export failed:', e)
    alert(e.message || 'Export failed')
  } finally {
    exporting.value = false
  }
}

// Initialize
onMounted(() => {
  fetchDepartments()
  fetchEvents()
})
</script>
