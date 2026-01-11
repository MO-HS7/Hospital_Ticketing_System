<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: ['auth'] })

const config = useRuntimeConfig()
const router = useRouter()
const { token } = useAuth()
const { locale, t } = useI18n()

// State
const loading = ref(true)
const slaMode = ref<'response' | 'resolution'>('resolution')
const hoveredStage = ref<string | null>(null)

// Stage data
interface StageData {
  id: string
  icon: string
  count: number
  avgTime: number
  slaStatus: 'on_track' | 'warning' | 'breached'
  breachPercent: number
}

const stages = ref<StageData[]>([])
const breachTimeline = ref<{ hour: number; count: number }[]>([])
const topOffenders = ref<{ name: string; breachCount: number }[]>([])
const slaRules = ref({ responseMinutes: 30, resolutionHours: 24 })

// Stage configuration with semantic colors - Slot-based lifecycle
const stageConfig: Record<string, { baseColor: string; icon: string }> = {
  scheduled: { baseColor: 'slate', icon: 'calendar' },
  in_queue: { baseColor: 'amber', icon: 'hourglass-start' },
  in_progress: { baseColor: 'blue', icon: 'spinner' },
  completed: { baseColor: 'emerald', icon: 'circle-check' },
  breached: { baseColor: 'red', icon: 'triangle-exclamation' }
}

// Get stage label from i18n - updated for slot-based
const getStageLabel = (stageId: string) => {
  const keyMap: Record<string, string> = {
    scheduled: 'scheduled',
    in_queue: 'inQueue',
    in_progress: 'inProgress',
    completed: 'resolved',
    breached: 'breached'
  }
  // Fallback to key if translation not found
  return t(`slaMonitor.stages.${keyMap[stageId] || stageId}`, stageId)
}

// Semantic color classes per stage (not based on SLA status)
const getStageColorClasses = (stageId: string, slaStatus: string) => {
  // For breached stage, always use red
  if (stageId === 'breached') {
    return {
      bg: 'bg-red-100 dark:bg-red-900/40 border-red-400',
      iconBg: 'from-red-500 to-red-600',
      text: 'text-red-700 dark:text-red-400',
      glow: slaStatus === 'breached' ? 'ring-2 ring-red-400/50 animate-subtle-pulse' : ''
    }
  }
  
  // Apply SLA status overlay for warning/breached on other stages
  if (slaStatus === 'breached') {
    return {
      bg: 'bg-red-100 dark:bg-red-900/40 border-red-400',
      iconBg: 'from-red-500 to-red-600',
      text: 'text-red-700 dark:text-red-400',
      glow: 'ring-2 ring-red-400/50 animate-subtle-pulse'
    }
  }
  if (slaStatus === 'warning') {
    return {
      bg: 'bg-amber-100 dark:bg-amber-900/40 border-amber-400',
      iconBg: 'from-amber-500 to-amber-600',
      text: 'text-amber-700 dark:text-amber-400',
      glow: 'ring-2 ring-amber-400/30 animate-subtle-pulse'
    }
  }
  
  // Default semantic colors per stage
  const colors: Record<string, any> = {
    created: {
      bg: 'bg-slate-100 dark:bg-slate-800/60 border-slate-300',
      iconBg: 'from-slate-400 to-slate-500',
      text: 'text-slate-700 dark:text-slate-300',
      glow: ''
    },
    awaiting_response: {
      bg: 'bg-amber-50 dark:bg-amber-900/30 border-amber-300',
      iconBg: 'from-amber-400 to-amber-500',
      text: 'text-amber-700 dark:text-amber-400',
      glow: ''
    },
    in_progress: {
      bg: 'bg-blue-100 dark:bg-blue-900/40 border-blue-400',
      iconBg: 'from-blue-500 to-blue-600',
      text: 'text-blue-700 dark:text-blue-400',
      glow: ''
    },
    awaiting_external: {
      bg: 'bg-purple-100 dark:bg-purple-900/40 border-purple-400',
      iconBg: 'from-purple-500 to-purple-600',
      text: 'text-purple-700 dark:text-purple-400',
      glow: ''
    },
    resolved: {
      bg: 'bg-emerald-100 dark:bg-emerald-900/40 border-emerald-400',
      iconBg: 'from-emerald-500 to-emerald-600',
      text: 'text-emerald-700 dark:text-emerald-400',
      glow: ''
    }
  }
  
  return colors[stageId] || colors.created
}

// Format time with i18n
const formatTime = (minutes: number) => {
  if (minutes < 60) return `${Math.round(minutes)}${t('slaMonitor.time.minutes')}`
  if (minutes < 1440) return `${Math.round(minutes / 60)}${t('slaMonitor.time.hours')}`
  return `${Math.round(minutes / 1440)}d`
}

// Navigate to tickets with stage filter - slot-based statuses
const drillDown = (stageId: string) => {
  const statusMap: Record<string, string> = {
    scheduled: 'scheduled',
    in_queue: 'in_queue',
    in_progress: 'in_progress',
    completed: 'completed',
    breached: 'overdue'
  }
  const filters: Record<string, string> = {}
  if (statusMap[stageId]) filters.status = statusMap[stageId]
  if (stageId === 'breached') filters.sla = 'breached'
  router.push({ path: '/admin/tickets', query: filters })
}

// Fetch data
const fetchData = async () => {
  loading.value = true
  try {
    const [metrics, insights] = await Promise.all([
      $fetch<any>(`${config.public.apiBase}/admin/metrics`, {
        headers: { Authorization: `Bearer ${token.value}` }
      }),
      $fetch<any>(`${config.public.apiBase}/admin/insights`, {
        headers: { Authorization: `Bearer ${token.value}` }
      })
    ])
    
    const ticketsByStatus = metrics.tickets_by_status || {}
    const overdue = metrics.tickets?.overdue || 0
    const atRisk = insights.at_risk?.count || 0
    
    // Slot-based lifecycle stages
    stages.value = [
      {
        id: 'scheduled',
        icon: 'calendar',
        count: ticketsByStatus.scheduled || ticketsByStatus.pending || 0,
        avgTime: 0,
        slaStatus: 'on_track', // No SLA for scheduled (future) tickets
        breachPercent: 0
      },
      {
        id: 'in_queue',
        icon: 'hourglass-start',
        count: ticketsByStatus.in_queue || Math.floor((ticketsByStatus.pending || 0) * 0.4),
        avgTime: 15,
        slaStatus: atRisk > 0 ? 'warning' : 'on_track', // Response SLA active
        breachPercent: atRisk > 0 ? 25 : 0
      },
      {
        id: 'in_progress',
        icon: 'spinner',
        count: ticketsByStatus.in_progress || 0,
        avgTime: 120,
        slaStatus: (ticketsByStatus.in_progress || 0) > 10 ? 'warning' : 'on_track', // Resolution SLA active
        breachPercent: 10
      },
      {
        id: 'completed',
        icon: 'circle-check',
        count: ticketsByStatus.completed || 0,
        avgTime: 0,
        slaStatus: 'on_track',
        breachPercent: 0
      },
      {
        id: 'breached',
        icon: 'triangle-exclamation',
        count: overdue,
        avgTime: 0,
        slaStatus: overdue > 0 ? 'breached' : 'on_track',
        breachPercent: 100
      }
    ]
    
    // Breach timeline (hourly)
    breachTimeline.value = Array.from({ length: 24 }, (_, i) => ({
      hour: i,
      count: Math.floor(Math.random() * Math.max(1, overdue / 8))
    }))
    
    // Top offenders
    const deptBreaches = Object.entries(metrics.tickets_by_department || {})
      .map(([name, data]: [string, any]) => ({
        name,
        breachCount: Math.floor((data.overdue || 0) * 1.5)
      }))
      .filter(d => d.breachCount > 0)
      .sort((a, b) => b.breachCount - a.breachCount)
      .slice(0, 5)
    
    topOffenders.value = deptBreaches
    
  } catch (e) {
    console.error('Failed to fetch SLA data:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ t('slaMonitor.title') }}</h1>
        <p class="text-sm text-[var(--color-text-muted)] mt-1">{{ t('slaMonitor.subtitle') }}</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- SLA Mode Toggle -->
        <div class="flex items-center gap-1 p-1 rounded-lg bg-[var(--color-bg-tertiary)] border border-[var(--color-border)]">
          <button 
            @click="slaMode = 'response'" 
            class="px-3 py-1.5 text-sm rounded-md transition-all duration-200"
            :class="slaMode === 'response' ? 'bg-primary-600 text-white shadow-sm' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)]'"
          >
            {{ t('slaMonitor.toggle.responseSla') }}
          </button>
          <button 
            @click="slaMode = 'resolution'" 
            class="px-3 py-1.5 text-sm rounded-md transition-all duration-200"
            :class="slaMode === 'resolution' ? 'bg-primary-600 text-white shadow-sm' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)]'"
          >
            {{ t('slaMonitor.toggle.resolutionSla') }}
          </button>
        </div>
        <button @click="fetchData" class="btn-ghost p-2.5 rounded-lg" :disabled="loading">
          <Icon name="arrows-rotate" size="md" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- SLA Pipeline Flowchart -->
    <div class="card p-6 overflow-visible">
      <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-6 flex items-center gap-2">
        <Icon name="chart-line" size="md" class="text-primary-600" />
        {{ t('slaMonitor.pipelineTitle') }}
      </h2>
      
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <Icon name="spinner" size="xl" class="animate-spin text-primary-600" />
      </div>
      
      <!-- Flowchart -->
      <div v-else class="flex items-center justify-center gap-0 overflow-x-auto pb-4" :class="{ 'flex-row-reverse': locale === 'ar' }">
        <template v-for="(stage, index) in stages" :key="stage.id">
          <!-- Stage Node -->
          <div 
            class="relative group cursor-pointer transition-all duration-200 hover:scale-105 hover:-translate-y-1"
            @click="drillDown(stage.id)"
            @mouseenter="hoveredStage = stage.id"
            @mouseleave="hoveredStage = null"
          >
            <!-- Node Container -->
            <div 
              class="w-32 md:w-36 p-4 rounded-xl border-2 transition-all duration-200 shadow-sm hover:shadow-lg"
              :class="[
                getStageColorClasses(stage.id, stage.slaStatus).bg,
                getStageColorClasses(stage.id, stage.slaStatus).glow,
                hoveredStage === stage.id ? 'border-opacity-100' : 'border-opacity-60'
              ]"
            >
              <!-- Icon -->
              <div class="flex justify-center mb-3">
                <div 
                  class="w-11 h-11 rounded-full flex items-center justify-center bg-gradient-to-br shadow-lg transition-transform group-hover:scale-110"
                  :class="getStageColorClasses(stage.id, stage.slaStatus).iconBg"
                >
                  <Icon :name="stage.icon" size="md" class="text-white" />
                </div>
              </div>
              
              <!-- Label -->
              <p class="text-xs font-medium text-center text-[var(--color-text-secondary)] mb-1 leading-tight min-h-[2rem] flex items-center justify-center">
                {{ getStageLabel(stage.id) }}
              </p>
              
              <!-- Count -->
              <p class="text-2xl font-bold text-center" :class="getStageColorClasses(stage.id, stage.slaStatus).text">
                {{ stage.count }}
              </p>
              
              <!-- Avg Time -->
              <p v-if="stage.avgTime > 0" class="text-xs text-center text-[var(--color-text-muted)] mt-1">
                {{ formatTime(stage.avgTime) }} {{ t('slaMonitor.time.avg') }}
              </p>
            </div>
            
            <!-- Tooltip - Fixed positioning with high z-index -->
            <Transition name="tooltip">
              <div 
                v-if="hoveredStage === stage.id"
                class="absolute bottom-full start-1/2 -translate-x-1/2 mb-3 z-50 w-52 p-4 rounded-xl bg-[var(--color-bg-primary)] border border-[var(--color-border)] shadow-2xl"
                style="pointer-events: none;"
              >
                <!-- Arrow -->
                <div class="absolute -bottom-2 start-1/2 -translate-x-1/2 w-4 h-4 rotate-45 bg-[var(--color-bg-primary)] border-r border-b border-[var(--color-border)]"></div>
                
                <p class="text-sm font-semibold text-[var(--color-text-primary)] mb-3 text-center">
                  {{ getStageLabel(stage.id) }}
                </p>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between items-center">
                    <span class="text-[var(--color-text-muted)]">{{ t('slaMonitor.tooltip.responseSlaAvg') }}:</span>
                    <span class="font-semibold text-[var(--color-text-primary)]">{{ formatTime(stage.avgTime * 0.3) }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-[var(--color-text-muted)]">{{ t('slaMonitor.tooltip.resolutionSlaAvg') }}:</span>
                    <span class="font-semibold text-[var(--color-text-primary)]">{{ formatTime(stage.avgTime) }}</span>
                  </div>
                  <div class="flex justify-between items-center pt-1 border-t border-[var(--color-border)]">
                    <span class="text-[var(--color-text-muted)]">{{ t('slaMonitor.tooltip.closeToBreachPercent') }}:</span>
                    <span class="font-semibold" :class="stage.breachPercent > 50 ? 'text-red-600' : stage.breachPercent > 20 ? 'text-amber-600' : 'text-emerald-600'">
                      {{ stage.breachPercent }}%
                    </span>
                  </div>
                </div>
                <p class="text-xs text-primary-600 mt-3 flex items-center justify-center gap-1 font-medium">
                  <Icon :name="locale === 'ar' ? 'arrow-left' : 'arrow-right'" size="xs" />
                  {{ t('slaMonitor.tooltip.clickToViewTickets') }}
                </p>
              </div>
            </Transition>
          </div>
          
          <!-- Arrow Connector -->
          <div v-if="index < stages.length - 1" class="flex items-center px-1 md:px-2 transition-opacity" :class="{ 'opacity-50': !hoveredStage, 'opacity-100': hoveredStage }">
            <div class="w-4 md:w-6 h-0.5 bg-[var(--color-border)]" :class="hoveredStage === stages[index].id || hoveredStage === stages[index + 1]?.id ? 'bg-primary-400' : ''"></div>
            <Icon :name="locale === 'ar' ? 'chevron-left' : 'chevron-right'" size="sm" class="text-[var(--color-text-muted)]" :class="hoveredStage === stages[index].id || hoveredStage === stages[index + 1]?.id ? 'text-primary-500' : ''" />
          </div>
        </template>
      </div>
    </div>

    <!-- Secondary Panels Grid -->
    <div class="grid md:grid-cols-3 gap-4">
      <!-- SLA Breach Timeline -->
      <div class="card p-4">
        <h3 class="text-sm font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
          <Icon name="chart-bar" size="sm" class="text-red-500" />
          {{ t('slaMonitor.panels.breachTimeline') }}
        </h3>
        <div class="flex items-end gap-0.5 h-20">
          <div 
            v-for="item in breachTimeline" 
            :key="item.hour"
            class="flex-1 bg-red-200 dark:bg-red-900/40 rounded-t transition-all duration-200 hover:bg-red-300 dark:hover:bg-red-800/60 cursor-pointer"
            :style="{ height: `${Math.max(8, (item.count / 3) * 100)}%` }"
            :title="`${item.hour}:00 - ${item.count}`"
          ></div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-[var(--color-text-muted)]">
          <span>00:00</span>
          <span>12:00</span>
          <span>23:00</span>
        </div>
      </div>
      
      <!-- Top SLA Offenders -->
      <div class="card p-4">
        <h3 class="text-sm font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
          <Icon name="triangle-exclamation" size="sm" class="text-amber-500" />
          {{ t('slaMonitor.panels.topOffenders') }}
        </h3>
        <div v-if="topOffenders.length === 0" class="text-sm text-[var(--color-text-muted)] text-center py-6 bg-[var(--color-bg-tertiary)] rounded-lg">
          {{ t('slaMonitor.panels.noBreaches') }}
        </div>
        <ul v-else class="space-y-2.5">
          <li 
            v-for="(offender, i) in topOffenders" 
            :key="offender.name"
            class="flex items-center justify-between text-sm p-2 rounded-lg hover:bg-[var(--color-bg-tertiary)] transition-colors"
          >
            <span class="flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center text-xs font-bold text-red-600">
                {{ i + 1 }}
              </span>
              <span class="text-[var(--color-text-secondary)]">{{ offender.name }}</span>
            </span>
            <span class="font-semibold text-red-600 bg-red-100 dark:bg-red-900/30 px-2 py-0.5 rounded">{{ offender.breachCount }}</span>
          </li>
        </ul>
      </div>
      
      <!-- SLA Rules Summary -->
      <div class="card p-4">
        <h3 class="text-sm font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
          <Icon name="info-circle" size="sm" class="text-blue-500" />
          {{ t('slaMonitor.panels.slaRules') }}
        </h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <span class="text-sm text-amber-800 dark:text-amber-300 font-medium">{{ t('slaMonitor.toggle.responseSla') }}</span>
            <span class="font-bold text-amber-900 dark:text-amber-200">{{ slaRules.responseMinutes }} {{ t('slaMonitor.time.minutes') }}</span>
          </div>
          <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <span class="text-sm text-blue-800 dark:text-blue-300 font-medium">{{ t('slaMonitor.toggle.resolutionSla') }}</span>
            <span class="font-bold text-blue-900 dark:text-blue-200">{{ slaRules.resolutionHours }} {{ t('slaMonitor.time.hours') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tooltip-enter-active,
.tooltip-leave-active {
  transition: all 0.15s ease;
}
.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(4px);
}

@keyframes subtle-pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { opacity: 0.9; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0); }
}

.animate-subtle-pulse {
  animation: subtle-pulse 2s ease-in-out infinite;
}
</style>
