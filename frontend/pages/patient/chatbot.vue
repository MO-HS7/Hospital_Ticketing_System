<template>
  <NuxtLayout name="patient">
    <!-- Two-Panel Layout Container -->
    <div class="h-[calc(100vh-120px)] max-w-7xl mx-auto flex gap-0 lg:gap-4">
      
      <!-- Main Chat Panel -->
      <div class="flex-1 flex flex-col bg-white dark:bg-slate-900 lg:rounded-2xl border border-slate-200 dark:border-white/10 shadow-xl overflow-hidden relative">
      
      <!-- Compact Sticky Header -->
      <div class="px-4 py-3 border-b border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md flex items-center justify-between shrink-0 z-10 sticky top-0">
        <div class="flex items-center gap-3">
          <!-- Avatar with Status -->
          <div class="relative">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-md shadow-primary-500/20">
              <Icon name="robot" size="md" class="text-white" />
            </div>
            <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
              <span v-if="!isLoading" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span :class="isLoading ? 'bg-amber-500' : 'bg-green-500'" class="relative inline-flex rounded-full h-3 w-3 border-2 border-white dark:border-slate-900"></span>
            </span>
          </div>
          
          <!-- Title + Status -->
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-sm text-slate-900 dark:text-white">Masar</span>
              <span class="text-xs text-slate-500 dark:text-slate-400">•</span>
              <span :class="isLoading ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'" class="text-xs font-medium">
                {{ isLoading ? $t('chatbot.thinking') : $t('chatbot.online') }}
              </span>
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ $t('chatbot.aiPowered') }}</p>
          </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center gap-2">
          <!-- Mobile Context Toggle -->
          <button 
            v-if="isInBookingFlow"
            @click="showMobileContext = true"
            class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            :aria-label="$t('chatbot.viewSummary')"
          >
            <Icon name="clipboard-list" size="sm" />
          </button>
          
          <!-- Clear Chat -->
          <button 
            @click="handleNewChat" 
            class="p-2 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            :aria-label="$t('chatbot.newChat')"
          >
            <Icon name="trash" size="sm" />
          </button>
        </div>
      </div>
      
      <!-- Step Progress Bar (visible during 5-step booking flow) -->
      <div v-if="isInBookingFlow" class="px-6 py-3 border-b border-[var(--color-border)] bg-[var(--color-bg-secondary)]/50 shrink-0">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wide">
            {{ $t('chatbot.bookingProgress') }}
          </span>
          <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
            {{ stepNumber }}/5
          </span>
        </div>
        <div class="flex items-center gap-1" :class="{ 'flex-row-reverse': $i18n.locale === 'ar' }">
          <template v-for="step in 5" :key="step">
            <div 
              class="flex-1 h-2 rounded-full transition-all duration-300"
              :class="[
                step <= stepNumber 
                  ? 'bg-primary-500 shadow-sm shadow-primary-500/30' 
                  : 'bg-gray-200 dark:bg-gray-700',
                step === stepNumber ? 'ring-2 ring-primary-300 ring-offset-1' : ''
              ]"
            ></div>
            <div v-if="step < 5" class="w-1"></div>
          </template>
        </div>
        <div class="flex justify-between mt-2 text-[10px] text-[var(--color-text-muted)]" :class="{ 'flex-row-reverse': $i18n.locale === 'ar' }">
          <span :class="{ 'text-primary-600 font-semibold': stepNumber === 1 }">{{ $t('chatbot.steps.department') }}</span>
          <span :class="{ 'text-primary-600 font-semibold': stepNumber === 2 }">{{ $t('chatbot.steps.doctor') }}</span>
          <span :class="{ 'text-primary-600 font-semibold': stepNumber === 3 }">{{ $t('chatbot.steps.info') }}</span>
          <span :class="{ 'text-primary-600 font-semibold': stepNumber === 4 }">{{ $t('chatbot.steps.payment') }}</span>
          <span :class="{ 'text-primary-600 font-semibold': stepNumber === 5 }">{{ $t('chatbot.steps.confirm') }}</span>
        </div>
      </div>

      <!-- Messages Area -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-4 scroll-smooth bg-slate-50/50 dark:bg-slate-950/50">
        
        <!-- Disclaimer -->
        <div class="flex justify-center">
          <div class="px-4 py-2 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 flex items-center gap-2 max-w-md mx-auto">
             <Icon name="exclamation-triangle" size="sm" class="text-amber-600 shrink-0" />
             <span class="text-xs font-medium text-amber-900 dark:text-amber-200 text-center">
               {{ $t('chatbot.emergencyCall') }}
             </span>
          </div>
        </div>

        <!-- DEV Debug Banner (shows last response debug info) - ONLY in development -->
        <div v-if="debugEnabled && lastDebug" class="mx-auto max-w-2xl px-3 py-1.5 rounded-lg bg-gray-800 text-green-400 font-mono text-[10px] overflow-x-auto whitespace-nowrap border border-green-700">
          <span class="text-green-300">DEBUG:</span>
          intent=<span class="text-yellow-300">{{ lastDebug.intent || 'N/A' }}</span> |
          handler=<span class="text-cyan-300">{{ lastDebug.handler || 'N/A' }}</span> |
          provider=<span class="text-pink-300">{{ lastDebug.provider || 'N/A' }}</span> |
          ui_type=<span class="text-orange-300">{{ lastDebug.ui_type || 'N/A' }}</span> |
          mode=<span class="text-blue-300">{{ lastDebug.mode || 'N/A' }}</span> |
          step=<span class="text-purple-300">{{ lastDebug.step || 'N/A' }}</span> |
          req=<span class="text-gray-400">{{ lastDebug.request_id?.slice(-8) || 'N/A' }}</span>
        </div>

        <TransitionGroup name="fade">
          <div v-for="msg in messages" :key="msg.id" class="w-full">
            
            <!-- User Bubble -->
            <div v-if="msg.role === 'user'" class="flex justify-end mb-2">
              <div class="flex flex-col items-end max-w-[80%]">
                <div class="px-5 py-3.5 rounded-2xl rounded-tr-sm bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-md">
                  <p class="text-[15px] leading-relaxed whitespace-pre-wrap">{{ msg.content }}</p>
                </div>
              </div>
            </div>

            <!-- Bot Bubble -->
            <div v-else class="flex gap-3 mb-2">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900/50 dark:to-primary-800/50 flex items-center justify-center shrink-0 shadow-sm border border-primary-200 dark:border-primary-700 mt-1">
                <Icon name="robot" size="xs" class="text-primary-600 dark:text-primary-400" />
              </div>
              
              <div class="flex flex-col gap-2 max-w-[85%]">
                <!-- Text Content -->
                <div class="px-5 py-4 rounded-2xl rounded-tl-sm bg-white dark:bg-gray-800 border border-[var(--color-border)] shadow-sm">
                  <p class="text-[15px] text-[var(--color-text-primary)] leading-relaxed whitespace-pre-wrap">{{ msg.content }}</p>
                </div>

                <!-- Departments/Doctors (Interactive) - ONLY when ui.type matches -->
                <div v-if="msg.uiType === 'department_cards' && msg.departments?.length" class="flex flex-col gap-2 animate-slideUp">
                  <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider ml-1 rtl:mr-1">
                    {{ $t('chatbot.selectDepartment') }}
                  </span>
                  
                  <!-- Department Cards -->
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
                </div>
                
                <!-- Doctor Cards - ONLY when ui.type matches -->
                <div v-if="msg.uiType === 'doctor_cards' && msg.doctors?.length" class="flex flex-col gap-2 animate-slideUp">
                  <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider ml-1 rtl:mr-1">
                    {{ $t('chatbot.selectDoctor') }}
                  </span>
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

                <!-- Slot Cards - Time slot selection -->
                <div v-if="msg.uiType === 'slot_cards' && msg.slots?.length" class="flex flex-col gap-2 animate-slideUp">
                  <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider ml-1 rtl:mr-1">
                    {{ locale === 'ar' ? 'اختر الموعد' : 'Select Time Slot' }}
                  </span>
                  <div v-for="day in msg.slots" :key="day.date" class="mb-3">
                    <p class="text-sm font-medium text-[var(--color-text-primary)] mb-2">
                      {{ day.date_formatted || day.date }}
                    </p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                      <button
                        v-for="slot in day.slots"
                        :key="slot.start || slot"
                        @click="selectSlot(day.date, slot)"
                        class="px-3 py-2 rounded-lg bg-white dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-green-900/20 border border-[var(--color-border)] hover:border-green-400 transition-all text-center text-sm font-medium"
                      >
                        {{ typeof slot === 'string' ? slot : slot.start_time || slot.start }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Patient Form UI (from backend) -->
                <div v-if="msg.uiType === 'patient_form' && msg.fields?.length" class="flex flex-col gap-3 animate-slideUp">
                  <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
                    {{ locale === 'ar' ? 'بيانات المريض' : 'Patient Information' }}
                  </span>
                  <div class="grid grid-cols-2 gap-3">
                    <div v-for="field in msg.fields" :key="field.name" :class="field.type === 'textarea' ? 'col-span-2' : ''">
                      <label class="block text-xs font-medium text-[var(--color-text-muted)] mb-1">
                        {{ locale === 'ar' ? field.label_ar : field.label_en }}
                        <span v-if="field.required" class="text-red-500">*</span>
                      </label>
                      <input
                        v-if="field.type !== 'select' && field.type !== 'textarea'"
                        v-model="(patientForm as Record<string, string>)[field.name]"
                        :type="field.type"
                        class="w-full h-11 px-4 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400"
                      />
                      <select
                        v-if="field.type === 'select'"
                        v-model="(patientForm as Record<string, string>)[field.name]"
                        class="w-full h-11 px-4 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white"
                      >
                        <option value="">{{ locale === 'ar' ? 'اختر...' : 'Select...' }}</option>
                        <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                          {{ locale === 'ar' ? opt.label_ar : opt.label_en }}
                        </option>
                      </select>
                      <textarea
                        v-if="field.type === 'textarea'"
                        v-model="(patientForm as Record<string, string>)[field.name]"
                        rows="3"
                        class="w-full px-4 py-2 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white"
                      ></textarea>
                    </div>
                  </div>
                  <button
                    @click="submitPatientForm"
                    :disabled="isLoading"
                    class="w-full py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold disabled:opacity-50 transition-all"
                  >
                    {{ locale === 'ar' ? 'متابعة' : 'Continue' }}
                  </button>
                </div>

                <!-- Confirmation UI -->
                <div v-if="msg.uiType === 'confirmation' && msg.summary" class="flex flex-col gap-3 animate-slideUp">
                  <div class="p-4 rounded-xl bg-gradient-to-r from-primary-50 to-green-50 dark:from-primary-900/20 dark:to-green-900/20 border border-primary-200 dark:border-primary-700">
                    <h4 class="font-semibold text-[var(--color-text-primary)] mb-3">
                      {{ locale === 'ar' ? 'ملخص الحجز' : 'Booking Summary' }}
                    </h4>
                    <div class="space-y-2 text-sm">
                      <p><span class="text-[var(--color-text-muted)]">{{ locale === 'ar' ? 'القسم:' : 'Department:' }}</span> {{ msg.summary.department }}</p>
                      <p><span class="text-[var(--color-text-muted)]">{{ locale === 'ar' ? 'الطبيب:' : 'Doctor:' }}</span> {{ msg.summary.doctor }}</p>
                      <p><span class="text-[var(--color-text-muted)]">{{ locale === 'ar' ? 'التاريخ:' : 'Date:' }}</span> {{ msg.summary.date }}</p>
                      <p><span class="text-[var(--color-text-muted)]">{{ locale === 'ar' ? 'الوقت:' : 'Time:' }}</span> {{ msg.summary.time }}</p>
                      <p><span class="text-[var(--color-text-muted)]">{{ locale === 'ar' ? 'المريض:' : 'Patient:' }}</span> {{ msg.summary.patient_name }}</p>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="confirmBooking"
                      :disabled="isLoading"
                      class="flex-1 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold disabled:opacity-50 transition-all"
                    >
                      {{ locale === 'ar' ? 'تأكيد الحجز' : 'Confirm Booking' }}
                    </button>
                    <button
                      @click="handleCancel"
                      :disabled="isLoading"
                      class="px-6 py-3 rounded-xl bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-[var(--color-text-primary)] font-medium transition-all"
                    >
                      {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                    </button>
                  </div>
                </div>

                <!-- Booking Success UI -->
                <div v-if="msg.uiType === 'booking_success'" class="mt-2 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-300 dark:border-green-700">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-green-200 dark:bg-green-800 flex items-center justify-center">
                      <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <div>
                      <p class="font-bold text-green-800 dark:text-green-200">
                        {{ locale === 'ar' ? '✅ تم الحجز بنجاح' : '✅ Booking Confirmed' }}
                      </p>
                      <p class="text-sm text-green-700 dark:text-green-300">
                        {{ locale === 'ar' ? `رقم التذكرة: #${msg.ticketId}` : `Ticket #${msg.ticketId}` }}
                      </p>
                    </div>
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
                      @click="selectPayment(option.id)"
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

        <!-- Typing Indicator with "Masar is typing..." -->
         <div v-if="isLoading" class="flex gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900/50 dark:to-primary-800/50 flex items-center justify-center shrink-0 shadow-sm border border-primary-200 dark:border-primary-700">
              <Icon name="robot" size="xs" class="text-primary-600 dark:text-primary-400 animate-pulse" />
            </div>
            <div class="flex flex-col gap-1">
              <div class="px-5 py-4 rounded-2xl rounded-tl-sm bg-white dark:bg-gray-800 border border-[var(--color-border)] shadow-sm">
                <div class="flex items-center gap-2">
                  <div class="flex gap-1">
                    <div class="w-2 h-2 rounded-full bg-primary-400 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 rounded-full bg-primary-400 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 rounded-full bg-primary-400 animate-bounce" style="animation-delay: 300ms"></div>
                  </div>
                </div>
              </div>
              <span class="text-xs text-[var(--color-text-muted)] ms-2">
                {{ locale === 'ar' ? 'مسار يكتب...' : 'Masar is typing...' }}
              </span>
            </div>
         </div>
      </div>

      <!-- Footer / Input -->
      <div class="p-4 bg-[var(--color-bg-primary)] border-t border-[var(--color-border)] shrink-0 z-10">
        
        <!-- Flow Control Buttons (visible during booking flow) -->
        <div v-if="isInBookingFlow && !showPatientForm" class="flex gap-2 mb-3 max-w-4xl mx-auto" :class="{ 'flex-row-reverse': $i18n.locale === 'ar' }">
          <button
            @click="handleBack"
            :disabled="stepNumber <= 1 || isLoading"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all border"
            :class="[
              stepNumber <= 1 || isLoading
                ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-700'
                : 'bg-white dark:bg-gray-800 text-[var(--color-text-primary)] hover:bg-gray-50 dark:hover:bg-gray-700 border-[var(--color-border)] hover:border-primary-300 shadow-sm'
            ]"
          >
            <Icon name="chevron-left" size="sm" class="rtl:rotate-180" />
            {{ locale === 'ar' ? 'رجوع' : 'Back' }}
          </button>
          
          <button
            @click="handleCancel"
            :disabled="isLoading"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all border"
            :class="[
              isLoading
                ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-700'
                : 'bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 border-[var(--color-border)] hover:border-red-300 shadow-sm'
            ]"
          >
            <Icon name="x-mark" size="sm" />
            {{ locale === 'ar' ? 'إلغاء الحجز' : 'Cancel Booking' }}
          </button>
        </div>
        
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
                class="w-full h-11 px-4 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 shadow-sm"
                :placeholder="locale === 'ar' ? 'العمر' : 'Age'"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-[var(--color-text-muted)] mb-1">
                {{ locale === 'ar' ? 'الجنس' : 'Gender' }} *
              </label>
              <select 
                v-model="patientForm.gender"
                class="w-full h-11 px-4 rounded-xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 shadow-sm"
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
        
        <!-- Action Quick Reply Buttons (only show AFTER at least one bot message in QA mode) -->
        <div v-if="showActionBar" class="max-w-4xl mx-auto mb-3">
          <div class="flex items-center gap-2 mb-2 px-1">
            <svg class="w-4 h-4 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
            </svg>
            <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
              {{ locale === 'ar' ? 'قد يعجبك أيضاً' : 'You might also like' }}
            </span>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="item in actionQuickReplies"
              :key="item.action"
              @click="sendAction(item.action, item.label)"
              :disabled="isLoading"
              class="px-4 py-2 rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 hover:from-primary-100 hover:to-primary-200 dark:hover:from-primary-800/30 dark:hover:to-primary-700/30 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-700 font-medium text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md"
            >
              {{ item.label }}
            </button>
          </div>
        </div>
        
        <!-- Regular Text Input (ALWAYS visible unless patient form showing) -->
        <form v-if="!showPatientForm" @submit.prevent="handleSend" class="relative max-w-4xl mx-auto">
          <div class="flex items-end gap-2">
            <!-- Mic Button (Speech-to-Text) - visible on all screen sizes -->
            <button 
              type="button"
              @click="toggleMic"
              :disabled="!sttSupported || isLoading"
              :class="[
                'flex p-3 rounded-xl transition-all z-10',
                isListening 
                  ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 ring-2 ring-red-400 animate-pulse' 
                  : sttSupported && !isLoading
                    ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-primary-600'
                    : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
              ]"
              :aria-label="isListening ? (locale === 'ar' ? 'إيقاف التسجيل' : 'Stop recording') : (locale === 'ar' ? 'بدء التسجيل الصوتي' : 'Start voice input')"
              :title="!sttSupported ? (locale === 'ar' ? 'التعرف على الكلام غير مدعوم في هذا المتصفح. استخدم Chrome أو Edge.' : 'Speech-to-text not supported in this browser. Use Chrome/Edge.') : ''"
            >
              <svg v-if="isListening" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" rx="1" />
                <rect x="14" y="4" width="4" height="16" rx="1" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </button>
            
            <!-- Input -->
            <div class="relative flex-1 bg-white dark:bg-white/5 rounded-xl focus-within:ring-2 focus-within:ring-primary-500/20 transition-all border border-slate-200 dark:border-white/10 shadow-sm">
              <textarea
                ref="inputRef"
                v-model="inputText"
                rows="1"
                class="w-full h-11 bg-transparent border-0 focus:ring-0 px-4 py-2.5 max-h-24 resize-none text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 leading-relaxed text-sm"
                :placeholder="locale === 'ar' ? 'اكتب رسالتك هنا...' : 'Type your message...'"
                @keydown.enter.exact.prevent="handleSend"
              ></textarea>
            </div>
            
            <!-- Send -->
            <button 
              type="submit"
              :disabled="!inputText.trim() || isLoading"
              class="p-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md"
              :aria-label="$t('chatbot.send')"
            >
              <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </button>
          </div>
        </form>
        <p class="text-center text-[10px] text-slate-400 dark:text-slate-500 mt-2">
          {{ $t('chatbot.aiDisclaimer') }}
        </p>
      </div>

      </div><!-- End Main Chat Panel -->
      
      <!-- Context Panel Sidebar (Desktop only) -->
      <aside
        v-if="isInBookingFlow"
        class="hidden lg:flex w-80 shrink-0"
      >
        <PatientChatContextPanel
          :current-step="stepNumber"
          :department-name="selectedDepartmentName"
          :doctor-name="selectedDoctorName"
          :payment-method="selectedPaymentMethod"
          @cancel="handleCancel"
        />
      </aside>
      
    </div><!-- End Two-Panel Container -->
    
    <!-- Mobile Bottom Sheet -->
    <PatientChatBottomSheet
      v-model="showMobileContext"
      :current-step="stepNumber"
      :department-name="selectedDepartmentName"
      :doctor-name="selectedDoctorName"
      :payment-method="selectedPaymentMethod"
      @cancel="handleCancel"
    />
    
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
  uiType?: string  // UI type from backend response
  slots?: any[]    // Slot cards for time selection
  fields?: any[]   // Patient form fields from backend
  summary?: any    // Booking confirmation summary
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
  _ai_status?: string
  // New debug/routing fields from backend
  mode?: string
  intent?: string
  ui?: { type: string; items?: any[]; reset_booking?: boolean }
  debug?: { request_id?: string; provider?: string; intent?: string; ui_type?: string; handler?: string; mode?: string; step?: string }
  _intent?: string
  provider?: string
  handler?: string
  reply?: string           // Combined message text
  step?: string            // Current step (alias for current_step)
  request_id?: string      // Request ID for tracing
}

// Debug info type
interface DebugInfo {
  intent?: string
  handler?: string
  provider?: string
  ui_type?: string
  mode?: string
  step?: string
  request_id?: string
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
const aiStatus = ref<string>('') // AI status from API response
const showMobileContext = ref(false) // Mobile bottom sheet visibility
const selectedDepartmentId = ref<number | null>(null)
const selectedDoctorId = ref<number | null>(null)
const paymentMethod = ref<string | null>(null)

// Server-authoritative state (backend controls these)
const mode = ref<string>('qa') // 'qa' or 'booking'
const step = ref<string>('initial') // 'initial', 'department', 'doctor', etc.

const patientForm = reactive({
  full_name: '',
  phone: '',
  age: '',
  gender: '',
  national_id: '',
  symptoms: '',
})
// DEV: Debug info from last response
const lastDebug = ref<DebugInfo | null>(null)

// Debug mode: only enabled in development or via explicit flag
const debugEnabled = computed(() => {
  // Debug banner is OFF by default, even in development
  // Must be explicitly enabled via VITE_CHATBOT_DEBUG=true
  if (import.meta.env.VITE_CHATBOT_DEBUG === 'true') return true
  return false
})

// ============================================================
// SPEECH-TO-TEXT (STT) - Web Speech API
// ============================================================

// Check if Web Speech API is supported (client-only)
const sttSupported = computed(() => {
  if (typeof window === 'undefined') return false
  return !!((window as any).SpeechRecognition || (window as any).webkitSpeechRecognition)
})

// STT state
const isListening = ref(false)
let recognizer: any = null

/**
 * Toggle microphone: start/stop speech recognition.
 */
const toggleMic = () => {
  if (!sttSupported.value) return
  
  if (isListening.value) {
    // Stop listening
    stopListening()
  } else {
    // Start listening
    startListening()
  }
}

/**
 * Start speech recognition.
 */
const startListening = () => {
  if (typeof window === 'undefined') return
  
  const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
  if (!SpeechRecognition) return
  
  // Create new recognizer instance
  recognizer = new SpeechRecognition()
  
  // Configure
  recognizer.lang = locale.value === 'ar' ? 'ar-SA' : 'en-US'
  recognizer.interimResults = true  // Show live transcription
  recognizer.continuous = true      // Keep listening until stopped
  recognizer.maxAlternatives = 1
  
  // Handle results (interim and final)
  recognizer.onresult = (event: any) => {
    let interimTranscript = ''
    let finalTranscript = ''
    
    for (let i = event.resultIndex; i < event.results.length; i++) {
      const transcript = event.results[i][0].transcript
      if (event.results[i].isFinal) {
        finalTranscript += transcript + ' '
      } else {
        interimTranscript += transcript
      }
    }
    
    // Update input field with transcription
    // For final results, append to existing text
    // For interim, show live preview
    if (finalTranscript) {
      inputText.value = (inputText.value + finalTranscript).trim()
    } else if (interimTranscript) {
      // Show interim in a way that doesn't overwrite existing text
      // We'll just update if the field is mostly empty or show interim at end
      const existing = inputText.value.trim()
      if (!existing) {
        inputText.value = interimTranscript
      }
    }
  }
  
  // Handle errors
  recognizer.onerror = (event: any) => {
    console.error('[STT] Error:', event.error)
    isListening.value = false
    
    // Show user-friendly error messages
    let errorMsg = ''
    switch (event.error) {
      case 'not-allowed':
        errorMsg = locale.value === 'ar' 
          ? 'تم رفض إذن الميكروفون. يرجى السماح بالوصول.' 
          : 'Microphone permission denied. Please allow access.'
        break
      case 'no-speech':
        errorMsg = locale.value === 'ar' 
          ? 'لم يتم اكتشاف كلام. حاول مرة أخرى.' 
          : 'No speech detected. Please try again.'
        break
      case 'network':
        errorMsg = locale.value === 'ar' 
          ? 'خطأ في الشبكة. تحقق من اتصالك.' 
          : 'Network error. Check your connection.'
        break
      case 'aborted':
        // User cancelled, no message needed
        break
      default:
        errorMsg = locale.value === 'ar' 
          ? 'حدث خطأ في التعرف على الصوت.' 
          : 'Speech recognition error occurred.'
    }
    
    if (errorMsg) {
      // Add error as temporary bot message (optional)
      // For now, just log it - could show toast instead
      console.warn('[STT]', errorMsg)
    }
  }
  
  // Handle end (recognition stopped)
  recognizer.onend = () => {
    isListening.value = false
    recognizer = null
  }
  
  // Start listening
  try {
    recognizer.start()
    isListening.value = true
  } catch (err) {
    console.error('[STT] Failed to start:', err)
    isListening.value = false
  }
}

/**
 * Stop speech recognition.
 */
const stopListening = () => {
  if (recognizer) {
    try {
      recognizer.stop()
    } catch (err) {
      // Ignore InvalidStateError if already stopped
    }
  }
  isListening.value = false
}

// Cleanup on unmount
onUnmounted(() => {
  stopListening()
})

const getDeptName = (dept: Department): string => {
  if (locale.value === 'ar') {
    return dept.name_ar || dept.name || dept.name_en || ''
  }
  return dept.name_en || dept.name || dept.name_ar || ''
}

// Computed: Selected department name for context panel
const selectedDepartmentName = computed<string | null>(() => {
  const lastMsg = [...messages.value].reverse().find(m => m.departments?.length)
  if (selectedDepartmentId.value && lastMsg?.departments) {
    const dept = lastMsg.departments.find(d => Number(d.id) === selectedDepartmentId.value)
    return dept ? getDeptName(dept) : null
  }
  return null
})

// Computed: Selected doctor name for context panel
const selectedDoctorName = computed<string | null>(() => {
  const lastMsg = [...messages.value].reverse().find(m => m.doctors?.length)
  if (selectedDoctorId.value && lastMsg?.doctors) {
    const doc = lastMsg.doctors.find(d => Number(d.id) === selectedDoctorId.value)
    return doc ? doc.name : null
  }
  return null
})

// Computed: Selected payment method for context panel
const selectedPaymentMethod = computed<string | null>(() => paymentMethod.value)

// Computed: Check if in booking flow (steps 1-5)
const isInBookingFlow = computed(() => {
  return stepNumber.value >= 1 && stepNumber.value <= 5
})

// Handler: Go back to previous step
const handleBack = async () => {
  if (isLoading.value || stepNumber.value <= 1) return
  await sendMessage(locale.value === 'ar' ? 'رجوع' : 'go back')
}

// Handler: Cancel booking flow
const handleCancel = async () => {
  if (isLoading.value) return
  const confirmMsg = locale.value === 'ar' 
    ? 'هل أنت متأكد من إلغاء الحجز؟' 
    : 'Are you sure you want to cancel?'
  if (confirm(confirmMsg)) {
    // Reset context state
    selectedDepartmentId.value = null
    selectedDoctorId.value = null
    paymentMethod.value = null
    showMobileContext.value = false
    await sendMessage(locale.value === 'ar' ? 'الغاء' : 'cancel')
  }
}

// ============================================================
// SELECTION HANDLERS - Use dedicated API endpoints
// These do NOT add selections to the message array (fixes JSON bug)
// ============================================================

// Handler: Select department (uses dedicated /chatbot/select-department API)
const selectDepartment = async (dept: Department) => {
  selectedDepartmentId.value = Number(dept.id)
  await sendSelection('select-department', { department_id: dept.id })
}

// Handler: Select doctor (uses dedicated /chatbot/select-doctor API)
const selectDoctor = async (doc: Doctor) => {
  selectedDoctorId.value = Number(doc.id)
  await sendSelection('select-doctor', { doctor_id: doc.id })
}

// Handler: Select payment method (uses dedicated endpoint via message)
const selectPayment = async (method: string) => {
  paymentMethod.value = method
  // Payment uses /chatbot/message with structured payload (backend parses JSON)
  await sendSelectionViaMessage({ payment_method: method })
}

// Handler: Select time slot (sends slot selection as JSON)
const selectSlot = async (date: string, slot: any) => {
  const slotStart = typeof slot === 'string' ? `${date} ${slot}` : slot.start || `${date} ${slot.start_time}`
  const slotEnd = typeof slot === 'string' ? null : slot.end
  await sendSelectionViaMessage({ 
    slot_start: slotStart,
    slot_end: slotEnd,
    date: date
  })
}

// Handler: Submit patient form data (sends JSON to backend)
const submitPatientForm = async () => {
  if (!patientForm.full_name || !patientForm.phone || !patientForm.age || !patientForm.gender) {
    alert(locale.value === 'ar' ? 'الرجاء تعبئة جميع الحقول المطلوبة' : 'Please fill all required fields')
    return
  }
  await sendSelectionViaMessage({
    full_name: patientForm.full_name,
    phone: patientForm.phone,
    age: patientForm.age,
    gender: patientForm.gender,
    national_id: patientForm.national_id || '',
    symptoms: patientForm.symptoms || ''
  })
}

// Handler: Confirm booking (sends confirmation message)
const confirmBooking = async () => {
  await sendMessage(locale.value === 'ar' ? 'تأكيد' : 'confirm')
}

/**
 * Send a structured selection to a dedicated API endpoint.
 * Does NOT add user message to chat - only processes bot response.
 */
const sendSelection = async (endpoint: string, payload: object) => {
  if (isLoading.value) return
  isLoading.value = true
  scrollToBottom()

  try {
    const response = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/${endpoint}`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { 
        session_id: sessionId.value, 
        locale: locale.value,
        ...payload 
      }
    })

    processApiResponse(response)
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

/**
 * Send a selection via the message endpoint (for payment step).
 * The backend parses JSON payloads - we don't show them in chat.
 */
const sendSelectionViaMessage = async (payload: object) => {
  if (isLoading.value) return
  isLoading.value = true
  scrollToBottom()

  try {
    const response = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/message`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { 
        message: JSON.stringify(payload), 
        session_id: sessionId.value, 
        locale: locale.value 
      }
    })

    processApiResponse(response)
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

/**
 * Process API response and add bot messages to chat.
 * Shared by both sendSelection and sendSelectionViaMessage.
 */
const processApiResponse = (response: ChatResponse) => {
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
  if (response._ai_status) {
    aiStatus.value = response._ai_status
  }
  
  // Handle patient info form step
  if (response.action === 'collect_patient_info') {
    showPatientForm.value = true
  } else {
    showPatientForm.value = false
  }
  
  // Get ui type from backend (critical for rendering control)
  const uiType = response.ui?.type || 'none'
  const uiItems = response.ui?.items || []
  
  // Add bot messages
  botMsgs.forEach((content, i) => {
    const isLast = i === botMsgs.length - 1
    
    // Map ui.items to departments/doctors based on ui.type
    let departments = undefined
    let doctors = undefined
    if (isLast) {
      if (uiType === 'department_cards') {
        departments = uiItems.length > 0 ? uiItems : response.suggestions
      } else if (uiType === 'doctor_cards') {
        doctors = uiItems.length > 0 ? uiItems : response.doctors
      }
    }
    
    messages.value.push({
      id: Date.now() + i + 1,
      role: 'bot',
      content,
      uiType: isLast ? uiType : undefined,
      departments,
      doctors,
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

/**
 * Ensure a session exists before sending messages.
 * Calls /chatbot/init if no session_id is present.
 */
const ensureSession = async (): Promise<void> => {
  // Already have a session
  if (sessionId.value) return
  
  // Try to restore from localStorage
  const stored = localStorage.getItem('chatbot_session_id')
  if (stored) {
    sessionId.value = stored
    return
  }
  
  // Initialize new session
  console.log('[Chatbot Frontend] No session, calling /chatbot/init...')
  try {
    const initResponse = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/init`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { locale: locale.value }
    })
    
    if (initResponse.session_id) {
      sessionId.value = initResponse.session_id
      localStorage.setItem('chatbot_session_id', initResponse.session_id)
      console.log('[Chatbot Frontend] Session initialized:', initResponse.session_id)
      
      // Show welcome message if present
      if (initResponse.reply) {
        messages.value.push({
          id: Date.now(),
          role: 'bot',
          content: initResponse.reply,
        })
      }
    }
  } catch (e) {
    console.error('[Chatbot Frontend] Failed to init session:', e)
  }
}

const sendMessage = async (text: string) => {
  if (!text || isLoading.value) return
  
  // CRITICAL: Ensure session exists before sending
  await ensureSession()
  
  messages.value.push({ id: Date.now(), role: 'user', content: text })
  inputText.value = ''
  isLoading.value = true
  scrollToBottom()

  // === TRACE LOG: Frontend sending message ===
  const requestPayload = {
    message: text,
    session_id: sessionId.value,
    locale: locale.value,
    url: `${config.public.apiBase}/chatbot/message`,
    mode: currentStep.value ? 'booking' : 'qa',
    step: currentStep.value || 'initial',
  }
  console.log('[Chatbot Frontend] Sending message:', JSON.stringify(requestPayload, null, 2))

  try {
    const response = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/message`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { message: text, session_id: sessionId.value, locale: locale.value }
    })

    // === TRACE LOG: Response received (RAW for debugging) ===
    console.log('[Chatbot Frontend] RAW Response:', response)
    console.log('[Chatbot Frontend] Response summary:', {
      session_id: response.session_id,
      intent: response.intent,
      handler: response.handler,
      provider: response.provider,
      mode: response.mode,
      step: response.step,
      ui_type: response.ui?.type,
      reply_length: response.reply?.length,
      messages_count: response.messages?.length,
      request_id: response.request_id,
    })

    // Update debug banner (DEV only) - read directly from response fields
    lastDebug.value = {
      intent: response.intent || response.debug?.intent || 'MISSING',
      handler: response.handler || response.debug?.handler || 'MISSING',
      provider: response.provider || response.debug?.provider || 'MISSING',
      ui_type: response.ui?.type || response.debug?.ui_type || 'MISSING',
      mode: response.mode || response.debug?.mode || 'MISSING',
      step: response.step || response.current_step || response.debug?.step || 'MISSING',
      request_id: response.request_id || response.debug?.request_id || 'MISSING',
    }
    console.log('[Chatbot Frontend] DEBUG:', lastDebug.value)
    
    // === APPLY SERVER STATE (server-authoritative) ===
    // CRITICAL: Apply server state as single source of truth
    if (response.ui?.reset_booking === true) {
      console.log('[Chatbot Frontend] RESET BOOKING - clearing ALL UI state')
      
      // Clear ALL booking-related selections
      selectedDepartmentId.value = null
      selectedDoctorId.value = null
      paymentMethod.value = null
      
      // Clear booking step tracking
      stepNumber.value = 0
      currentStep.value = ''
      
      // Hide all booking UI panels
      showMobileContext.value = false
      showPatientForm.value = false
      
      // Force back to QA mode
      mode.value = 'qa'
      step.value = 'initial'
    }
    
    // Always apply server mode/step (server is authoritative)
    mode.value = response.mode || 'qa'
    step.value = response.step || response.current_step || 'initial'


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
    // Track AI status from response
    if (response._ai_status) {
      aiStatus.value = response._ai_status
    }
    
    // Handle patient info form step
    if (response.action === 'collect_patient_info') {
      showPatientForm.value = true
    } else {
      showPatientForm.value = false
    }
    
    // Get ui type from backend (critical for rendering control)
    const uiType = response.ui?.type || 'none'
    const uiItems = response.ui?.items || []
    
    botMsgs.forEach((content, i) => {
      const isLast = i === botMsgs.length - 1
      
      // Map ui.items to departments/doctors based on ui.type
      let departments = undefined
      let doctors = undefined
      if (isLast) {
        if (uiType === 'department_cards') {
          departments = uiItems.length > 0 ? uiItems : response.suggestions
        } else if (uiType === 'doctor_cards') {
          doctors = uiItems.length > 0 ? uiItems : response.doctors
        }
      }
      
      messages.value.push({
        id: Date.now() + i + 1,
        role: 'bot',
        content,
        uiType: isLast ? uiType : undefined,
        departments,
        doctors,
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

/**
 * Send an action-based quick reply (bypasses text classification).
 * Actions like topic_sleep, topic_nutrition route directly to You Agent.
 */
const sendAction = async (action: string, displayText?: string) => {
  if (isLoading.value) return
  
  // Ensure session exists
  await ensureSession()
  
  // Show what user "clicked" in chat
  if (displayText) {
    messages.value.push({ id: Date.now(), role: 'user', content: displayText })
  }
  
  isLoading.value = true
  scrollToBottom()
  
  console.log('[Chatbot Frontend] Sending action:', action)
  
  try {
    const response = await $fetch<ChatResponse>(`${config.public.apiBase}/chatbot/message`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}`, 'Accept-Language': locale.value },
      body: { 
        action,
        session_id: sessionId.value, 
        locale: locale.value 
      }
    })
    
    console.log('[Chatbot Frontend] Action response:', response)
    
    // Update debug banner
    lastDebug.value = {
      intent: response.intent || 'MISSING',
      handler: response.handler || 'MISSING',
      provider: response.provider || 'MISSING',
      ui_type: response.ui?.type || 'MISSING',
      mode: response.mode || 'MISSING',
      step: response.step || response.current_step || 'MISSING',
      request_id: response.request_id || 'MISSING',
    }
    
    // CRITICAL: Apply server state as single source of truth (Issue C fix)
    if (response.ui?.reset_booking === true) {
      console.log('[Chatbot Frontend] RESET BOOKING - clearing ALL UI state')
      
      // Clear ALL booking-related selections
      selectedDepartmentId.value = null
      selectedDoctorId.value = null
      paymentMethod.value = null
      
      // Clear booking step tracking
      stepNumber.value = 0
      currentStep.value = ''
      
      // Hide all booking UI panels
      showMobileContext.value = false
      showPatientForm.value = false
      
      // Force back to QA mode
      mode.value = 'qa'
      step.value = 'initial'
    }
    
    // Always apply server mode/step (server is authoritative)
    mode.value = response.mode || 'qa'
    step.value = response.step || response.current_step || 'initial'
    
    if (response.session_id) {
      sessionId.value = response.session_id
      localStorage.setItem('chatbot_session_id', response.session_id)
    }
    
    // Add bot response
    const botMsgs = response.messages || []
    botMsgs.forEach((msg: string) => {
      messages.value.push({
        id: Date.now() + Math.random(),
        role: 'bot',
        content: msg,
      })
    })
  } catch (err) {
    console.error('[Chatbot Frontend] Action error:', err)
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

// Action quick replies that route to You Agent (prevent symptom misclassification)
const actionQuickReplies = computed(() => [
  { action: 'topic_sleep', label: locale.value === 'ar' ? 'النوم والراحة' : 'Sleep & Rest' },
  { action: 'topic_nutrition', label: locale.value === 'ar' ? 'التغذية والحمية' : 'Nutrition & Diet' },
  { action: 'topic_stress', label: locale.value === 'ar' ? 'التوتر والقلق' : 'Stress & Anxiety' },
  { action: 'start_booking', label: locale.value === 'ar' ? 'احجز موعد' : 'Book Appointment' },
])

// Show action bar only after at least one bot message, in QA mode, no booking in progress
const showActionBar = computed(() => {
  // Must have at least one bot message (don't show on empty chat)
  const hasBotMessage = messages.value.some(m => m.role === 'bot')
  
  // Only in QA mode
  const inQAMode = mode.value === 'qa' && (step.value === 'initial' || stepNumber.value === 0)
  
  // No booking in progress
  const noBookingUI = !showPatientForm.value && selectedDepartmentId.value === null
  
  return hasBotMessage && inQAMode && noBookingUI
})

const submitPatientInfo = () => {
  const age = patientForm.age
  const gender = patientForm.gender
  
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
  Object.assign(patientForm, { full_name: '', phone: '', age: '', gender: '', national_id: '', symptoms: '' })
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
