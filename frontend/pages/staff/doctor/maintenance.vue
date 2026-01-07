<template>
  <NuxtLayout name="staff">
    <div class="max-w-3xl mx-auto">
      <NuxtLink to="/staff/doctor/tickets" class="flex items-center gap-2 text-[var(--color-text-secondary)] mb-4 hover:text-[var(--color-text-primary)] transition-colors">
        <svg class="w-5 h-5 icon-flip" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ $t('common.back') }}
      </NuxtLink>
      
      <h1 class="text-2xl font-bold text-[var(--color-text-primary)] mb-2">{{ $t('maintenance.reportIssue') }}</h1>
      <p class="text-[var(--color-text-muted)] mb-6">{{ $t('maintenance.reportDescription') }}</p>

      <!-- Success Message -->
      <div
        v-if="createdTicket"
        class="card p-4 mb-6 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800"
      >
        <div class="flex gap-3">
          <svg class="w-6 h-6 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <p class="font-medium text-green-800 dark:text-green-300">{{ $t('maintenance.ticketCreated') }}</p>
            <p class="text-sm text-green-700 dark:text-green-400">{{ $t('maintenance.ticketId') }}: #{{ createdTicket.id }}</p>
          </div>
        </div>
        <div class="mt-4 flex gap-2">
          <button @click="resetForm" class="btn-primary text-sm">{{ $t('maintenance.createAnother') }}</button>
          <NuxtLink to="/staff/doctor/tickets" class="btn-ghost text-sm">{{ $t('common.back') }}</NuxtLink>
        </div>
      </div>

      <!-- Error Message -->
      <div
        v-if="error"
        class="card p-4 mb-6 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800"
      >
        <div class="flex gap-3">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm text-red-700 dark:text-red-400">{{ error }}</p>
        </div>
      </div>

      <!-- Info Banner -->
      <div v-if="!createdTicket" class="card p-4 mb-6 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
        <div class="flex gap-3">
          <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm text-amber-800 dark:text-amber-300">{{ $t('maintenance.infoBanner') }}</p>
        </div>
      </div>

      <!-- Create Form -->
      <div v-if="!createdTicket" class="card p-6">
        <h3 class="font-semibold mb-4">{{ $t('maintenance.formTitle') }}</h3>
        <form @submit.prevent="submitTicket" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('maintenance.issueType') }} *</label>
            <select v-model="form.issueType" class="input" required>
              <option value="">{{ $t('common.select') }}</option>
              <option value="electrical">{{ $t('maintenance.types.electrical') }}</option>
              <option value="equipment">{{ $t('maintenance.types.equipment') }}</option>
              <option value="hvac">{{ $t('maintenance.types.hvac') }}</option>
              <option value="plumbing">{{ $t('maintenance.types.plumbing') }}</option>
              <option value="it">{{ $t('maintenance.types.it') }}</option>
              <option value="other">{{ $t('maintenance.types.other') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('tickets.subject') }} *</label>
            <input v-model="form.subject" type="text" class="input" :placeholder="$t('maintenance.subjectPlaceholder')" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('tickets.description') }} *</label>
            <textarea v-model="form.description" class="input" rows="4" :placeholder="$t('maintenance.descriptionPlaceholder')" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('tickets.priority') }}</label>
            <select v-model="form.priority" class="input">
              <option value="low">{{ $t('priority.low') }}</option>
              <option value="medium">{{ $t('priority.medium') }}</option>
              <option value="high">{{ $t('priority.high') }}</option>
              <option value="urgent">{{ $t('priority.urgent') }}</option>
            </select>
          </div>
          <button type="submit" class="btn-primary w-full" :disabled="loading || !canSubmit">
            <svg v-if="loading" class="animate-spin w-4 h-4 me-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            {{ $t('maintenance.submitTicket') }}
          </button>
        </form>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { useTickets, type CreateTicketPayload, type TicketBase } from '~/composables/useTickets'

definePageMeta({ layout: false, middleware: ['auth'] })

useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()
const { createTicket, loading, error } = useTickets()

const form = reactive({
  issueType: '',
  subject: '',
  description: '',
  priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
})

const createdTicket = ref<TicketBase | null>(null)
const maintenanceDeptId = ref<string | null>(null)

// Fetch IT & Maintenance department ID on mount
onMounted(async () => {
  try {
    const departments = await $fetch<Array<{ id: string; slug: string }>>(`${config.public.apiBase}/departments`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    const itDept = departments.find(d => d.slug === 'it-maintenance')
    if (itDept) {
      maintenanceDeptId.value = itDept.id
    }
  } catch (e) {
    console.error('Failed to fetch departments', e)
  }
})

const canSubmit = computed(() => {
  return form.issueType && form.subject.trim() && form.description.trim() && maintenanceDeptId.value
})

const submitTicket = async () => {
  if (!canSubmit.value || !maintenanceDeptId.value) return

  const fullSubject = `[${form.issueType.toUpperCase()}] ${form.subject}`

  const payload: CreateTicketPayload = {
    department_id: maintenanceDeptId.value,
    type: 'maintenance',
    subject: fullSubject,
    description: form.description,
    priority: form.priority,
    assigned_to: null, // Unassigned, maintenance staff will pick it up
  }

  const ticket = await createTicket(payload)
  if (ticket) {
    createdTicket.value = ticket
  }
}

const resetForm = () => {
  createdTicket.value = null
  form.issueType = ''
  form.subject = ''
  form.description = ''
  form.priority = 'medium'
}
</script>
