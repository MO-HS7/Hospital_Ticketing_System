<template>
  <div class="medical-orders-section card p-5">
    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
      </svg>
      {{ $t('doctorPortal.medicalOrders') }}
    </h3>

    <!-- Order Type Tabs -->
    <div class="flex border-b border-[var(--color-border)] mb-4">
      <button
        v-for="tab in orderTabs"
        :key="tab.value"
        @click="activeTab = tab.value as 'lab' | 'radiology' | 'pharmacy'"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          activeTab === tab.value
            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
            : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Create Order Form (Inline) -->
    <div v-if="showCreateForm" class="bg-[var(--color-bg-tertiary)] rounded-lg p-4 mb-4">
      <!-- Patient Context Banner -->
      <div class="flex items-center gap-3 mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <div class="flex-1">
          <p class="font-medium text-primary-700 dark:text-primary-300">{{ patientName }}</p>
          <p class="text-xs text-primary-600/70 dark:text-primary-400/70">{{ $t('doctorPortal.ticketId') }}: #{{ ticketId }}</p>
        </div>
        <span v-if="isEmergency" class="px-2 py-1 text-xs font-bold bg-red-500 text-white rounded">🚨 {{ $t('doctorPortal.emergency') }}</span>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('doctorPortal.orderItems') }}</label>
          <textarea 
            v-model="newOrder.items" 
            rows="3" 
            class="input w-full" 
            :placeholder="getOrderPlaceholder(activeTab)"
          ></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('doctorPortal.instructions') }}</label>
          <input v-model="newOrder.instructions" type="text" class="input w-full" :placeholder="$t('doctorPortal.instructionsPlaceholder')" />
        </div>
        <div class="flex justify-end gap-2">
          <button @click="showCreateForm = false" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
          <button @click="createOrder" :disabled="!newOrder.items.trim() || creating" class="btn-primary text-sm">
            <span v-if="creating" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ $t('doctorPortal.createOrder') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Order Button -->
    <button v-if="!showCreateForm && canCreateOrders" @click="showCreateForm = true" class="w-full py-3 px-4 border-2 border-dashed border-[var(--color-border)] rounded-lg text-[var(--color-text-muted)] hover:border-primary-500 hover:text-primary-600 transition-colors flex items-center justify-center gap-2 mb-4">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
      </svg>
      {{ $t('doctorPortal.addOrder') }} ({{ activeTabLabel }})
    </button>

    <!-- Orders List -->
    <div v-if="orders.length === 0" class="text-sm text-[var(--color-text-muted)] text-center py-4">
      {{ $t('doctorPortal.noOrders') }}
    </div>
    <div v-else class="space-y-3">
      <div 
        v-for="order in filteredOrders" 
        :key="order.id" 
        class="p-3 rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-primary)]"
      >
        <div class="flex items-start justify-between gap-2 mb-2">
          <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="getOrderTypeClass(order.order_type)">
            {{ getOrderTypeLabel(order.order_type) }}
          </span>
          <span class="text-xs text-[var(--color-text-muted)]">{{ formatDate(order.created_at) }}</span>
        </div>
        <p class="text-sm font-medium text-[var(--color-text-primary)]">{{ order.items }}</p>
        <p v-if="order.instructions" class="text-xs text-[var(--color-text-muted)] mt-1">{{ order.instructions }}</p>
        <p class="text-xs text-[var(--color-text-muted)] mt-2">{{ $t('doctorPortal.orderedBy') }}: {{ order.created_by?.name || '-' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Order {
  id: number
  order_type: 'lab' | 'radiology' | 'pharmacy'
  items: string
  instructions?: string
  status: string
  created_at: string
  created_by?: { name?: string }
}

const props = defineProps<{
  ticketId: number
  patientName: string
  isEmergency?: boolean
  orders: Order[]
  canCreateOrders: boolean
}>()

const emit = defineEmits<{
  orderCreated: []
}>()

const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()

const activeTab = ref<'lab' | 'radiology' | 'pharmacy'>('lab')
const showCreateForm = ref(false)
const creating = ref(false)
const newOrder = ref({
  items: '',
  instructions: '',
})

const orderTabs = [
  { value: 'lab', label: t('doctorPortal.laboratory') },
  { value: 'radiology', label: t('doctorPortal.radiology') },
  { value: 'pharmacy', label: t('doctorPortal.pharmacy') },
]

const activeTabLabel = computed(() => orderTabs.find(t => t.value === activeTab.value)?.label || '')

const filteredOrders = computed(() => {
  return props.orders.filter(o => o.order_type === activeTab.value)
})

const getOrderPlaceholder = (type: string) => {
  if (type === 'lab') return t('doctorPortal.labPlaceholder')
  if (type === 'radiology') return t('doctorPortal.radiologyPlaceholder')
  return t('doctorPortal.pharmacyPlaceholder')
}

const getOrderTypeClass = (type: string) => {
  if (type === 'lab') return 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400'
  if (type === 'radiology') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
  return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
}

const getOrderTypeLabel = (type: string) => {
  if (type === 'lab') return t('doctorPortal.laboratory')
  if (type === 'radiology') return t('doctorPortal.radiology')
  return t('doctorPortal.pharmacy')
}

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const createOrder = async () => {
  if (!newOrder.value.items.trim()) return
  creating.value = true
  try {
    await $fetch(`${config.public.apiBase}/tickets/${props.ticketId}/orders`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: {
        order_type: activeTab.value,
        items: newOrder.value.items.trim(),
        instructions: newOrder.value.instructions.trim() || null,
      }
    })
    newOrder.value = { items: '', instructions: '' }
    showCreateForm.value = false
    emit('orderCreated')
  } catch (e: any) {
    console.error('Failed to create order:', e)
    alert(e.data?.message || 'Failed to create order')
  } finally {
    creating.value = false
  }
}
</script>
