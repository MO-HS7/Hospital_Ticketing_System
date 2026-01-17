<template>
  <div
    class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-800 space-y-4"
    role="alert"
  >
    <!-- Header -->
    <div class="flex items-start gap-3">
      <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/50 flex items-center justify-center shrink-0">
        <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <div class="flex-1">
        <h3 class="font-bold text-red-800 dark:text-red-200 text-base">
          🚨 {{ $t('chatbot.emergency.title') }}
        </h3>
        <p class="text-sm text-red-700 dark:text-red-300 mt-0.5">
          {{ $t('chatbot.emergency.subtitle') }}
        </p>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
      <!-- Call 911 -->
      <a
        href="tel:911"
        class="flex-1 min-w-[140px] flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm transition-all shadow-md"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
        </svg>
        {{ $t('chatbot.emergency.call911') }}
      </a>
      
      <!-- Contact Hospital -->
      <a
        v-if="hospitalPhone"
        :href="`tel:${hospitalPhone}`"
        class="flex-1 min-w-[140px] flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white dark:bg-white/10 text-red-700 dark:text-red-300 font-semibold text-sm border border-red-300 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/50 transition-all"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        {{ $t('chatbot.emergency.contactHospital') }}
      </a>
      
      <!-- Continue Chat -->
      <button
        @click="$emit('continue')"
        class="flex-1 min-w-[140px] flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
      >
        {{ $t('chatbot.emergency.continue') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  hospitalPhone?: string
}>()

defineEmits<{
  (e: 'continue'): void
}>()
</script>
