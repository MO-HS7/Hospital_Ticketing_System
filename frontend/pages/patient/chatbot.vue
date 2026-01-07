<template>
  <NuxtLayout name="patient">
    <div class="h-[calc(100vh-140px)] max-w-4xl mx-auto flex flex-col bg-[var(--color-bg-primary)] rounded-3xl border border-[var(--color-border)] shadow-xl overflow-hidden relative">
      
      <!-- Top Bar / Header -->
      <div class="px-6 py-4 border-b border-[var(--color-border)] bg-[var(--color-bg-secondary)]/80 backdrop-blur-md flex items-center justify-between shrink-0 z-10">
        <div class="flex items-center gap-4">
          <div class="relative">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
            </div>
            <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500 border-2 border-white dark:border-gray-800"></span>
            </span>
          </div>
          <div>
            <h1 class="font-bold text-lg text-[var(--color-text-primary)] leading-tight">
              {{ $t('chatbot.title') }}
            </h1>
            <p class="text-xs font-medium text-primary-600 dark:text-primary-400">
              {{ $t('chatbot.aiPowered') }}
            </p>
          </div>
        </div>
        
        <button 
          @click="handleNewChat" 
          class="group flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-[var(--color-border)] hover:border-primary-300 transition-all shadow-sm"
        >
          <svg class="w-4 h-4 text-[var(--color-text-muted)] group-hover:text-primary-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span class="text-sm font-medium text-[var(--color-text-primary)]">
            {{ $t('chatbot.newChat') }}
          </span>
        </button>
      </div>

      <!-- Messages Area -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-6 scroll-smooth bg-[url('/pattern.svg')] bg-fixed bg-opacity-5">
        
        <!-- Disclaimer -->
        <div class="flex justify-center">
          <div class="px-4 py-2 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 flex items-center gap-2 max-w-md mx-auto">
             <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
             </svg>
             <span class="text-xs font-medium text-amber-900 dark:text-amber-200 text-center">
               {{ $t('chatbot.emergencyCall') }}
             </span>
          </div>
        </div>

        <TransitionGroup name="fade">
          <div v-for="msg in messages" :key="msg.id" class="w-full">
            
            <!-- User Bubble -->
            <div v-if="msg.role === 'user'" class="flex justify-end mb-2">
              <div class="flex flex-col items-end max-w-[80%]">
                <div class="px-5 py-3.5 rounded-2xl rounded-tr-sm bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-md">
                  <p class="text-[15px] leading-relaxed">{{ msg.content }}</p>
                </div>
              </div>
            </div>

            <!-- Bot Bubble -->
            <div v-else class="flex gap-4 mb-2">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shrink-0 shadow-sm border border-white dark:border-gray-600 mt-1">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              
              <div class="flex flex-col gap-2 max-w-[85%]">
                <!-- Text Content -->
                <div class="px-5 py-4 rounded-2xl rounded-tl-sm bg-white dark:bg-gray-800 border border-[var(--color-border)] shadow-sm">
                  <p class="text-[15px] text-[var(--color-text-primary)] leading-relaxed whitespace-pre-wrap">{{ msg.content }}</p>
                </div>

                <!-- Departments/Doctors (Interactive) -->
                <div v-if="msg.departments?.length || msg.doctors?.length" class="flex flex-col gap-2 animate-slideUp">
                  <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider ml-1 rtl:mr-1">
                    {{ $t('chatbot.availableOptions') }}
                  </span>
                  
                  <!-- Department Chips -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                      v-for="dept in msg.departments"
                      :key="dept.id"
                      @click="selectDepartment(dept)"
                      class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 border border-[var(--color-border)] hover:border-primary-300 transition-all text-start group shadow-sm"
                    >
                      <span class="font-medium text-sm text-[var(--color-text-primary)] group-hover:text-primary-700 dark:group-hover:text-primary-300">
                        {{ getDeptName(dept) }}
                      </span>
                      <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </div>

                  <!-- Doctor Chips -->
                   <div class="grid grid-cols-1 gap-2">
                    <button
                      v-for="doc in msg.doctors"
                      :key="doc.id"
                      @click="selectDoctor(doc)"
                      class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-[var(--color-border)] hover:border-blue-300 transition-all text-start group shadow-sm"
                    >
                      <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 font-bold text-xs">Dr</div>
                      <span class="font-medium text-sm text-[var(--color-text-primary)]">
                        {{ doc.name }}
                      </span>
                    </button>
                  </div>
                </div>

                <!-- Step Indicator (Booking Flow) -->
                <div v-if="msg.stepNumber && msg.stepTotal" class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800">
                  <div class="flex gap-1">
                    <template v-for="step in msg.stepTotal" :key="step">
                      <div 
                        :class="[
                          'w-2 h-2 rounded-full transition-all',
                          step <= msg.stepNumber ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'
                        ]"
                      ></div>
                    </template>
                  </div>
                  <span class="text-xs font-medium text-primary-700 dark:text-primary-300">
                    {{ locale === 'ar' ? `الخطوة ${msg.stepNumber} من ${msg.stepTotal}` : `Step ${msg.stepNumber} of ${msg.stepTotal}` }}
                  </span>
                </div>

                <!-- Emergency Banner -->
                <div v-if="msg.isEmergency" class="mt-2 p-3 rounded-xl bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-red-200 dark:bg-red-800 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-bold text-red-800 dark:text-red-200">
                      {{ locale === 'ar' ? '⚠️ حالة طوارئ' : '⚠️ Emergency Detected' }}
                    </p>
                    <p class="text-sm text-red-700 dark:text-red-300">
                      {{ locale === 'ar' ? 'سيتم إعطاء أولوية لحالتك' : 'Your case will be prioritized' }}
                    </p>
                  </div>
                </div>

                <!-- Payment Options Buttons -->
                <div v-if="msg.paymentOptions?.length" class="mt-2 flex flex-col gap-2 animate-slideUp">
                  <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider ml-1 rtl:mr-1">
                    {{ locale === 'ar' ? 'طريقة الدفع' : 'Payment Method' }}
                  </span>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                      v-for="option in msg.paymentOptions"
                      :key="option.id"
                      @click="selectPaymentOption(option)"
                      class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-green-900/20 border border-[var(--color-border)] hover:border-green-400 transition-all text-start group shadow-sm"
                    >
                      <div class="flex items-center gap-2">
                        <div :class="[
                          'w-8 h-8 rounded-full flex items-center justify-center',
                          option.id === 'online' ? 'bg-green-100 dark:bg-green-900 text-green-600' : 'bg-amber-100 dark:bg-amber-900 text-amber-600'
                        ]">
                          <svg v-if="option.id === 'online'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <span class="font-medium text-sm text-[var(--color-text-primary)]">{{ option.label }}</span>
                      </div>
                      <svg class="w-4 h-4 text-gray-400 group-hover:text-green-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Quick Replies -->
                <div v-if="msg.quickReplies?.length" class="mt-2 flex flex-wrap gap-2">
                  <button
                    v-for="reply in msg.quickReplies"
                    :key="reply"
                    @click="sendMessage(reply)"
                    class="px-4 py-2 rounded-full bg-white dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 border border-[var(--color-border)] hover:border-primary-300 text-sm font-medium text-[var(--color-text-primary)] transition-all"
                  >
                    {{ reply }}
                  </button>
                </div>

                <!-- Ticket Created Success -->
                <div v-if="msg.ticketId" class="mt-2 p-4 rounded-xl bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                  </div>
                  <div>
                    <p class="font-bold text-green-800 dark:text-green-300">{{ $t('chatbot.ticketCreated') }}</p>
                    <p class="text-sm text-green-700 dark:text-green-400">ID: #{{ msg.ticketId }}</p>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </TransitionGroup>

        <!-- Typing Indicator -->
         <div v-if="isLoading" class="flex gap-4 animate-pulse">
            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="px-5 py-4 rounded-2xl rounded-tl-sm bg-gray-100 dark:bg-gray-800 w-32">
              <div class="flex gap-1.5 justify-center">
                <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce"></div>
                <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce delay-100"></div>
                <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce delay-200"></div>
              </div>
            </div>
         </div>
      </div>

      <!-- Footer / Input -->
      <div class="p-4 bg-[var(--color-bg-primary)] border-t border-[var(--color-border)] shrink-0 z-10">
        
        <!-- Patient Info Form (Step 3) -->
        <div v-if="showPatientForm" class="max-w-4xl mx-auto space-y-4">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-primary-600 font-bold text-xs">3</div>
            <span class="font-semibold text-[var(--color-text-primary)]">
              {{ locale === 'ar' ? 'معلومات المريض' : 'Patient Information' }}
            </span>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-[var(--color-text-muted)] mb-1">
                {{ locale === 'ar' ? 'العمر' : 'Age' }} *
              </label>
              <input 
                v-model="patientForm.age"
                type="number"
                min="1"
                max="120"
                class="w-full px-4 py-3 rounded-xl bg-[var(--color-bg-secondary)] border border-[var(--color-border)] text-[var(--color-text-primary)] focus:ring-2 focus:ring-primary-500/50 focus:border-primary-400"
                :placeholder="locale === 'ar' ? 'العمر' : 'Age'"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-[var(--color-text-muted)] mb-1">
                {{ locale === 'ar' ? 'الجنس' : 'Gender' }} *
              </label>
              <select 
                v-model="patientForm.gender"
                class="w-full px-4 py-3 rounded-xl bg-[var(--color-bg-secondary)] border border-[var(--color-border)] text-[var(--color-text-primary)] focus:ring-2 focus:ring-primary-500/50 focus:border-primary-400"
              >
                <option value="">{{ locale === 'ar' ? 'اختر...' : 'Select...' }}</option>
                <option value="male">{{ locale === 'ar' ? 'ذكر' : 'Male' }}</option>
                <option value="female">{{ locale === 'ar' ? 'أنثى' : 'Female' }}</option>
              </select>
            </div>
          </div>
          
          <button 
            @click="submitPatientInfo"
            :disabled="!patientForm.age || !patientForm.gender || isLoading"
            class="w-full py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            {{ locale === 'ar' ? 'متابعة' : 'Continue' }}
          </button>
        </div>
        
        <!-- Regular Text Input (for medical chat) -->
        <form v-else @submit.prevent="handleSend" class="relative max-w-4xl mx-auto flex items-end gap-2">
          <div class="relative flex-1 bg-[var(--color-bg-secondary)] rounded-2xl focus-within:ring-2 focus-within:ring-primary-500/50 transition-all border border-[var(--color-border)]">
            <textarea
              ref="inputRef"
              v-model="inputText"
              rows="1"
              class="w-full bg-transparent border-0 focus:ring-0 p-4 max-h-32 resize-none text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] leading-relaxed"
              :placeholder="locale === 'ar' ? 'اكتب رسالتك هنا... (Shift+Enter لسطر جديد)' : 'Type your message here... (Shift+Enter for new line)'"
              @keydown.enter.exact.prevent="handleSend"
            ></textarea>
          </div>
          <button 
            type="submit"
            :disabled="!inputText.trim() || isLoading"
            class="p-4 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5"
          >
            <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
        <p class="text-center text-[10px] text-[var(--color-text-muted)] mt-2 opacity-60">
          {{ $t('chatbot.aiDisclaimer') }}
        </p>
      </div>

    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
// Logic remains identical, but ensuring component structure is solid
definePageMeta({ layout: false, middleware: ['auth'] })

// Interfaces
interface Department {
  id: string
  name?: string
  name_en?: string
  name_ar?: string
  slug?: string
}

interface Doctor {
  id: number
  name: string
}

interface ChatMessage {
  id: number
  role: 'user' | 'bot'
  content: string
  departments?: Department[]
  doctors?: Doctor[]
  quickReplies?: string[]
  ticketId?: number
  stepNumber?: number
  stepTotal?: number
  paymentOptions?: { id: string; label: string }[]
  isEmergency?: boolean
  action?: string
  prefilledData?: { name?: string; phone?: string }
}

interface ChatResponse {
  session_id: string
  messages: string[]
  quick_replies?: string[]
  suggestions?: Department[]
  doctors?: Doctor[]
  current_step?: string
  action?: string
  ticket_id?: number
  step_number?: number
  step_total?: number
  payment_options?: { id: string; label: string }[]
  is_emergency?: boolean
  prefilled?: { name?: string; phone?: string }
}

const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()

const inputText = ref('')
const inputRef = ref<HTMLTextAreaElement>()
const isLoading = ref(false)
const messagesContainer = ref<HTMLElement>()
const sessionId = ref<string | null>(null)
const messages = ref<ChatMessage[]>([])
const hasWelcomed = ref(false)
const currentStep = ref<string>('')
const stepNumber = ref(0)
const isEmergency = ref(false)
const showPatientForm = ref(false)
const patientForm = ref({
  age: '',
  gender: '',
})

const getDeptName = (dept: Department): string => {
  if (locale.value === 'ar') {
    return dept.name_ar || dept.name || dept.name_en || ''
  }
  return dept.name_en || dept.name || dept.name_ar || ''
}

const showWelcome = () => {
  if (hasWelcomed.value || messages.value.length > 0) return
  hasWelcomed.value = true
  messages.value.push({
    id: Date.now(),
    role: 'bot',
    content: t('chatbot.welcome'),
    quickReplies: []
  })
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: 'smooth'
      })
    }
  })
}

const handleSend = () => {
  const text = inputText.value.trim()
  if (!text || isLoading.value) return
  sendMessage(text)
}

const sendMessage = async (text: string) => {
  if (!text || isLoading.value) return
  
  messages.value.push({ id: Date.now(), role: 'user', content: text })
  inputText.value = ''
  isLoading.value = true
  scrollToBottom()

  try {
    const response = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/message`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { message: text, session_id: sessionId.value, locale: locale.value }
    })

    if (response.session_id) {
      sessionId.value = response.session_id
      localStorage.setItem('chatbot_session_id', response.session_id)
    }

    const botMsgs = response.messages || []
    
    // Update step tracking state
    if (response.step_number) {
      stepNumber.value = response.step_number
      currentStep.value = response.current_step || ''
    }
    if (response.is_emergency) {
      isEmergency.value = true
    }
    
    // Handle patient info form step
    if (response.action === 'collect_patient_info') {
      showPatientForm.value = true
    } else {
      showPatientForm.value = false
    }
    
    botMsgs.forEach((content, i) => {
      const isLast = i === botMsgs.length - 1
      messages.value.push({
        id: Date.now() + i + 1,
        role: 'bot',
        content,
        departments: isLast ? response.suggestions : undefined,
        doctors: isLast ? response.doctors : undefined,
        quickReplies: isLast ? response.quick_replies : undefined,
        ticketId: isLast ? response.ticket_id : undefined,
        stepNumber: isLast ? response.step_number : undefined,
        stepTotal: isLast ? response.step_total : undefined,
        paymentOptions: isLast ? response.payment_options : undefined,
        isEmergency: isLast ? response.is_emergency : undefined,
        action: isLast ? response.action : undefined,
        prefilledData: isLast ? response.prefilled : undefined,
      })
    })
  } catch (err) {
    console.error(err)
    messages.value.push({
      id: Date.now() + 1,
      role: 'bot',
      content: t('chatbot.connectionError')
    })
  } finally {
    isLoading.value = false
    scrollToBottom()
    nextTick(() => inputRef.value?.focus())
  }
}

const selectDepartment = (dept: Department) => {
  messages.value.push({ id: Date.now(), role: 'user', content: getDeptName(dept) })
  sendMessageToApi(String(dept.id))
}

const selectDoctor = (doc: Doctor) => {
  const displayName = (locale.value === 'ar' ? 'د. ' : 'Dr. ') + doc.name
  messages.value.push({ id: Date.now(), role: 'user', content: displayName })
  sendMessageToApi(String(doc.id))
}

const selectPaymentOption = (option: { id: string; label: string }) => {
  messages.value.push({ id: Date.now(), role: 'user', content: option.label })
  sendMessageToApi(option.id)
}

const submitPatientInfo = () => {
  const age = patientForm.value.age
  const gender = patientForm.value.gender
  
  if (!age || !gender) {
    return
  }
  
  // Send as JSON for structured parsing
  const payload = JSON.stringify({
    age: parseInt(age),
    gender: gender,
  })
  
  const displayMsg = locale.value === 'ar' 
    ? `العمر: ${age}, الجنس: ${gender === 'male' ? 'ذكر' : 'أنثى'}`
    : `Age: ${age}, Gender: ${gender}`
  
  messages.value.push({ id: Date.now(), role: 'user', content: displayMsg })
  
  // Reset form
  patientForm.value = { age: '', gender: '' }
  showPatientForm.value = false
  
  sendMessageToApi(payload)
}

const sendMessageToApi = async (text: string) => {
  if (isLoading.value) return
  isLoading.value = true
  scrollToBottom()

  try {
    const response = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/message`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { message: text, session_id: sessionId.value, locale: locale.value }
    })
    
    if (response.session_id) {
        sessionId.value = response.session_id
        localStorage.setItem('chatbot_session_id', response.session_id)
    }

    const botMsgs = response.messages || []
    
    // Update step tracking state
    if (response.step_number) {
      stepNumber.value = response.step_number
      currentStep.value = response.current_step || ''
    }
    if (response.is_emergency) {
      isEmergency.value = true
    }
    
    // Handle patient info form step
    if (response.action === 'collect_patient_info') {
      showPatientForm.value = true
    } else {
      showPatientForm.value = false
    }
    
    botMsgs.forEach((content, i) => {
      const isLast = i === botMsgs.length - 1
      messages.value.push({
        id: Date.now() + i + 1,
        role: 'bot',
        content,
        departments: isLast ? response.suggestions : undefined,
        doctors: isLast ? response.doctors : undefined,
        quickReplies: isLast ? response.quick_replies : undefined,
        ticketId: isLast ? response.ticket_id : undefined,
        stepNumber: isLast ? response.step_number : undefined,
        stepTotal: isLast ? response.step_total : undefined,
        paymentOptions: isLast ? response.payment_options : undefined,
        isEmergency: isLast ? response.is_emergency : undefined,
        action: isLast ? response.action : undefined,
        prefilledData: isLast ? response.prefilled : undefined,
      })
    })
  } catch (err) {
    messages.value.push({ id: Date.now() + 1, role: 'bot', content: 'Error occurred.' })
  } finally {
    isLoading.value = false
    scrollToBottom()
  }
}

const handleNewChat = async () => {
  if (sessionId.value) {
    try {
      await $fetch(`${config.public.apiBase}/chatbot/reset`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` },
        body: { session_id: sessionId.value }
      })
    } catch (e) {}
  }
  
  sessionId.value = null
  localStorage.removeItem('chatbot_session_id')
  hasWelcomed.value = false
  messages.value = []
  showWelcome()
}

// Logic to load existing session
onMounted(async () => {
    const saved = localStorage.getItem('chatbot_session_id')
    if (saved) {
        sessionId.value = saved
        // Try load session
        try {
            const res = await $fetch<{messages: any[]}>(`${config.public.apiBase}/chatbot/session`, {
                params: { session_id: saved },
                headers: { Authorization: `Bearer ${token.value}` }
            })
            if (res.messages && res.messages.length) {
                hasWelcomed.value = true
                messages.value = res.messages.map((m, i) => ({
                    id: i,
                    role: m.role,
                    content: m.content
                }))
            }
        } catch(e) {
            console.log('Session expired or invalid')
            sessionId.value = null
            localStorage.removeItem('chatbot_session_id')
        }
    }
    
    if (messages.value.length === 0) {
        showWelcome()
    }
    scrollToBottom()
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.animate-slideUp { animation: slideUp 0.3s ease-out forwards; }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
