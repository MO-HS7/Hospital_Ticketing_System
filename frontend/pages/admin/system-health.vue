<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('commandCenter.systemHealth') }}</h1>
        <p class="text-sm text-[var(--color-text-muted)] mt-1">{{ $t('commandCenter.systemHealthSubtitle') }}</p>
      </div>

      <!-- Overall Status -->
      <div class="card p-6">
        <div class="flex items-center gap-4">
          <div 
            class="w-16 h-16 rounded-full flex items-center justify-center"
            :class="overallStatusClass"
          >
            <svg v-if="health.overall === 'healthy'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold" :class="overallTextClass">
              {{ $t(`commandCenter.${health.overall || 'healthy'}`) }}
            </p>
            <p class="text-sm text-[var(--color-text-muted)]">
              {{ $t('commandCenter.lastUpdated') }}: {{ health.timestamp ? new Date(health.timestamp).toLocaleTimeString() : '-' }}
            </p>
          </div>
          <button @click="fetchHealth" class="ms-auto btn-ghost p-2" :disabled="loading">
            <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Service Cards -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Database -->
        <div class="card p-4">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getStatusBg(health.database?.status)">
              <svg class="w-5 h-5" :class="getStatusColor(health.database?.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
              </svg>
            </div>
            <div>
              <p class="font-medium">{{ $t('commandCenter.database') }}</p>
              <p class="text-sm" :class="getStatusColor(health.database?.status)">
                {{ $t(`commandCenter.${health.database?.status || 'unknown'}`) }}
              </p>
            </div>
          </div>
          <div v-if="health.database?.latency_ms" class="text-xs text-[var(--color-text-muted)]">
            {{ $t('commandCenter.latency') }}: {{ health.database.latency_ms }}ms
          </div>
        </div>

        <!-- Cache -->
        <div class="card p-4">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getStatusBg(health.cache?.status)">
              <svg class="w-5 h-5" :class="getStatusColor(health.cache?.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
            </div>
            <div>
              <p class="font-medium">{{ $t('commandCenter.cache') }}</p>
              <p class="text-sm" :class="getStatusColor(health.cache?.status)">
                {{ $t(`commandCenter.${health.cache?.status || 'unknown'}`) }}
              </p>
            </div>
          </div>
          <div v-if="health.cache?.driver" class="text-xs text-[var(--color-text-muted)]">
            Driver: {{ health.cache.driver }}
          </div>
        </div>

        <!-- Queue -->
        <div class="card p-4">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getStatusBg(health.queue?.status)">
              <svg class="w-5 h-5" :class="getStatusColor(health.queue?.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <p class="font-medium">{{ $t('commandCenter.queue') }}</p>
              <p class="text-sm" :class="getStatusColor(health.queue?.status)">
                {{ $t(`commandCenter.${health.queue?.status || 'unknown'}`) }}
              </p>
            </div>
          </div>
          <div v-if="health.queue?.failed_jobs !== undefined" class="text-xs text-[var(--color-text-muted)]">
            Failed jobs: {{ health.queue.failed_jobs }}
          </div>
        </div>

        <!-- Storage -->
        <div class="card p-4">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getStatusBg(health.storage?.status)">
              <svg class="w-5 h-5" :class="getStatusColor(health.storage?.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
              </svg>
            </div>
            <div>
              <p class="font-medium">{{ $t('commandCenter.storage') }}</p>
              <p class="text-sm" :class="getStatusColor(health.storage?.status)">
                {{ $t(`commandCenter.${health.storage?.status || 'unknown'}`) }}
              </p>
            </div>
          </div>
          <div v-if="health.storage?.used_percent" class="space-y-1">
            <div class="flex justify-between text-xs text-[var(--color-text-muted)]">
              <span>Used: {{ health.storage.used_percent }}%</span>
              <span>Free: {{ health.storage.free_gb }}GB</span>
            </div>
            <div class="w-full h-2 bg-[var(--color-bg-tertiary)] rounded-full overflow-hidden">
              <div 
                class="h-full rounded-full transition-all"
                :class="health.storage.used_percent > 90 ? 'bg-red-500' : health.storage.used_percent > 70 ? 'bg-amber-500' : 'bg-green-500'"
                :style="{ width: `${health.storage.used_percent}%` }"
              />
            </div>
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

const loading = ref(false)
const health = ref<any>({})

const overallStatusClass = computed(() => ({
  'bg-green-100 dark:bg-green-900/30 text-green-600': health.value.overall === 'healthy',
  'bg-amber-100 dark:bg-amber-900/30 text-amber-600': health.value.overall === 'degraded',
  'bg-red-100 dark:bg-red-900/30 text-red-600': health.value.overall === 'unhealthy',
}))

const overallTextClass = computed(() => ({
  'text-green-600': health.value.overall === 'healthy',
  'text-amber-600': health.value.overall === 'degraded',
  'text-red-600': health.value.overall === 'unhealthy',
}))

const getStatusBg = (status?: string) => ({
  'bg-green-100 dark:bg-green-900/30': status === 'healthy',
  'bg-amber-100 dark:bg-amber-900/30': status === 'degraded' || status === 'warning',
  'bg-red-100 dark:bg-red-900/30': status === 'unhealthy',
  'bg-gray-100 dark:bg-gray-800': !status || status === 'unknown',
})

const getStatusColor = (status?: string) => ({
  'text-green-600': status === 'healthy',
  'text-amber-600': status === 'degraded' || status === 'warning',
  'text-red-600': status === 'unhealthy',
  'text-gray-600': !status || status === 'unknown',
})

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
