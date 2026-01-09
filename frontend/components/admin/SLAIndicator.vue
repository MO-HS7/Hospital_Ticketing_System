<template>
  <div class="sla-indicator flex items-center gap-2">
    <!-- Progress bar -->
    <div class="flex-1 h-2 bg-[var(--color-bg-tertiary)] rounded-full overflow-hidden">
      <div 
        class="h-full rounded-full transition-all duration-300"
        :class="progressColorClass"
        :style="{ width: `${progressPercent}%` }"
      />
    </div>
    
    <!-- Time text -->
    <span class="shrink-0 text-xs font-medium" :class="textColorClass">
      <template v-if="isOverdue">
        {{ $t('doctorPortal.overdue') }} {{ formattedTime }}
      </template>
      <template v-else>
        {{ formattedTime }} {{ $t('doctorPortal.remaining') }}
      </template>
    </span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  deadline: string | Date
  createdAt?: string | Date
}

const props = defineProps<Props>()

const now = ref(new Date())

// Update time every minute
onMounted(() => {
  const interval = setInterval(() => {
    now.value = new Date()
  }, 60000)
  
  onUnmounted(() => clearInterval(interval))
})

const deadlineDate = computed(() => new Date(props.deadline))
const createdDate = computed(() => props.createdAt ? new Date(props.createdAt) : null)

const isOverdue = computed(() => now.value > deadlineDate.value)

const remainingMs = computed(() => {
  const diff = deadlineDate.value.getTime() - now.value.getTime()
  return Math.max(0, diff)
})

const overdueMs = computed(() => {
  if (!isOverdue.value) return 0
  return now.value.getTime() - deadlineDate.value.getTime()
})

// Calculate progress: 0% = just created, 100% = at deadline
const progressPercent = computed(() => {
  if (isOverdue.value) return 100
  
  if (createdDate.value) {
    const totalTime = deadlineDate.value.getTime() - createdDate.value.getTime()
    const elapsed = now.value.getTime() - createdDate.value.getTime()
    return Math.min(100, Math.max(0, (elapsed / totalTime) * 100))
  }
  
  // Fallback: use remaining time as pseudo-progress
  const oneHour = 60 * 60 * 1000
  return Math.min(100, Math.max(0, 100 - (remainingMs.value / oneHour) * 100))
})

// Color based on urgency
const urgencyLevel = computed(() => {
  if (isOverdue.value) return 'critical'
  const minutes = remainingMs.value / (60 * 1000)
  if (minutes <= 15) return 'critical'
  if (minutes <= 30) return 'warning'
  return 'normal'
})

const progressColorClass = computed(() => ({
  'bg-red-500': urgencyLevel.value === 'critical',
  'bg-amber-500': urgencyLevel.value === 'warning',
  'bg-green-500': urgencyLevel.value === 'normal',
}))

const textColorClass = computed(() => ({
  'text-red-600 dark:text-red-400': urgencyLevel.value === 'critical',
  'text-amber-600 dark:text-amber-400': urgencyLevel.value === 'warning',
  'text-green-600 dark:text-green-400': urgencyLevel.value === 'normal',
}))

const formattedTime = computed(() => {
  const ms = isOverdue.value ? overdueMs.value : remainingMs.value
  const minutes = Math.floor(ms / (60 * 1000))
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)
  
  if (days > 0) return `${days}d ${hours % 24}h`
  if (hours > 0) return `${hours}h ${minutes % 60}m`
  return `${minutes}m`
})
</script>
