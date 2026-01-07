<template>
  <span
    class="badge"
    :class="statusClass"
  >
    <span class="w-1.5 h-1.5 rounded-full me-1.5" :class="dotClass" />
    {{ $t(`ticketStatus.${status}`) }}
  </span>
</template>

<script setup lang="ts">
import type { TicketStatus } from '~/composables/useTickets'

interface Props {
  status: TicketStatus
}

const props = defineProps<Props>()

const statusClass = computed(() => {
  switch (props.status) {
    case 'pending': return 'badge-pending'
    case 'assigned': return 'badge-assigned'
    case 'awaiting_payment': return 'badge-awaiting-payment'
    case 'in_progress': return 'badge-in-progress'
    case 'completed': return 'badge-completed'
    case 'overdue': return 'badge-overdue'
    case 'closed_late': return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
    default: return ''
  }
})

const dotClass = computed(() => {
  switch (props.status) {
    case 'pending': return 'bg-amber-500'
    case 'assigned': return 'bg-indigo-500'
    case 'awaiting_payment': return 'bg-purple-500 animate-pulse'
    case 'in_progress': return 'bg-blue-500 animate-pulse'
    case 'completed': return 'bg-green-500'
    case 'overdue': return 'bg-red-500 animate-pulse'
    case 'closed_late': return 'bg-orange-500'
    default: return 'bg-gray-500'
  }
})
</script>
