<template>
  <button
    :class="[
      'group relative flex items-center gap-4 p-4 rounded-2xl border-2 transition-all duration-200 w-full',
      'bg-white dark:bg-white/5 hover:bg-blue-50 dark:hover:bg-blue-900/20',
      selected
        ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md shadow-blue-500/10'
        : 'border-slate-200 dark:border-white/10 hover:border-blue-300 dark:hover:border-blue-500/50',
    ]"
    @click="$emit('select', doctor)"
    :aria-label="$t('doctor.select') + ': ' + doctor.name"
  >
    <!-- Avatar -->
    <div
      :class="[
        'w-14 h-14 rounded-xl flex items-center justify-center shrink-0 font-bold text-lg transition-colors',
        selected
          ? 'bg-blue-500 text-white'
          : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
      ]"
    >
      {{ initials }}
    </div>

    <!-- Content -->
    <div class="flex-1 text-start min-w-0">
      <h4
        :class="[
          'font-semibold text-sm leading-tight transition-colors',
          selected
            ? 'text-blue-700 dark:text-blue-300'
            : 'text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-300',
        ]"
      >
        {{ displayName }}
      </h4>
      <p
        v-if="doctor.specialty"
        class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
      >
        {{ doctor.specialty }}
      </p>
      
      <!-- Availability Chips -->
      <div v-if="doctor.availability || doctor.next_available" class="flex items-center gap-2 mt-2">
        <span
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
          {{ doctor.availability || 'Available' }}
        </span>
        <span
          v-if="doctor.next_available"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400"
        >
          {{ doctor.next_available }}
        </span>
      </div>
    </div>

    <!-- Arrow -->
    <svg
      :class="[
        'w-5 h-5 shrink-0 transition-all rtl:rotate-180',
        selected
          ? 'text-blue-500 translate-x-1 rtl:-translate-x-1'
          : 'text-slate-300 dark:text-slate-600 group-hover:text-blue-400 group-hover:translate-x-1 rtl:group-hover:-translate-x-1',
      ]"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>

    <!-- Selected Check -->
    <div
      v-if="selected"
      class="absolute top-2 end-2 w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center"
    >
      <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
      </svg>
    </div>
  </button>
</template>

<script setup lang="ts">
interface Doctor {
  id: number | string
  name: string
  specialty?: string
  availability?: string
  next_available?: string
}

const props = defineProps<{
  doctor: Doctor
  selected?: boolean
}>()

defineEmits<{
  (e: 'select', doctor: Doctor): void
}>()

const displayName = computed(() => {
  const name = props.doctor.name || ''
  // Add "Dr." prefix if not already present
  if (name.toLowerCase().startsWith('dr')) {
    return name
  }
  return `Dr. ${name}`
})

const initials = computed(() => {
  const name = props.doctor.name || ''
  const parts = name.split(' ').filter(Boolean)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})
</script>
