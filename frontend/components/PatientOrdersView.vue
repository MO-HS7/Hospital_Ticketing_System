<template>
  <div v-if="orders.length > 0 || loading" class="mt-6">
    <h4 class="font-semibold mb-4">{{ $t('orders.title') }}</h4>
    
    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="card p-3 animate-pulse">
        <div class="h-3 bg-[var(--color-bg-tertiary)] rounded w-1/4 mb-2"></div>
        <div class="h-2 bg-[var(--color-bg-tertiary)] rounded w-1/2"></div>
      </div>
    </div>

    <!-- Orders List -->
    <div v-else class="space-y-3">
      <div
        v-for="order in orders"
        :key="order.id"
        class="card p-4"
      >
        <div class="flex items-start justify-between gap-4 mb-3">
          <div class="flex items-center gap-2">
            <span class="badge" :class="typeClass(order.type)">{{ $t(`orders.${order.type}`) }}</span>
            <span class="badge text-xs" :class="statusClass(order.status)">{{ $t(`orders.status.${order.status}`) }}</span>
          </div>
          <span class="text-xs text-[var(--color-text-muted)]">{{ formatDate(order.created_at) }}</span>
        </div>

        <!-- Items -->
        <div class="mb-3">
          <label class="text-xs text-[var(--color-text-muted)] block mb-1">{{ $t('orders.items') }}</label>
          <ul class="list-disc list-inside text-sm text-[var(--color-text-primary)]">
            <li v-for="(item, i) in order.items" :key="i">{{ item }}</li>
          </ul>
        </div>

        <!-- Instructions -->
        <div v-if="order.instructions" class="mb-3">
          <label class="text-xs text-[var(--color-text-muted)] block mb-1">{{ $t('orders.instructions') }}</label>
          <p class="text-sm text-[var(--color-text-primary)]">{{ order.instructions }}</p>
        </div>

        <!-- Results (when completed) -->
        <div v-if="order.status === 'completed' && order.results" class="border-t border-[var(--color-border)] pt-3 mt-3">
          <label class="text-xs text-[var(--color-text-muted)] block mb-1">{{ $t('orders.results') }}</label>
          <pre class="bg-[var(--color-bg-tertiary)] p-3 rounded-lg text-sm overflow-auto">{{ formatResults(order.results) }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  encounterId: string | null
}>()

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()

const loading = ref(false)
const orders = ref<any[]>([])

const typeClass = (type: string) => {
  const classes: Record<string, string> = {
    lab: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    radiology: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    pharmacy: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
  }
  return classes[type] || ''
}

const statusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
    processing: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    completed: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    cancelled: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
  }
  return classes[status] || ''
}

const formatDate = (value: string | null) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString(locale.value === 'ar' ? 'ar' : 'en')
}

const formatResults = (results: any) => {
  if (typeof results === 'string') return results
  return JSON.stringify(results, null, 2)
}

const fetchOrders = async () => {
  if (!props.encounterId) return
  
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/encounters/${props.encounterId}/orders`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    orders.value = res || []
  } catch (e) {
    console.error('Failed to fetch encounter orders:', e)
    orders.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.encounterId, () => fetchOrders(), { immediate: true })
</script>
