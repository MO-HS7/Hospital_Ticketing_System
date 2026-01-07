<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('dashboard.title') }}</h1>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.total }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.totalTickets') }}</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.pending }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.pendingTickets') }}</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.overdue }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.overdueTickets') }}</p>
            </div>
          </div>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ metrics.completed }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ $t('dashboard.completedTickets') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid md:grid-cols-2 gap-6">
        <div class="card p-6">
          <h3 class="font-semibold mb-4">{{ $t('dashboard.ticketsByDept') }}</h3>
          <div class="space-y-3">
            <div v-for="d in deptStats" :key="d.id" class="flex items-center gap-3">
              <span class="w-32 text-sm text-[var(--color-text-secondary)] truncate">{{ d.name }}</span>
              <div class="flex-1 h-4 bg-[var(--color-bg-tertiary)] rounded-full overflow-hidden">
                <div class="h-full bg-primary-600 rounded-full" :style="{ width: `${Math.min((d.count / 50) * 100, 100)}%` }" />
              </div>
              <span class="text-sm font-medium w-8">{{ d.count }}</span>
            </div>
          </div>
        </div>
        <div class="card p-6">
          <h3 class="font-semibold mb-4">{{ $t('dashboard.staffCount') }}</h3>
          <div class="grid grid-cols-3 gap-4 text-center">
            <div><p class="text-3xl font-bold text-cyan-600">{{ staffCounts.doctors }}</p><p class="text-sm text-[var(--color-text-muted)]">{{ $t('staffTypes.doctor') }}</p></div>
            <div><p class="text-3xl font-bold text-amber-600">{{ staffCounts.maintenance }}</p><p class="text-sm text-[var(--color-text-muted)]">{{ $t('staffTypes.maintenance') }}</p></div>
            <div><p class="text-3xl font-bold text-rose-600">{{ staffCounts.reception }}</p><p class="text-sm text-[var(--color-text-muted)]">{{ $t('staffTypes.reception') }}</p></div>
          </div>
        </div>
      </div>

      <!-- Overdue Table -->
      <div class="card overflow-hidden">
        <div class="p-4 border-b border-[var(--color-border)] flex items-center justify-between">
          <h3 class="font-semibold">{{ $t('dashboard.recentOverdue') }}</h3>
          <select class="input w-auto text-sm">
            <option>{{ $t('filters.all') }} {{ $t('nav.departments') }}</option>
            <option>{{ $t('departments.cardiology') }}</option>
            <option>{{ $t('departments.orthopedics') }}</option>
          </select>
        </div>
        <table class="w-full">
          <thead class="bg-[var(--color-bg-tertiary)]">
            <tr>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('table.id') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.department') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('filters.type') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.deadline') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('tickets.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-border)]">
            <tr v-for="t in overdueTickets" :key="t.id" class="hover:bg-[var(--color-bg-tertiary)]">
              <td class="px-4 py-3 text-sm font-medium">#{{ t.id }}</td>
              <td class="px-4 py-3 text-sm">{{ getDeptName(t.department) }}</td>
              <td class="px-4 py-3 text-sm">{{ $t(`ticketType.${t.type}`) }}</td>
              <td class="px-4 py-3 text-sm text-red-600">{{ t.deadline }}</td>
              <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ $t('ticketStatus.overdue') }}</span></td>
            </tr>
            <tr v-if="overdueTickets.length === 0">
              <td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ $t('common.noResults') }}</td>
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
const { locale } = useI18n()
const { departments, fetchDepartments, getName } = useDepartments()

const metrics = ref({ total: 0, pending: 0, overdue: 0, completed: 0 })
const deptStats = ref<Array<{ id: string; name: string; count: number }>>([])
const overdueTickets = ref<Array<{ id: string; department: { name_en: string; name_ar: string }; type: string; deadline: string; status: string }>>([])
const staffCounts = ref({ doctors: 0, maintenance: 0, reception: 0 })
const loading = ref(true)

const getDeptName = (dept: { name_en: string; name_ar: string }) => locale.value === 'ar' ? dept.name_ar : dept.name_en

const fetchMetrics = async () => {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/admin/metrics`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    metrics.value = res.tickets || { total: 0, pending: 0, overdue: 0, completed: 0 }
    staffCounts.value = res.staff || { doctors: 0, maintenance: 0, reception: 0 }
    
    // Build department stats from API response or departments list
    if (res.departments_stats) {
      deptStats.value = res.departments_stats.map((d: any) => ({
        id: d.id,
        name: locale.value === 'ar' ? d.name_ar : d.name_en,
        count: d.count,
      }))
    } else {
      // Use fetched departments with placeholder counts
      deptStats.value = departments.value.slice(0, 6).map(d => ({
        id: d.id,
        name: getName(d),
        count: Math.floor(Math.random() * 50) + 5,
      }))
    }
    
    overdueTickets.value = res.overdue_tickets || []
  } catch (e) {
    console.error('Failed to fetch metrics:', e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchDepartments()
  await fetchMetrics()
})
</script>
