<template>
  <div class="case-details-card card p-5">
    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      {{ $t('doctorPortal.caseDetails') }}
    </h3>

    <div class="space-y-3">
      <!-- Ticket ID -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('doctorPortal.ticketId') }}</span>
        <span class="font-mono font-semibold text-primary-600">#{{ ticket.id }}</span>
      </div>

      <!-- Department -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('tickets.department') }}</span>
        <span class="font-medium">{{ departmentName }}</span>
      </div>

      <!-- Assigned Doctor -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('doctorPortal.assignedDoctor') }}</span>
        <span class="font-medium">{{ ticket.assignee?.name || $t('common.notAssigned') }}</span>
      </div>

      <!-- Status with Badge -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('tickets.status') }}</span>
        <TicketStatusBadge :status="ticket.status" />
      </div>

      <!-- SLA Status -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('tickets.sla') }}</span>
        <span 
          class="px-2 py-1 text-xs font-medium rounded-full"
          :class="slaClass"
        >
          {{ slaText }}
        </span>
      </div>

      <!-- Scheduled Date -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('tickets.scheduledAt') }}</span>
        <span class="font-medium">{{ formatDate(ticket.scheduled_at) }}</span>
      </div>

      <!-- Created Date -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-[var(--color-text-muted)]">{{ $t('tickets.createdAt') }}</span>
        <span class="text-sm text-[var(--color-text-secondary)]">{{ formatDate(ticket.created_at) }}</span>
      </div>

      <!-- Divider -->
      <div class="border-t border-[var(--color-border)] my-3"></div>

      <!-- Subject -->
      <div>
        <span class="text-sm text-[var(--color-text-muted)] block mb-1">{{ $t('tickets.subject') }}</span>
        <p class="font-medium text-[var(--color-text-primary)]">{{ ticket.subject }}</p>
      </div>

      <!-- Description / Symptoms -->
      <div v-if="ticket.description">
        <span class="text-sm text-[var(--color-text-muted)] block mb-1">{{ $t('doctorPortal.symptoms') }}</span>
        <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-wrap">{{ ticket.description }}</p>
      </div>

      <!-- Additional Notes -->
      <div v-if="ticket.additional_notes">
        <span class="text-sm text-[var(--color-text-muted)] block mb-1">{{ $t('patientInfo.additionalNotes') }}</span>
        <p class="text-sm text-[var(--color-text-secondary)] whitespace-pre-wrap">{{ ticket.additional_notes }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  ticket: {
    id: number
    subject: string
    description?: string | null
    status: any // TicketStatus compatible
    department?: { name_en?: string; name_ar?: string } | null
    department_id?: string
    assignee?: { name?: string } | null
    scheduled_at?: string | null
    created_at: string
    additional_notes?: string | null
    sla?: { is_overdue?: boolean; minutes_remaining?: number | null }
  }
}>()

const { t, locale } = useI18n()

const departmentName = computed(() => {
  if (!props.ticket.department) return props.ticket.department_id || '-'
  return locale.value === 'ar' ? props.ticket.department.name_ar : props.ticket.department.name_en
})

const slaClass = computed(() => {
  if (!props.ticket.sla) return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
  if (props.ticket.sla.is_overdue) return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
  return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
})

const slaText = computed(() => {
  if (!props.ticket.sla || props.ticket.sla.minutes_remaining === null || props.ticket.sla.minutes_remaining === undefined) return '-'
  const mins = Math.trunc(props.ticket.sla.minutes_remaining)
  if (props.ticket.sla.is_overdue || mins < 0) {
    return `${t('doctorPortal.overdue')} ${Math.abs(mins)} ${t('tickets.minutes')}`
  }
  return `${mins} ${t('tickets.minutes')} ${t('doctorPortal.remaining')}`
})

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
