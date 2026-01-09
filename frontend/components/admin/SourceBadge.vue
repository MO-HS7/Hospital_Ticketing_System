<template>
  <span 
    class="source-badge inline-flex items-center gap-1 rounded-full font-medium"
    :class="[sizeClasses, bgClasses]"
    :title="tooltipText"
  >
    <span v-if="chatbot > 0" class="flex items-center gap-0.5">
      <svg class="shrink-0" :class="iconSizeClass" viewBox="0 0 20 20" fill="currentColor">
        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
      </svg>
      <span>{{ chatbotPercent }}%</span>
    </span>
    <span v-if="manual > 0 && chatbot > 0" class="text-[var(--color-text-muted)]">/</span>
    <span v-if="manual > 0" class="flex items-center gap-0.5">
      <svg class="shrink-0" :class="iconSizeClass" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
      </svg>
      <span>{{ manualPercent }}%</span>
    </span>
  </span>
</template>

<script setup lang="ts">
interface Props {
  manual: number
  chatbot: number
  size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md'
})

const { t } = useI18n()

const total = computed(() => props.manual + props.chatbot)
const manualPercent = computed(() => total.value > 0 ? Math.round((props.manual / total.value) * 100) : 0)
const chatbotPercent = computed(() => total.value > 0 ? Math.round((props.chatbot / total.value) * 100) : 0)

const tooltipText = computed(() => 
  `${t('commandCenter.manual')}: ${props.manual} | ${t('commandCenter.chatbot')}: ${props.chatbot}`
)

const sizeClasses = computed(() => ({
  'px-1.5 py-0.5 text-xs': props.size === 'sm',
  'px-2 py-1 text-xs': props.size === 'md',
}))

const iconSizeClass = computed(() => ({
  'w-3 h-3': props.size === 'sm',
  'w-3.5 h-3.5': props.size === 'md',
}))

const bgClasses = 'bg-[var(--color-bg-tertiary)] text-[var(--color-text-secondary)]'
</script>
