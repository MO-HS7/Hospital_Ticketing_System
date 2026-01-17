<template>
  <button
    :class="[
      'group relative flex items-center gap-4 p-4 rounded-2xl border-2 transition-all duration-200 w-full',
      'bg-white dark:bg-white/5',
      selected
        ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md shadow-emerald-500/10'
        : type === 'online'
          ? 'border-slate-200 dark:border-white/10 hover:border-emerald-300 dark:hover:border-emerald-500/50 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
          : 'border-slate-200 dark:border-white/10 hover:border-amber-300 dark:hover:border-amber-500/50 hover:bg-amber-50 dark:hover:bg-amber-900/20',
    ]"
    @click="$emit('select', type)"
    :aria-label="label"
  >
    <!-- Icon -->
    <div
      :class="[
        'w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors',
        selected
          ? 'bg-emerald-500 text-white'
          : type === 'online'
            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
            : 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
      ]"
    >
      <!-- Online Payment Icon -->
      <svg v-if="type === 'online'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
      </svg>
      <!-- Pay at Hospital Icon -->
      <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
    </div>

    <!-- Content -->
    <div class="flex-1 text-start min-w-0">
      <h4
        :class="[
          'font-semibold text-sm leading-tight transition-colors',
          selected
            ? 'text-emerald-700 dark:text-emerald-300'
            : 'text-slate-900 dark:text-white',
        ]"
      >
        {{ label }}
      </h4>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
        {{ description }}
      </p>
      
      <!-- Badge -->
      <span
        v-if="type === 'online'"
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 mt-2"
      >
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        {{ $t('payment.secure') || 'Secure' }}
      </span>
    </div>

    <!-- Arrow / Check -->
    <div class="shrink-0">
      <div
        v-if="selected"
        class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center"
      >
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <svg
        v-else
        :class="[
          'w-5 h-5 transition-all rtl:rotate-180',
          'text-slate-300 dark:text-slate-600 group-hover:translate-x-1 rtl:group-hover:-translate-x-1',
          type === 'online' ? 'group-hover:text-emerald-400' : 'group-hover:text-amber-400',
        ]"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </div>
  </button>
</template>

<script setup lang="ts">
const props = defineProps<{
  type: 'online' | 'pay_at_hospital'
  label: string
  description?: string
  selected?: boolean
}>()

defineEmits<{
  (e: 'select', type: string): void
}>()
</script>
