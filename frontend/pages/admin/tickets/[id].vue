<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: ['auth'] })

const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const { token } = useAuth()

const ticketId = computed(() => route.params.id as string)

// Data
const ticket = ref<any>(null)
const loading = ref(true)
const error = ref('')

// Load ticket details
const loadTicket = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/tickets/${ticketId.value}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    ticket.value = res
  } catch (e: any) {
    error.value = e.message || t('adminTickets.loadError')
  } finally {
    loading.value = false
  }
}

// Status badge classes
const getStatusClass = (status: string) => {
  switch (status) {
    case 'pending': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
    case 'in_progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
    case 'completed': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
    case 'overdue': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
    case 'awaiting_payment': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
  }
}

// Priority badge classes
const getPriorityClass = (priority: string) => {
  switch (priority) {
    case 'urgent': return 'text-red-600 bg-red-100 dark:bg-red-900/30'
    case 'high': return 'text-amber-600 bg-amber-100 dark:bg-amber-900/30'
    case 'medium': return 'text-blue-600 bg-blue-100 dark:bg-blue-900/30'
    default: return 'text-gray-500 bg-gray-100 dark:bg-gray-900/30'
  }
}



const formatShortDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// SLA countdown
const slaStatus = computed(() => {
  if (!ticket.value?.deadline || ticket.value?.completed_at) return null
  const deadline = new Date(ticket.value.deadline)
  const now = new Date()
  const diff = deadline.getTime() - now.getTime()
  const minutes = Math.floor(diff / 60000)
  
  if (minutes < 0) return { status: 'breached', label: t('adminTickets.breached'), class: 'text-red-600 bg-red-100 dark:bg-red-900/30' }
  if (minutes <= 30) return { status: 'at_risk', label: `${minutes}m`, class: 'text-amber-600 bg-amber-100 dark:bg-amber-900/30' }
  return { status: 'ok', label: `${Math.floor(minutes / 60)}h ${minutes % 60}m`, class: 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30' }
})

// Go back
const goBack = () => router.push('/admin/tickets')

onMounted(() => {
  loadTicket()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Back Button + Title -->
    <div class="flex items-center gap-4">
      <button @click="goBack" class="btn-ghost p-2 rounded-lg">
        <Icon name="arrow-left" size="md" />
      </button>
      <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">
          {{ t('tickets.ticketDetails') }} #{{ ticketId }}
        </h1>
        <p class="text-[var(--color-text-muted)]">{{ t('adminTickets.subtitle') }}</p>
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="card p-8">
      <div class="animate-pulse space-y-6">
        <div class="h-8 bg-[var(--color-bg-tertiary)] rounded w-1/3"></div>
        <div class="h-4 bg-[var(--color-bg-tertiary)] rounded w-1/2"></div>
        <div class="h-32 bg-[var(--color-bg-tertiary)] rounded"></div>
      </div>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="card p-8 text-center">
      <Icon name="exclamation-triangle" size="xl" class="text-red-500 mb-4" />
      <p class="text-[var(--color-text-muted)] mb-4">{{ error }}</p>
      <div class="flex justify-center gap-3">
        <button @click="loadTicket" class="btn-primary">{{ t('common.retry') }}</button>
        <button @click="goBack" class="btn-secondary">{{ t('common.back') }}</button>
      </div>
    </div>
    
    <!-- Ticket Details -->
    <template v-else-if="ticket">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Header Card -->
          <div class="card p-6">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h2 class="text-xl font-semibold text-[var(--color-text-primary)]">{{ ticket.subject }}</h2>
                <div class="flex items-center gap-3 mt-2 text-sm text-[var(--color-text-muted)]">
                  <span class="flex items-center gap-1">
                    <Icon :name="ticket.source === 'chatbot' ? 'robot' : 'user'" size="xs" />
                    {{ t(`commandCenter.${ticket.source || 'manual'}`) }}
                  </span>
                  <span>{{ formatShortDate(ticket.created_at) }}</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusClass(ticket.status)]">
                  {{ t(`ticketStatus.${ticket.status}`) }}
                </span>
              </div>
            </div>
            
            <!-- Description -->
            <div class="prose prose-sm dark:prose-invert max-w-none">
              <p class="text-[var(--color-text-secondary)] whitespace-pre-wrap">{{ ticket.description || t('tickets.noDescription') }}</p>
            </div>
          </div>
          
          <!-- Notes / Timeline -->
          <div class="card p-6">
            <h3 class="font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
              <Icon name="clock-rotate-left" size="sm" />
              {{ t('tickets.timeline') }}
            </h3>
            
            <div v-if="ticket.notes?.length > 0" class="space-y-4">
              <div v-for="note in ticket.notes" :key="note.id" class="flex gap-3 p-3 rounded-lg bg-[var(--color-bg-tertiary)]">
                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0">
                  <Icon name="message" size="xs" class="text-primary-600" />
                </div>
                <div class="flex-1">
                  <p class="text-sm text-[var(--color-text-primary)]">{{ note.content }}</p>
                  <p class="text-xs text-[var(--color-text-muted)] mt-1">
                    {{ note.user?.name }} · {{ formatShortDate(note.created_at) }}
                  </p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6 text-[var(--color-text-muted)]">
              <Icon name="inbox" size="lg" class="opacity-50 mb-2" />
              <p>{{ t('tickets.noNotes') }}</p>
            </div>
          </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Quick Info Card -->
          <div class="card p-4 space-y-4">
            <h3 class="font-semibold text-[var(--color-text-primary)]">{{ t('tickets.details') }}</h3>
            
            <!-- Priority -->
            <div class="flex items-center justify-between py-2 border-b border-[var(--color-border)]">
              <span class="text-sm text-[var(--color-text-muted)]">{{ t('tickets.priority') }}</span>
              <span :class="['px-2 py-0.5 rounded text-xs font-medium', getPriorityClass(ticket.priority)]">
                {{ t(`priority.${ticket.priority}`) }}
              </span>
            </div>
            
            <!-- SLA Status -->
            <div v-if="slaStatus" class="flex items-center justify-between py-2 border-b border-[var(--color-border)]">
              <span class="text-sm text-[var(--color-text-muted)]">SLA</span>
              <span :class="['px-2 py-0.5 rounded text-xs font-medium', slaStatus.class]">
                {{ slaStatus.label }}
              </span>
            </div>
            
            <!-- Department -->
            <div class="flex items-center justify-between py-2 border-b border-[var(--color-border)]">
              <span class="text-sm text-[var(--color-text-muted)]">{{ t('tickets.department') }}</span>
              <span class="text-sm font-medium text-[var(--color-text-primary)]">
                {{ locale === 'ar' ? ticket.department?.name_ar : ticket.department?.name_en }}
              </span>
            </div>
            
            <!-- Patient -->
            <div class="flex items-center justify-between py-2 border-b border-[var(--color-border)]">
              <span class="text-sm text-[var(--color-text-muted)]">{{ t('tickets.patient') }}</span>
              <span class="text-sm font-medium text-[var(--color-text-primary)]">
                {{ ticket.patient?.name || '—' }}
              </span>
            </div>
            
            <!-- Assignee -->
            <div class="flex items-center justify-between py-2 border-b border-[var(--color-border)]">
              <span class="text-sm text-[var(--color-text-muted)]">{{ t('tickets.assignedTo') }}</span>
              <span class="text-sm font-medium text-[var(--color-text-primary)]">
                {{ ticket.assignee?.name || t('tickets.unassigned') }}
              </span>
            </div>
            
            <!-- Deadline -->
            <div v-if="ticket.deadline" class="flex items-center justify-between py-2">
              <span class="text-sm text-[var(--color-text-muted)]">{{ t('tickets.deadline') }}</span>
              <span class="text-sm font-medium text-[var(--color-text-primary)]">
                {{ formatShortDate(ticket.deadline) }}
              </span>
            </div>
          </div>
          
          <!-- Timestamps Card -->
          <div class="card p-4 space-y-3">
            <h3 class="font-semibold text-[var(--color-text-primary)]">{{ t('tickets.timestamps') }}</h3>
            
            <div class="text-sm space-y-2">
              <div class="flex justify-between">
                <span class="text-[var(--color-text-muted)]">{{ t('tickets.createdAt') }}</span>
                <span class="text-[var(--color-text-secondary)]">{{ formatShortDate(ticket.created_at) }}</span>
              </div>
              <div v-if="ticket.accepted_at" class="flex justify-between">
                <span class="text-[var(--color-text-muted)]">{{ t('tickets.acceptedAt') }}</span>
                <span class="text-[var(--color-text-secondary)]">{{ formatShortDate(ticket.accepted_at) }}</span>
              </div>
              <div v-if="ticket.completed_at" class="flex justify-between">
                <span class="text-[var(--color-text-muted)]">{{ t('tickets.completedAt') }}</span>
                <span class="text-emerald-600">{{ formatShortDate(ticket.completed_at) }}</span>
              </div>
            </div>
          </div>
          
          <!-- Actions Card -->
          <div class="card p-4">
            <h3 class="font-semibold text-[var(--color-text-primary)] mb-3">{{ t('departments.actions') }}</h3>
            <div class="space-y-2">
              <button @click="goBack" class="btn-secondary w-full justify-center">
                <Icon name="arrow-left" size="sm" class="me-1.5" />
                {{ t('common.back') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
