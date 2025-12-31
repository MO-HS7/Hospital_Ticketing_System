<template>
  <NuxtLayout name="patient">
    <div class="max-w-3xl mx-auto">
      <!-- Consent Banner -->
      <div class="card p-4 mb-6 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
        <div class="flex gap-3">
          <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <p class="text-sm text-amber-800 dark:text-amber-300">{{ $t('chatbot.disclaimer') }}</p>
        </div>
      </div>

      <!-- Chat Window -->
      <div class="card overflow-hidden flex flex-col h-[600px]">
        <!-- Header -->
        <div class="p-4 border-b border-[var(--color-border)] bg-[var(--color-bg-tertiary)]">
          <h2 class="font-semibold text-[var(--color-text-primary)]">{{ $t('chatbot.title') }}</h2>
          <p class="text-sm text-[var(--color-text-muted)]">{{ $t('chatbot.subtitle') }}</p>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
          <div v-for="msg in messages" :key="msg.id" :class="['max-w-[80%] p-3 rounded-2xl', msg.role === 'user' ? 'message-user bg-primary-600 text-white' : 'message-bot bg-[var(--color-bg-tertiary)] text-[var(--color-text-primary)]']">
            <p class="text-sm">{{ msg.content }}</p>
            <!-- Quick Action -->
            <button v-if="msg.action" @click="handleAction(msg.action)" class="mt-2 text-xs font-medium px-3 py-1.5 rounded-lg bg-white/20 hover:bg-white/30">
              {{ $t('chatbot.bookNow') }}
            </button>
          </div>
          <div v-if="typing" class="message-bot bg-[var(--color-bg-tertiary)] p-3 rounded-2xl max-w-[80%]">
            <div class="flex gap-1"><span class="w-2 h-2 rounded-full bg-[var(--color-text-muted)] animate-bounce" /><span class="w-2 h-2 rounded-full bg-[var(--color-text-muted)] animate-bounce" style="animation-delay:0.1s" /><span class="w-2 h-2 rounded-full bg-[var(--color-text-muted)] animate-bounce" style="animation-delay:0.2s" /></div>
          </div>
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-[var(--color-border)]">
          <form @submit.prevent="sendMessage" class="flex gap-2">
            <input v-model="input" type="text" :placeholder="$t('chatbot.placeholder')" class="input flex-1" />
            <button type="submit" class="btn-primary px-4" :disabled="!input.trim()">
              <svg class="w-5 h-5 icon-flip" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })
const router = useRouter()
const { t } = useI18n()
const input = ref('')
const typing = ref(false)
const messagesContainer = ref<HTMLElement>()
interface ChatMessage { id: number; role: string; content: string; action?: string }
const messages = ref<ChatMessage[]>([
  { id: 1, role: 'bot', content: t('chatbot.welcome') }
])

const responses: Record<string, { text: string; dept?: string }> = {
  'heart': { text: 'Based on your symptoms, I suggest the Cardiology department.', dept: 'cardiology' },
  'chest': { text: 'Based on your symptoms, I suggest the Cardiology department.', dept: 'cardiology' },
  'bone': { text: 'Based on your symptoms, I suggest the Orthopedics department.', dept: 'orthopedics' },
  'skin': { text: 'Based on your symptoms, I suggest the Dermatology department.', dept: 'dermatology' },
  'head': { text: 'Based on your symptoms, I suggest the Neurology department.', dept: 'neurology' },
  'child': { text: 'Based on your symptoms, I suggest the Pediatrics department.', dept: 'pediatrics' },
}

const sendMessage = async () => {
  if (!input.value.trim()) return
  const userMsg = input.value
  messages.value.push({ id: Date.now(), role: 'user', content: userMsg })
  input.value = ''
  typing.value = true
  await new Promise(r => setTimeout(r, 1000))
  
  const key = Object.keys(responses).find(k => userMsg.toLowerCase().includes(k))
  const resp = key ? responses[key] : { text: 'I can help you find the right department. Please describe your symptoms.' }
  messages.value.push({ id: Date.now() + 1, role: 'bot', content: resp.text, action: resp.dept })
  typing.value = false
  nextTick(() => messagesContainer.value?.scrollTo({ top: messagesContainer.value.scrollHeight, behavior: 'smooth' }))
}

const handleAction = (dept: string) => {
  router.push(`/patient/tickets/create?dept=${dept}`)
}
</script>
