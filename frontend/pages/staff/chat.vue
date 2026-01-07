<template>
  <NuxtLayout name="staff">
    <div class="h-[calc(100vh-140px)] max-w-4xl mx-auto flex flex-col bg-[var(--color-bg-primary)] rounded-2xl border border-[var(--color-border)] shadow-lg overflow-hidden">
      
      <!-- Header -->
      <div class="px-6 py-4 border-b border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-600 flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h1 class="font-bold text-lg text-[var(--color-text-primary)]">
                {{ $t('staffChatbot.title') }}
              </h1>
              <p class="text-xs text-amber-600">
                {{ $t('staffChatbot.subtitle') }}
              </p>
            </div>
          </div>
          <button 
            @click="resetChat" 
            class="px-3 py-1.5 text-sm rounded-lg bg-[var(--color-bg-tertiary)] hover:bg-[var(--color-bg-secondary)] text-[var(--color-text-secondary)] transition-colors"
          >
            {{ $t('common.reset') }}
          </button>
        </div>
      </div>
      
      <!-- Important Notice -->
      <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800 text-center">
        <p class="text-xs text-amber-800 dark:text-amber-300">
          ⚠️ {{ $t('staffChatbot.maintenanceOnly') }}
        </p>
      </div>

      <!-- Messages Area -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
        <div v-for="msg in messages" :key="msg.id" :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']">
          <!-- User Message -->
          <div v-if="msg.role === 'user'" class="max-w-[80%] px-4 py-3 rounded-2xl rounded-tr-sm bg-amber-600 text-white">
            <p class="text-sm">{{ msg.content }}</p>
          </div>
          <!-- Bot Message -->
          <div v-else class="max-w-[85%] px-4 py-3 rounded-2xl rounded-tl-sm bg-white dark:bg-gray-800 border border-[var(--color-border)] shadow-sm">
            <p class="text-sm text-[var(--color-text-primary)] whitespace-pre-wrap">{{ msg.content }}</p>
            
            <!-- Create Ticket CTA -->
            <div v-if="msg.canCreateTicket" class="mt-3 pt-3 border-t border-[var(--color-border)]">
              <button 
                @click="showTicketForm = true"
                class="w-full py-2 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
              >
                🎫 {{ $t('staffChatbot.createTicket') }}
              </button>
            </div>
          </div>
        </div>
        
        <!-- Typing Indicator -->
        <div v-if="isLoading" class="flex justify-start">
          <div class="px-4 py-3 rounded-2xl rounded-tl-sm bg-gray-100 dark:bg-gray-800">
            <div class="flex gap-1">
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="p-4 border-t border-[var(--color-border)] bg-[var(--color-bg-primary)]">
        <form @submit.prevent="sendMessage" class="flex gap-2">
          <input
            v-model="inputText"
            type="text"
            :placeholder="$t('staffChatbot.placeholder')"
            :disabled="isLoading"
            class="flex-1 px-4 py-3 rounded-xl bg-[var(--color-bg-secondary)] border border-[var(--color-border)] text-[var(--color-text-primary)] text-sm focus:ring-2 focus:ring-amber-500/50 focus:border-amber-400"
          />
          <button 
            type="submit" 
            :disabled="!inputText.trim() || isLoading"
            class="px-5 py-3 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-xl transition-colors"
          >
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
      </div>
    </div>
    
    <!-- Create Maintenance Ticket Modal -->
    <Teleport to="body">
      <div v-if="showTicketForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-[var(--color-bg-primary)] rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-[var(--color-border)]">
            <h2 class="text-lg font-bold text-[var(--color-text-primary)]">
              🎫 {{ $t('staffChatbot.createMaintenanceTicket') }}
            </h2>
            <p class="text-sm text-[var(--color-text-muted)] mt-1">
              {{ $t('staffChatbot.ticketWillBeRouted') }}
            </p>
          </div>
          
          <form @submit.prevent="createTicket" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">
                {{ $t('tickets.subject') }} *
              </label>
              <input 
                v-model="ticketForm.subject"
                type="text"
                required
                class="w-full px-4 py-2 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border)] text-[var(--color-text-primary)]"
                :placeholder="suggestedSubject || $t('staffChatbot.subjectPlaceholder')"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">
                {{ $t('tickets.description') }} *
              </label>
              <textarea 
                v-model="ticketForm.description"
                rows="4"
                required
                class="w-full px-4 py-2 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border)] text-[var(--color-text-primary)] resize-none"
                :placeholder="$t('staffChatbot.descriptionPlaceholder')"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">
                {{ $t('tickets.priority') }}
              </label>
              <select 
                v-model="ticketForm.priority"
                class="w-full px-4 py-2 rounded-lg bg-[var(--color-bg-secondary)] border border-[var(--color-border)] text-[var(--color-text-primary)]"
              >
                <option value="low">{{ $t('priority.low') }}</option>
                <option value="medium">{{ $t('priority.medium') }}</option>
                <option value="high">{{ $t('priority.high') }}</option>
                <option value="urgent">{{ $t('priority.urgent') }}</option>
              </select>
            </div>
            
            <div class="flex gap-3 pt-4">
              <button 
                type="button" 
                @click="showTicketForm = false"
                class="flex-1 py-2 px-4 rounded-lg border border-[var(--color-border)] text-[var(--color-text-primary)] hover:bg-[var(--color-bg-tertiary)]"
              >
                {{ $t('common.cancel') }}
              </button>
              <button 
                type="submit"
                :disabled="creatingTicket"
                class="flex-1 py-2 px-4 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-medium disabled:opacity-50"
              >
                {{ creatingTicket ? $t('common.loading') : $t('staffChatbot.submitTicket') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()

interface ChatMessage {
  id: number
  role: 'user' | 'bot'
  content: string
  canCreateTicket?: boolean
}

const inputText = ref('')
const messages = ref<ChatMessage[]>([])
const isLoading = ref(false)
const messagesContainer = ref<HTMLElement>()
const conversationId = ref<string | null>(null)
const showTicketForm = ref(false)
const creatingTicket = ref(false)
const suggestedSubject = ref('')

const ticketForm = ref({
  subject: '',
  description: '',
  priority: 'medium',
})

// Welcome message on mount
onMounted(() => {
  messages.value.push({
    id: Date.now(),
    role: 'bot',
    content: t('staffChatbot.welcome'),
  })
})

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

const sendMessage = async () => {
  const text = inputText.value.trim()
  if (!text || isLoading.value) return
  
  messages.value.push({ id: Date.now(), role: 'user', content: text })
  inputText.value = ''
  isLoading.value = true
  scrollToBottom()
  
  try {
    const response = await $fetch<{
      reply: string
      conversation_id?: string
      can_create_ticket?: boolean
      suggested_subject?: string
    }>(`${config.public.apiBase}/staff/chatbot/chat`, {
      method: 'POST',
      headers: { 
        Authorization: `Bearer ${token.value}`,
        'Accept-Language': locale.value,
      },
      body: { 
        message: text, 
        conversation_id: conversationId.value,
      },
    })
    
    if (response.conversation_id) {
      conversationId.value = response.conversation_id
    }
    
    if (response.suggested_subject) {
      suggestedSubject.value = response.suggested_subject
      ticketForm.value.subject = response.suggested_subject
    }
    
    messages.value.push({
      id: Date.now() + 1,
      role: 'bot',
      content: response.reply,
      canCreateTicket: response.can_create_ticket,
    })
  } catch (err) {
    messages.value.push({
      id: Date.now() + 1,
      role: 'bot',
      content: t('staffChatbot.error'),
    })
  } finally {
    isLoading.value = false
    scrollToBottom()
  }
}

const createTicket = async () => {
  if (!ticketForm.value.subject || !ticketForm.value.description) return
  
  creatingTicket.value = true
  
  try {
    const response = await $fetch<{ ticket: { id: number }; message: string }>(
      `${config.public.apiBase}/staff/chatbot/ticket`,
      {
        method: 'POST',
        headers: { 
          Authorization: `Bearer ${token.value}`,
          'Accept-Language': locale.value,
        },
        body: ticketForm.value,
      }
    )
    
    showTicketForm.value = false
    ticketForm.value = { subject: '', description: '', priority: 'medium' }
    
    messages.value.push({
      id: Date.now(),
      role: 'bot',
      content: `✅ ${t('staffChatbot.ticketCreated')} #${response.ticket.id}\n\n${t('staffChatbot.ticketRoutedToMaintenance')}`,
    })
    scrollToBottom()
  } catch (err) {
    alert(t('staffChatbot.ticketError'))
  } finally {
    creatingTicket.value = false
  }
}

const resetChat = () => {
  messages.value = [{
    id: Date.now(),
    role: 'bot',
    content: t('staffChatbot.welcome'),
  }]
  conversationId.value = null
  suggestedSubject.value = ''
}
</script>
