<template>
  <NuxtLayout name="admin">
    <div class="space-y-4 md:space-y-6">
      <!-- Header (subtitle only - layout provides title) -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <p class="text-sm text-slate-600 dark:text-white/60">{{ $t('commandCenter.systemHealthSubtitle') }}</p>
        </div>
        <button 
          @click="fetchHealth" 
          class="h-10 px-4 flex items-center gap-2 rounded-xl bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/20 transition shrink-0"
          :disabled="loading"
        >
          <Icon name="arrows-rotate" :class="{ 'animate-spin': loading }" />
          <span class="hidden sm:inline">{{ $t('common.refresh') }}</span>
        </button>
      </div>

      <!-- Overall Status Card -->
      <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-6">
        <div class="flex items-center gap-4">
          <div 
            class="w-16 h-16 rounded-2xl flex items-center justify-center"
            :class="overallStatusClass"
          >
            <Icon :name="overallIcon" size="xl" class="text-current" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-2xl font-bold" :class="overallTextClass">
              {{ $t(`commandCenter.${health.overall || 'healthy'}`) }}
            </p>
            <p v-if="health.reason_summary" class="text-sm text-slate-600 dark:text-white/60 mt-1">
              {{ health.reason_summary }}
            </p>
            <p class="text-xs text-slate-400 dark:text-white/40 mt-1">
              {{ $t('commandCenter.lastUpdated') }}: {{ health.timestamp ? new Date(health.timestamp).toLocaleTimeString() : '-' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Service Cards Grid -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Database -->
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 min-h-[140px] flex flex-col">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="getStatusBg(health.database?.status)">
              <Icon name="database" size="md" :class="getStatusColor(health.database?.status)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 dark:text-white">{{ $t('commandCenter.database') }}</p>
              <p class="text-sm font-medium" :class="getStatusColor(health.database?.status)">
                {{ getStatusLabel(health.database?.status) }}
              </p>
            </div>
          </div>
          <div class="mt-auto space-y-1">
            <div v-if="health.database?.latency_ms" class="flex justify-between text-xs text-slate-500 dark:text-white/50">
              <span>{{ $t('commandCenter.latency') }}</span>
              <span class="font-medium">{{ health.database.latency_ms }}ms</span>
            </div>
            <div v-if="health.database?.error" class="text-xs text-red-600 dark:text-red-400 truncate" :title="health.database.error">
              {{ health.database.error }}
            </div>
          </div>
        </div>

        <!-- Cache -->
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 min-h-[140px] flex flex-col">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="getStatusBg(health.cache?.status)">
              <Icon name="bolt" size="md" :class="getStatusColor(health.cache?.status)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 dark:text-white">{{ $t('commandCenter.cache') }}</p>
              <p class="text-sm font-medium" :class="getStatusColor(health.cache?.status)">
                {{ getStatusLabel(health.cache?.status) }}
              </p>
            </div>
          </div>
          <div class="mt-auto space-y-1">
            <div v-if="health.cache?.driver" class="flex justify-between text-xs text-slate-500 dark:text-white/50">
              <span>{{ $t('commandCenter.driver') }}</span>
              <span class="font-medium">{{ health.cache.driver }}</span>
            </div>
            <div v-if="health.cache?.latency_ms" class="flex justify-between text-xs text-slate-500 dark:text-white/50">
              <span>{{ $t('commandCenter.latency') }}</span>
              <span class="font-medium">{{ health.cache.latency_ms }}ms</span>
            </div>
          </div>
        </div>

        <!-- Queue -->
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 min-h-[140px] flex flex-col">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="getStatusBg(health.queue?.status)">
              <Icon name="list" size="md" :class="getStatusColor(health.queue?.status)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 dark:text-white">{{ $t('commandCenter.queue') }}</p>
              <p class="text-sm font-medium" :class="getStatusColor(health.queue?.status)">
                {{ getStatusLabel(health.queue?.status) }}
              </p>
            </div>
          </div>
          <div class="mt-auto space-y-1">
            <div v-if="health.queue?.failed_jobs !== undefined" class="flex justify-between text-xs text-slate-500 dark:text-white/50">
              <span>{{ $t('commandCenter.failedJobs') }}</span>
              <span class="font-medium" :class="health.queue.failed_jobs > 0 ? 'text-amber-600' : ''">{{ health.queue.failed_jobs }}</span>
            </div>
          </div>
        </div>

        <!-- Storage -->
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 min-h-[140px] flex flex-col">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="getStatusBg(health.storage?.status)">
              <Icon name="folder" size="md" :class="getStatusColor(health.storage?.status)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 dark:text-white">{{ $t('commandCenter.storage') }}</p>
              <p class="text-sm font-medium" :class="getStatusColor(health.storage?.status)">
                {{ getStatusLabel(health.storage?.status) }}
              </p>
            </div>
          </div>
          <div v-if="health.storage?.used_percent !== undefined" class="mt-auto space-y-2">
            <div class="flex justify-between text-xs text-slate-500 dark:text-white/50">
              <span>{{ $t('commandCenter.used') }}: {{ health.storage.used_percent }}%</span>
              <span>{{ $t('commandCenter.free') }}: {{ health.storage.free_gb }}GB</span>
            </div>
            <div class="w-full h-2 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
              <div 
                class="h-full rounded-full transition-all"
                :class="health.storage.used_percent > 90 ? 'bg-red-500' : health.storage.used_percent > 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                :style="{ width: `${health.storage.used_percent}%` }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- App Info Section -->
      <div v-if="health.app" class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
        <h3 class="text-sm font-medium text-slate-900 dark:text-white mb-3">{{ $t('commandCenter.appInfo') }}</h3>
        <div class="grid sm:grid-cols-3 gap-4">
          <div class="flex justify-between text-sm">
            <span class="text-slate-500 dark:text-white/50">{{ $t('commandCenter.version') }}</span>
            <span class="font-medium text-slate-900 dark:text-white">{{ health.app.version }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-500 dark:text-white/50">{{ $t('commandCenter.environment') }}</span>
            <span class="font-medium text-slate-900 dark:text-white">{{ health.app.environment }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-500 dark:text-white/50">{{ $t('commandCenter.debugMode') }}</span>
            <span class="font-medium" :class="health.app.debug ? 'text-amber-600' : 'text-emerald-600'">
              {{ health.app.debug ? $t('common.on') : $t('common.off') }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const config = useRuntimeConfig()
const { token } = useAuth()
const { t } = useI18n()

const loading = ref(false)
const health = ref<any>({})

const overallStatusClass = computed(() => ({
  'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600': health.value.overall === 'healthy',
  'bg-amber-100 dark:bg-amber-900/30 text-amber-600': health.value.overall === 'degraded',
  'bg-red-100 dark:bg-red-900/30 text-red-600': health.value.overall === 'unhealthy' || health.value.overall === 'down',
  'bg-slate-100 dark:bg-slate-800 text-slate-600': !health.value.overall,
}))

const overallTextClass = computed(() => ({
  'text-emerald-600': health.value.overall === 'healthy',
  'text-amber-600': health.value.overall === 'degraded',
  'text-red-600': health.value.overall === 'unhealthy' || health.value.overall === 'down',
  'text-slate-600': !health.value.overall,
}))

const overallIcon = computed(() => {
  switch (health.value.overall) {
    case 'healthy': return 'check-circle'
    case 'degraded': return 'exclamation-triangle'
    case 'unhealthy':
    case 'down': return 'times-circle'
    default: return 'circle-question'
  }
})

const getStatusBg = (status?: string) => ({
  'bg-emerald-100 dark:bg-emerald-900/30': status === 'healthy',
  'bg-amber-100 dark:bg-amber-900/30': status === 'degraded' || status === 'warning',
  'bg-red-100 dark:bg-red-900/30': status === 'unhealthy' || status === 'down',
  'bg-slate-100 dark:bg-slate-800': !status || status === 'unknown',
})

const getStatusColor = (status?: string) => ({
  'text-emerald-600': status === 'healthy',
  'text-amber-600': status === 'degraded' || status === 'warning',
  'text-red-600': status === 'unhealthy' || status === 'down',
  'text-slate-500 dark:text-slate-400': !status || status === 'unknown',
})

// Get translated status label with fallback
const getStatusLabel = (status?: string): string => {
  if (!status) return t('commandCenter.unknown')
  // Try to translate, fallback to capitalized status
  const key = `commandCenter.${status}`
  const translated = t(key)
  // If translation returns the key itself, capitalize the status
  return translated === key ? status.charAt(0).toUpperCase() + status.slice(1) : translated
}

const fetchHealth = async () => {
  loading.value = true
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/admin/system-health`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    health.value = res
  } catch (e) {
    console.error('Failed to fetch health:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchHealth)
</script>
