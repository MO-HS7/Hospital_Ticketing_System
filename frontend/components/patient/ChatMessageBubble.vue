<template>
  <div class="w-full" :class="isUser ? 'flex justify-end' : 'flex justify-start'">
    <!-- User Message -->
    <div v-if="isUser" class="flex flex-col items-end max-w-[80%] group">
      <div
        class="px-4 py-3 rounded-2xl rounded-tr-md bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-md"
      >
        <p class="text-[15px] leading-relaxed whitespace-pre-wrap">{{ content }}</p>
      </div>
      <span
        class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 opacity-0 group-hover:opacity-100 transition-opacity"
      >
        {{ formattedTime }}
      </span>
    </div>

    <!-- Bot Message -->
    <div v-else class="flex gap-3 max-w-[85%] group">
      <!-- Avatar -->
      <div
        class="w-8 h-8 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center shrink-0 shadow-sm border border-white dark:border-slate-600"
      >
        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
      </div>

      <div class="flex flex-col gap-1 min-w-0">
        <!-- Text Bubble -->
        <div
          :class="[
            'px-4 py-3 rounded-2xl rounded-tl-md shadow-sm',
            isEmergency
              ? 'bg-red-50 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-800'
              : 'bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10',
          ]"
        >
          <p
            :class="[
              'text-[15px] leading-relaxed whitespace-pre-wrap',
              isEmergency ? 'text-red-800 dark:text-red-200' : 'text-slate-800 dark:text-slate-100',
            ]"
            v-html="formattedContent"
          />
        </div>

        <!-- Timestamp -->
        <span
          class="text-[10px] text-slate-400 dark:text-slate-500 ms-1 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          {{ formattedTime }}
        </span>

        <!-- Slot for interactive elements (cards, buttons) -->
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  content: string
  isUser?: boolean
  isEmergency?: boolean
  timestamp?: Date | string | number
}>()

const formattedContent = computed(() => {
  let text = props.content || ''
  
  // Convert markdown bold to HTML
  text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
  
  // Convert markdown italic to HTML
  text = text.replace(/\*(.*?)\*/g, '<em>$1</em>')
  
  return text
})

const formattedTime = computed(() => {
  if (!props.timestamp) {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  
  const date = new Date(props.timestamp)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
})
</script>
