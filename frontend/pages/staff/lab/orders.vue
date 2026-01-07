<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)]">
    <!-- Header -->
    <header class="bg-[var(--color-bg-primary)] border-b border-[var(--color-border)] px-6 py-4">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-4">
          <h1 class="text-xl font-bold text-[var(--color-text-primary)]">{{ $t('orders.lab') }}</h1>
          <span class="badge bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
            {{ $t('orders.title') }}
          </span>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-sm text-[var(--color-text-muted)]">{{ user?.name }}</span>
          <button @click="logout" class="btn-outline text-sm px-3 py-1">{{ $t('common.logout') }}</button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
      <!-- Status Tabs -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button
          v-for="status in statuses"
          :key="status"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="activeStatus === status 
            ? 'bg-primary-600 text-white' 
            : 'bg-[var(--color-bg-tertiary)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-primary)]'"
          @click="activeStatus = status"
        >
          {{ $t(`orders.status.${status}`) }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 5" :key="i" class="card p-4 animate-pulse">
          <div class="h-4 bg-[var(--color-bg-tertiary)] rounded w-1/3 mb-2"></div>
          <div class="h-3 bg-[var(--color-bg-tertiary)] rounded w-1/2"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="orders.length === 0" class="text-center py-16">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--color-bg-tertiary)] flex items-center justify-center">
          <svg class="w-8 h-8 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-[var(--color-text-primary)] mb-2">{{ $t('orders.noOrders') }}</h3>
        <p class="text-[var(--color-text-muted)]">{{ $t(`orders.status.${activeStatus}`) }}</p>
      </div>

      <!-- Orders List -->
      <div v-else class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="card p-4 hover:shadow-lg transition-shadow cursor-pointer"
          @click="openOrder(order)"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm font-medium text-[var(--color-text-primary)]">
                  #{{ order.ticket?.id }} - {{ order.ticket?.subject }}
                </span>
                <span 
                  class="badge text-xs"
                  :class="statusClass(order.status)"
                >
                  {{ $t(`orders.status.${order.status}`) }}
                </span>
              </div>
              <p class="text-sm text-[var(--color-text-muted)] mb-2">
                {{ order.items?.length }} {{ $t('orders.items') }}
              </p>
              <div class="flex items-center gap-4 text-xs text-[var(--color-text-muted)]">
                <span>{{ $t('orders.orderedBy') }}: {{ order.ordered_by_user?.name || '-' }}</span>
                <span>{{ formatDate(order.created_at) }}</span>
              </div>
            </div>
            <button class="btn-outline text-sm px-3 py-1">
              {{ $t('common.view') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="flex justify-center gap-2 mt-6">
        <button
          :disabled="pagination.currentPage <= 1"
          class="btn-outline text-sm px-3 py-1"
          @click="fetchOrders(pagination.currentPage - 1)"
        >
          {{ $t('common.previous') }}
        </button>
        <span class="text-sm text-[var(--color-text-muted)] px-3 py-1">
          {{ pagination.currentPage }} / {{ pagination.lastPage }}
        </span>
        <button
          :disabled="pagination.currentPage >= pagination.lastPage"
          class="btn-outline text-sm px-3 py-1"
          @click="fetchOrders(pagination.currentPage + 1)"
        >
          {{ $t('common.next') }}
        </button>
      </div>
    </main>

    <!-- Order Details Modal -->
    <div v-if="selectedOrder" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="selectedOrder = null">
      <div class="bg-[var(--color-bg-primary)] rounded-2xl shadow-2xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-[var(--color-text-primary)]">{{ $t('orders.title') }}</h3>
          <button @click="selectedOrder = null" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Order Info -->
        <div class="space-y-4 mb-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('tickets.ticket') }}</label>
              <p class="text-[var(--color-text-primary)]">#{{ selectedOrder.ticket?.id }} - {{ selectedOrder.ticket?.subject }}</p>
            </div>
            <div>
              <label class="label">{{ $t('orders.status.pending') }}</label>
              <span :class="statusClass(selectedOrder.status)" class="badge">{{ $t(`orders.status.${selectedOrder.status}`) }}</span>
            </div>
          </div>

          <div>
            <label class="label">{{ $t('orders.items') }}</label>
            <ul class="list-disc list-inside text-[var(--color-text-primary)]">
              <li v-for="(item, i) in selectedOrder.items" :key="i">{{ item }}</li>
            </ul>
          </div>

          <div v-if="selectedOrder.instructions">
            <label class="label">{{ $t('orders.instructions') }}</label>
            <p class="text-[var(--color-text-primary)]">{{ selectedOrder.instructions }}</p>
          </div>
        </div>

        <!-- Update Form -->
        <div v-if="canUpdate(selectedOrder)" class="border-t border-[var(--color-border)] pt-6 space-y-4">
          <h4 class="font-semibold">{{ $t('orders.process') }}</h4>

          <!-- Status Update -->
          <div>
            <label class="label mb-2">{{ $t('orders.type') }}</label>
            <select v-model="updateForm.status" class="input w-full">
              <option value="">{{ $t('common.select') }}</option>
              <option v-if="selectedOrder.status === 'pending'" value="processing">{{ $t('orders.status.processing') }}</option>
              <option v-if="selectedOrder.status === 'processing'" value="completed">{{ $t('orders.status.completed') }}</option>
              <option v-if="['pending', 'processing'].includes(selectedOrder.status)" value="cancelled">{{ $t('orders.status.cancelled') }}</option>
            </select>
          </div>

          <!-- Results -->
          <div v-if="updateForm.status === 'completed'">
            <label class="label mb-2">{{ $t('orders.results') }}</label>
            <textarea 
              v-model="resultsText" 
              class="input w-full" 
              rows="4"
              :placeholder="$t('orders.enterResults')"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 justify-end">
            <button @click="selectedOrder = null" class="btn-outline px-4 py-2">{{ $t('common.cancel') }}</button>
            <button 
              @click="submitUpdate" 
              :disabled="updating || !updateForm.status"
              class="btn-primary px-4 py-2"
            >
              {{ updating ? $t('orders.loading') : $t('orders.submit') }}
            </button>
          </div>
        </div>

        <!-- Completed Results -->
        <div v-if="selectedOrder.status === 'completed' && selectedOrder.results" class="border-t border-[var(--color-border)] pt-6">
          <h4 class="font-semibold mb-3">{{ $t('orders.results') }}</h4>
          <pre class="bg-[var(--color-bg-tertiary)] p-4 rounded-lg text-sm overflow-auto">{{ formatResults(selectedOrder.results) }}</pre>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['auth'],
  layout: 'default'
})

const config = useRuntimeConfig()
const { token, user, logout } = useAuth()
const { t, locale } = useI18n()

const statuses = ['pending', 'processing', 'completed', 'cancelled']
const activeStatus = ref('pending')
const loading = ref(false)
const orders = ref<any[]>([])
const selectedOrder = ref<any>(null)
const updating = ref(false)
const resultsText = ref('')

const updateForm = ref({
  status: ''
})

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0
})

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
  const date = new Date(value)
  return date.toLocaleDateString(locale.value === 'ar' ? 'ar' : 'en')
}

const formatResults = (results: any) => {
  if (typeof results === 'string') return results
  return JSON.stringify(results, null, 2)
}

const canUpdate = (order: any) => {
  return ['pending', 'processing'].includes(order.status)
}

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/orders`, {
      headers: { Authorization: `Bearer ${token.value}` },
      params: {
        type: 'lab',
        status: activeStatus.value,
        page,
        per_page: 10
      }
    })
    orders.value = res.data || []
    pagination.value = {
      currentPage: res.current_page || 1,
      lastPage: res.last_page || 1,
      total: res.total || 0
    }
  } catch (e) {
    console.error('Failed to fetch orders:', e)
  } finally {
    loading.value = false
  }
}

const openOrder = (order: any) => {
  selectedOrder.value = order
  updateForm.value.status = ''
  resultsText.value = ''
}

const submitUpdate = async () => {
  if (!selectedOrder.value || !updateForm.value.status) return
  
  updating.value = true
  try {
    const body: any = { status: updateForm.value.status }
    
    if (updateForm.value.status === 'completed' && resultsText.value) {
      try {
        body.results = JSON.parse(resultsText.value)
      } catch {
        body.results = { notes: resultsText.value }
      }
    }
    
    await $fetch(`${config.public.apiBase}/orders/${selectedOrder.value.id}`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${token.value}` },
      body
    })
    
    selectedOrder.value = null
    await fetchOrders(pagination.value.currentPage)
  } catch (e: any) {
    console.error('Failed to update order:', e)
  } finally {
    updating.value = false
  }
}

watch(activeStatus, () => fetchOrders(1))

onMounted(() => fetchOrders())
</script>
