<template>
  <NuxtLayout name="patient">
    <div class="max-w-2xl mx-auto">
      <!-- Steps Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">{{ $t('tickets.create') }}</h1>
        <div class="relative flex items-center justify-between">
          <div class="absolute h-1 bg-[var(--color-border)] left-0 right-0 top-1/2 -z-10 rounded"></div>
          <div 
            v-for="(step, idx) in steps" 
            :key="idx"
            class="flex flex-col items-center gap-2 bg-[var(--color-bg-secondary)] px-2"
          >
            <div 
              class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors"
              :class="currentStep > idx ? 'bg-primary-600 border-primary-600 text-white' : 
                      currentStep === idx ? 'border-primary-600 text-primary-600 bg-[var(--color-bg-primary)]' : 
                      'border-[var(--color-border)] text-[var(--color-text-muted)] bg-[var(--color-bg-primary)]'"
            >
              <svg v-if="currentStep > idx" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <span v-else>{{ idx + 1 }}</span>
            </div>
            <span class="text-xs font-medium" :class="currentStep >= idx ? 'text-[var(--color-text-primary)]' : 'text-[var(--color-text-muted)]'">
              {{ $t(step) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Step 1: Department -->
      <div v-if="currentStep === 0" class="animate-fadeIn">
        <h2 class="text-lg font-semibold mb-4">{{ $t('department.select') || 'Select Department' }}</h2>
        <div v-if="loadingDepts" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-xl" />
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button
            v-for="dept in departments"
            :key="dept.id"
            @click="selectDepartment(dept)"
            class="p-4 rounded-xl border text-start hover:border-primary-500 hover:shadow-md transition-all group"
            :class="form.department_id === dept.id ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-600' : 'border-[var(--color-border)] bg-[var(--color-bg-primary)]'"
          >
            <h3 class="font-bold" :class="form.department_id === dept.id ? 'text-primary-700 dark:text-primary-400' : 'text-[var(--color-text-primary)]'">
              {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
            </h3>
            <p class="text-xs text-[var(--color-text-muted)] mt-1 group-hover:text-[var(--color-text-secondary)]">
              {{ $t('common.select') }} &rarr;
            </p>
          </button>
        </div>
      </div>

      <!-- Step 2: Doctor -->
      <div v-else-if="currentStep === 1" class="animate-fadeIn">
        <h2 class="text-lg font-semibold mb-4">{{ $t('doctor.select') || 'Select Doctor' }}</h2>
        <div v-if="loadingDoctors" class="space-y-3">
          <Skeleton v-for="i in 3" :key="i" class="h-16 rounded-xl" />
        </div>
        <div v-else-if="doctors.length === 0" class="text-center py-8">
          <EmptyState 
            :title="$t('doctor.none_available')" 
            :description="$t('doctor.none_desc') || 'No doctors currently available in this department.'" 
          />
          <button @click="currentStep--" class="btn-outline mt-4">{{ $t('common.back') }}</button>
        </div>
        <div v-else class="space-y-3">
          <button
            v-for="doc in doctors"
            :key="doc.id"
            @click="selectDoctor(doc)"
            class="w-full flex items-center gap-4 p-3 rounded-xl border text-start hover:border-blue-500 transition-all"
            :class="form.doctor_id === doc.id ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-600' : 'border-[var(--color-border)] bg-[var(--color-bg-primary)]'"
          >
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
              Dr
            </div>
            <div>
              <p class="font-semibold text-[var(--color-text-primary)]">{{ doc.name }}</p>
              <p class="text-xs text-[var(--color-text-muted)]">{{ getDeptName(selectedDepartment) }}</p>
            </div>
          </button>
        </div>
      </div>

      <!-- Step 3: Details (Subject/Desc/Date + Patient Info) -->
      <div v-else-if="currentStep === 2" class="animate-fadeIn space-y-6">
        <div>
          <label class="label">{{ $t('tickets.subject') }}</label>
          <input v-model="form.subject" type="text" class="input w-full" :placeholder="$t('tickets.subjectPlaceholder')" />
        </div>
        <div>
          <label class="label">{{ $t('tickets.description') }} ({{ $t('tickets.symptoms') }})</label>
          <textarea v-model="form.description" rows="4" class="input w-full" :placeholder="$t('tickets.descPlaceholder')"></textarea>
        </div>
        <div>
          <label class="label">{{ $t('tickets.datetime') }}</label>
          <!-- Date selector -->
          <input v-model="selectedDate" type="date" :min="todayDate" :max="maxDate" class="input w-full mb-3" @change="onDateChange" />
          
          <!-- Slot Loading -->
          <div v-if="loadingSlots" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
            <div v-for="i in 8" :key="i" class="h-10 rounded-lg bg-[var(--color-bg-tertiary)] animate-pulse"></div>
          </div>
          
          <!-- Time slots from API -->
          <div v-else-if="selectedDate && isDateValid && availableTimeSlots.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
            <button
              v-for="slot in availableTimeSlots"
              :key="slot.slot_start"
              @click="selectTimeSlot(slot)"
              :disabled="slot.status === 'full'"
              type="button"
              class="relative px-3 py-2 text-sm rounded-lg border transition-all"
              :class="getSlotClasses(slot)"
            >
              {{ formatSlotTime(slot.slot_start) }}
              <!-- Capacity indicator -->
              <span v-if="slot.status === 'limited'" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-amber-500"></span>
              <span v-if="slot.status === 'full'" class="absolute inset-0 flex items-center justify-center bg-gray-200/80 dark:bg-gray-800/80 rounded-lg text-xs">Full</span>
            </button>
          </div>
          
          <p v-if="!selectedDate" class="text-sm text-[var(--color-text-muted)]">{{ $t('createTicket.selectDate') }}</p>
          <p v-else-if="!isDateValid" class="text-sm text-red-600 dark:text-red-400 font-medium">{{ $t('tickets.invalidDate') }}</p>
          <p v-else-if="!loadingSlots && availableTimeSlots.length === 0" class="text-sm text-amber-600">{{ $t('tickets.noSlotsAvailable') }}</p>
          
          <!-- Selected slot preview -->
          <div v-if="selectedSlot" class="mt-3 p-2 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-sm">
            <span class="font-medium text-primary-700 dark:text-primary-300">{{ formatDateTimePreview(selectedSlot.slot_start) }}</span>
            <span class="text-primary-600 dark:text-primary-400"> - {{ formatSlotTime(selectedSlot.slot_end) }}</span>
          </div>
        </div>

        <!-- Patient Demographics Section -->
        <div class="border-t border-[var(--color-border)] pt-6">
          <h3 class="text-md font-semibold text-[var(--color-text-primary)] mb-4">{{ $t('patientInfo.demographics') }}</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('patientInfo.age') }} *</label>
              <input v-model.number="form.patient_age" type="number" min="0" max="150" class="input w-full" :placeholder="$t('patientInfo.agePlaceholder')" />
            </div>
            <div>
              <label class="label">{{ $t('patientInfo.gender') }} *</label>
              <div class="flex gap-4 mt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="form.patient_gender" type="radio" value="male" class="w-4 h-4 text-primary-600" />
                  <span class="text-[var(--color-text-primary)]">{{ $t('patientInfo.male') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="form.patient_gender" type="radio" value="female" class="w-4 h-4 text-primary-600" />
                  <span class="text-[var(--color-text-primary)]">{{ $t('patientInfo.female') }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Information Section -->
        <div class="border-t border-[var(--color-border)] pt-6">
          <h3 class="text-md font-semibold text-[var(--color-text-primary)] mb-4">{{ $t('patientInfo.contact') }}</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('patientInfo.contactMethod') }} *</label>
              <select v-model="form.contact_method" class="input w-full">
                <option value="">{{ $t('common.select') }}</option>
                <option value="phone">{{ $t('patientInfo.phone') }}</option>
                <option value="whatsapp">{{ $t('patientInfo.whatsapp') }}</option>
                <option value="sms">{{ $t('patientInfo.sms') }}</option>
                <option value="in_app">{{ $t('patientInfo.inApp') }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('patientInfo.contactPhone') }} *</label>
              <input v-model="form.contact_phone" type="tel" class="input w-full" :placeholder="$t('patientInfo.phonePlaceholder')" />
            </div>
          </div>
        </div>

        <!-- Medical Context Section -->
        <div class="border-t border-[var(--color-border)] pt-6">
          <h3 class="text-md font-semibold text-[var(--color-text-primary)] mb-4">{{ $t('patientInfo.medical') }}</h3>
          
          <!-- Emergency Toggle -->
          <div class="mb-4">
            <label class="flex items-center gap-3 cursor-pointer">
              <input v-model="form.is_emergency" type="checkbox" class="w-5 h-5 text-red-600 rounded" />
              <span class="text-[var(--color-text-primary)] font-medium">{{ $t('patientInfo.isEmergency') }}</span>
            </label>
            <!-- Emergency Warning -->
            <div v-if="form.is_emergency" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
              <p class="text-sm text-red-700 dark:text-red-300 flex items-start gap-2">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ $t('patientInfo.emergencyWarning') }}
              </p>
            </div>
          </div>

          <div class="space-y-4">
            <div>
              <label class="label">{{ $t('patientInfo.medicalConditions') }}</label>
              <input v-model="form.medical_conditions" type="text" class="input w-full" :placeholder="$t('patientInfo.medicalConditionsPlaceholder')" />
            </div>
            <div>
              <label class="label">{{ $t('patientInfo.additionalNotes') }}</label>
              <textarea v-model="form.additional_notes" rows="2" class="input w-full" :placeholder="$t('patientInfo.additionalNotesPlaceholder')"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4: Payment Method (only if paymentsEnabled) -->
      <div v-else-if="paymentsEnabled && currentStep === 3" class="animate-fadeIn">
        <h2 class="text-lg font-semibold mb-4">{{ $t('payment.chooseMethod') }}</h2>
        <p class="text-sm text-[var(--color-text-muted)] mb-2">{{ $t('payment.selectMethod') }}</p>
        <p class="text-lg font-bold text-primary-600 dark:text-primary-400 mb-6">{{ $t('payment.amount') }}: {{ APPOINTMENT_AMOUNT.toLocaleString() }} YER</p>
        
        <div class="space-y-4">
          <!-- Pay Now (Online) - with provider accordion -->
          <div class="rounded-xl border-2 transition-all overflow-hidden"
            :class="form.payment_method === 'online' 
              ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
              : 'border-[var(--color-border)]'"
          >
            <button
              @click="selectPayNow"
              type="button"
              class="w-full p-4 text-start"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                  :class="form.payment_method === 'online' 
                    ? 'bg-primary-500 text-white' 
                    : 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400'"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="font-semibold text-[var(--color-text-primary)]">{{ $t('payment.payNow') }}</p>
                  <p class="text-xs text-[var(--color-text-muted)]">{{ $t('payment.selectProvider') || 'Select payment provider' }}</p>
                </div>
                <div class="w-6 h-6 rounded-full flex items-center justify-center transition-transform"
                  :class="form.payment_method === 'online' ? 'rotate-180' : ''"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>
              </div>
            </button>
            
            <!-- Payment Providers Accordion -->
            <div v-if="form.payment_method === 'online'" class="px-4 pb-4">
              <div class="grid grid-cols-1 gap-2 mt-2">
                <!-- Visa/Mastercard -->
                <button
                  @click="selectPaymentProvider('visa_mastercard')"
                  type="button"
                  class="p-3 rounded-lg border flex items-center gap-3 transition-all"
                  :class="form.payment_provider === 'visa_mastercard' 
                    ? 'border-primary-500 bg-white dark:bg-gray-800' 
                    : 'border-gray-200 dark:border-gray-700 hover:border-primary-300'"
                >
                  <div class="flex gap-1">
                    <span class="text-xl">💳</span>
                  </div>
                  <span class="font-medium">Visa / Mastercard</span>
                  <div v-if="form.payment_provider === 'visa_mastercard'" class="ml-auto w-5 h-5 rounded-full bg-primary-500 text-white flex items-center justify-center">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                  </div>
                </button>
                
                <!-- PayPal -->
                <button
                  @click="selectPaymentProvider('paypal')"
                  type="button"
                  class="p-3 rounded-lg border flex items-center gap-3 transition-all"
                  :class="form.payment_provider === 'paypal' 
                    ? 'border-primary-500 bg-white dark:bg-gray-800' 
                    : 'border-gray-200 dark:border-gray-700 hover:border-primary-300'"
                >
                  <span class="text-xl">🅿️</span>
                  <span class="font-medium">PayPal</span>
                  <div v-if="form.payment_provider === 'paypal'" class="ml-auto w-5 h-5 rounded-full bg-primary-500 text-white flex items-center justify-center">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                  </div>
                </button>
                
                <!-- Bank Transfer -->
                <button
                  @click="selectPaymentProvider('bank_transfer')"
                  type="button"
                  class="p-3 rounded-lg border flex items-center gap-3 transition-all"
                  :class="form.payment_provider === 'bank_transfer' 
                    ? 'border-primary-500 bg-white dark:bg-gray-800' 
                    : 'border-gray-200 dark:border-gray-700 hover:border-primary-300'"
                >
                  <span class="text-xl">🏦</span>
                  <span class="font-medium">{{ $t('payment.bankTransfer') || 'Bank Transfer' }}</span>
                  <div v-if="form.payment_provider === 'bank_transfer'" class="ml-auto w-5 h-5 rounded-full bg-primary-500 text-white flex items-center justify-center">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                  </div>
                </button>
              </div>
              
              <!-- Payment confirmation button (stub) -->
              <button
                v-if="form.payment_provider && !paymentConfirmed"
                @click="confirmPayment"
                :disabled="paymentProcessing"
                type="button"
                class="w-full mt-4 py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-all disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <span v-if="paymentProcessing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                {{ paymentProcessing ? $t('payment.processing') : $t('payment.confirmPayment') || 'Confirm Payment' }}
              </button>
              
              <!-- Payment confirmed badge -->
              <div v-if="paymentConfirmed" class="mt-4 p-3 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <span class="font-medium text-green-700 dark:text-green-300">{{ $t('payment.confirmed') }}</span>
              </div>
            </div>
          </div>

          <!-- Pay at Hospital -->
          <button
            @click="selectPayAtHospital"
            type="button"
            class="w-full p-4 rounded-xl border-2 transition-all text-start"
            :class="form.payment_method === 'pay_at_hospital' 
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
              : 'border-[var(--color-border)] hover:border-blue-400'"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center"
                :class="form.payment_method === 'pay_at_hospital' 
                  ? 'bg-blue-500 text-white' 
                  : 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400'"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-[var(--color-text-primary)]">{{ $t('payment.payAtHospital') }}</p>
                <p class="text-xs text-[var(--color-text-muted)]">{{ $t('payment.willCollectAtHospital') }}</p>
              </div>
              <div v-if="form.payment_method === 'pay_at_hospital'" class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Step 5/4: Summary -->
      <div v-else-if="currentStep === summaryStepIndex" class="animate-fadeIn">
        <h2 class="text-lg font-semibold mb-6">{{ $t('common.summary') }}</h2>
        <div class="card p-6 divide-y divide-[var(--color-border)]">
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.department') }}</span>
            <span class="font-medium">{{ getDeptName(selectedDepartment) }}</span>
          </div>
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.doctor') }}</span>
            <span class="font-medium">{{ selectedDoctor?.name || 'Not assigned' }}</span>
          </div>
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.subject') }}</span>
            <span class="font-medium">{{ form.subject }}</span>
          </div>
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('tickets.datetime') }}</span>
            <span class="font-medium text-end">{{ formatDateTimePreview(form.scheduled_at) }}</span>
          </div>
          <!-- Patient Info Summary -->
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.age') }}</span>
            <span class="font-medium">{{ form.patient_age }}</span>
          </div>
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.gender') }}</span>
            <span class="font-medium">{{ form.patient_gender ? $t(`patientInfo.${form.patient_gender}`) : '-' }}</span>
          </div>
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.contactMethod') }}</span>
            <span class="font-medium">{{ form.contact_method ? $t(`patientInfo.${form.contact_method === 'in_app' ? 'inApp' : form.contact_method}`) : '-' }}</span>
          </div>
          <div class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.contactPhone') }}</span>
            <span class="font-medium" dir="ltr">{{ form.contact_phone || '-' }}</span>
          </div>
          <div v-if="form.is_emergency" class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('patientInfo.isEmergency') }}</span>
            <span class="font-medium text-red-600 dark:text-red-400">⚠️ {{ $t('common.yes') }}</span>
          </div>
          <div v-if="paymentsEnabled" class="py-3 flex justify-between">
            <span class="text-[var(--color-text-muted)]">{{ $t('payment.method') }}</span>
            <span class="font-medium">{{ form.payment_method === 'online' ? $t('payment.payNow') : $t('payment.payAtHospital') }}</span>
          </div>
        </div>
      </div>

      <!-- Navigation Actions -->
      <div class="flex justify-between mt-8 pt-6 border-t border-[var(--color-border)]">
        <button 
          v-if="currentStep > 0" 
          @click="currentStep--" 
          class="btn-outline px-6"
        >
          {{ $t('common.back') }}
        </button>
        <div v-else></div> <!-- Spacer -->

        <button 
          v-if="currentStep < summaryStepIndex" 
          @click="nextStep"
          :disabled="!canProceed"
          class="btn-primary px-6 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ $t('common.next') }}
        </button>
        <button 
          v-else 
          @click="submitTicket" 
          :disabled="submitting"
          class="btn-primary px-6 flex items-center gap-2"
        >
          <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          {{ $t('common.confirm') }}
        </button>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-[var(--color-bg-primary)] rounded-2xl shadow-2xl max-w-md w-full p-6 animate-fadeIn">
        <h3 class="text-xl font-bold text-[var(--color-text-primary)] mb-2">{{ $t('payment.chooseMethod') }}</h3>
        <p class="text-sm text-[var(--color-text-muted)] mb-6">{{ $t('payment.selectMethod') }}</p>
        
        <div class="space-y-3">
          <button
            @click="handlePaymentMethodSelect('online')"
            :disabled="paymentProcessing"
            class="w-full p-4 rounded-xl border-2 border-primary-500 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-all text-start group disabled:opacity-50"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-primary-500 text-white flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-[var(--color-text-primary)]">{{ $t('payment.payNow') }}</p>
                <p class="text-xs text-[var(--color-text-muted)]">{{ $t('payment.amount') }}: 150 SAR</p>
              </div>
            </div>
          </button>

          <button
            @click="handlePaymentMethodSelect('pay_at_hospital')"
            :disabled="paymentProcessing"
            class="w-full p-4 rounded-xl border-2 border-[var(--color-border)] hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all text-start group disabled:opacity-50"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-[var(--color-text-primary)]">{{ $t('payment.payAtHospital') }}</p>
                <p class="text-xs text-[var(--color-text-muted)]">{{ $t('payment.willCollectAtHospital') }}</p>
              </div>
            </div>
          </button>
        </div>

        <button 
          v-if="!paymentProcessing"
          @click="showPaymentModal = false" 
          class="w-full mt-4 px-4 py-2 text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]"
        >
          {{ $t('common.cancel') }}
        </button>

        <div v-if="paymentProcessing" class="mt-4 text-center">
          <div class="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-2"></div>
          <p class="text-sm text-[var(--color-text-muted)]">{{ $t('payment.processing') }}</p>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import Skeleton from '~/components/ui/Skeleton.vue'
import EmptyState from '~/components/ui/EmptyState.vue'

definePageMeta({ layout: false, middleware: ['auth'] })

const config = useRuntimeConfig()
const { token } = useAuth()
const { t, locale } = useI18n()
const router = useRouter()

// Steps: 0=Department, 1=Doctor, 2=Details, 3=Payment (conditional), 4=Summary
const paymentsEnabled = computed((): boolean => {
  const value = config.public.paymentsEnabled as boolean | string | undefined
  // DEBUG: Log in development to verify config value
  if (import.meta.dev) {
    console.log('[create.vue] paymentsEnabled:', value, typeof value)
  }
  return value === true || String(value) === 'true'
})
const steps = computed(() => {
  const baseSteps = ['tickets.department', 'tickets.doctor', 'tickets.details']
  if (paymentsEnabled.value) {
    baseSteps.push('payment.step')
  }
  baseSteps.push('common.summary')
  return baseSteps
})
const currentStep = ref(0)
const loadingDepts = ref(true)
const loadingDoctors = ref(false)
const submitting = ref(false)

const departments = ref<any[]>([])
const doctors = ref<any[]>([])

const form = ref({
  department_id: '',
  doctor_id: '',
  subject: '',
  description: '',
  scheduled_at: '',
  slot_start: '',
  slot_end: '',
  slot_duration: 30,
  priority: 'medium',
  type: 'appointment',
  payment_method: '',
  payment_provider: '',
  // Patient Info fields
  patient_age: null as number | null,
  patient_gender: '' as '' | 'male' | 'female',
  contact_method: '' as '' | 'phone' | 'whatsapp' | 'sms' | 'in_app',
  contact_phone: '',
  is_emergency: false,
  medical_conditions: '',
  additional_notes: '',
})

// Payment flow state
const paymentConfirmed = ref(false)
const APPOINTMENT_AMOUNT = 5000 // Fixed amount in YER

const selectedDepartment = computed(() => departments.value.find(d => d.id === form.value.department_id))
const selectedDoctor = computed(() => doctors.value.find(d => d.id === form.value.doctor_id))
const summaryStepIndex = computed(() => paymentsEnabled.value ? 4 : 3)

const canProceed = computed(() => {
  // Steps are dynamic: 0=Dept, 1=Doctor, 2=Details, 3=Payment(if enabled), 4/3=Summary
  const paymentStepIndex = 3
  
  if (currentStep.value === 0) return !!form.value.department_id
  if (currentStep.value === 1) return !!form.value.doctor_id
  // Step 3: Details + Patient Info - require subject, description, date, age, gender, contact method, phone
  if (currentStep.value === 2) {
    return !!form.value.subject && 
           !!form.value.description && 
           !!form.value.slot_start && 
           !!form.value.slot_end &&
           isDateValid.value &&
           !!form.value.patient_age && form.value.patient_age > 0 &&
           !!form.value.patient_gender &&
           !!form.value.contact_method &&
           !!form.value.contact_phone
  }
  if (paymentsEnabled.value && currentStep.value === paymentStepIndex) {
    // For pay_at_hospital: just need method selected
    if (form.value.payment_method === 'pay_at_hospital') return true
    // For pay_now/online: need method + provider + payment confirmed
    if (form.value.payment_method === 'online') {
      return !!form.value.payment_provider && paymentConfirmed.value
    }
    return false // No method selected
  }
  return true // Summary step
})

// Payment selection helper functions
const selectPayNow = () => {
  form.value.payment_method = 'online'
  form.value.payment_provider = ''
  paymentConfirmed.value = false
}

const selectPayAtHospital = () => {
  form.value.payment_method = 'pay_at_hospital'
  form.value.payment_provider = ''
  paymentConfirmed.value = false
}

const selectPaymentProvider = (provider: string) => {
  form.value.payment_provider = provider
  paymentConfirmed.value = false
}

// Stub payment confirmation (simulates payment success)
const confirmPayment = async () => {
  paymentProcessing.value = true
  // Simulate payment processing delay
  await new Promise(resolve => setTimeout(resolve, 1500))
  paymentConfirmed.value = true
  paymentProcessing.value = false
}

const getDeptName = (dept: any) => {
  if (!dept) return '-'
  return locale.value === 'ar' ? dept.name_ar : dept.name_en
}

const formatDateTimePreview = (val: string) => {
  if (!val) return '-'
  return new Date(val).toLocaleString(locale.value, { 
    weekday: 'short', month: 'short', day: 'numeric', 
    hour: 'numeric', minute: '2-digit', hour12: true 
  })
}

// Time slot logic
const selectedDate = ref('')

const todayDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

// Max booking range: 60 days from today
const maxDate = computed(() => {
  const max = new Date()
  max.setDate(max.getDate() + 60)
  return max.toISOString().split('T')[0]
})

// Validate selected date is within allowed range and has valid year
const isDateValid = computed(() => {
  if (!selectedDate.value) return false
  
  const selected = new Date(selectedDate.value)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const maxDateObj = new Date()
  maxDateObj.setDate(maxDateObj.getDate() + 60)
  
  // Check year is valid (not ancient dates like 0020)
  const currentYear = new Date().getFullYear()
  if (selected.getFullYear() < currentYear) return false
  
  // Check date is not in past
  if (selected < today) return false
  
  // Check date is within booking window
  if (selected > maxDateObj) return false
  
  return true
})

// Slot data from API
interface SlotData {
  slot_start: string
  slot_end: string
  capacity: number
  booked: number
  available: number
  status: 'available' | 'limited' | 'full'
}

const loadingSlots = ref(false)
const slotsFromApi = ref<SlotData[]>([])
const selectedSlot = ref<SlotData | null>(null)

const availableTimeSlots = computed(() => slotsFromApi.value)

const onDateChange = () => {
  selectedSlot.value = null
  form.value.slot_start = ''
  form.value.slot_end = ''
  form.value.scheduled_at = ''
  if (selectedDate.value && isDateValid.value && form.value.doctor_id) {
    fetchSlots()
  }
}

const fetchSlots = async () => {
  if (!form.value.doctor_id || !selectedDate.value) return
  
  loadingSlots.value = true
  try {
    const res = await $fetch<{ slots: SlotData[] }>(`${config.public.apiBase}/slots`, {
      headers: { Authorization: `Bearer ${token.value}` },
      params: {
        doctor_id: form.value.doctor_id,
        date: selectedDate.value
      }
    })
    slotsFromApi.value = res.slots || []
  } catch (e) {
    console.error('[fetchSlots] Error:', e)
    slotsFromApi.value = []
  } finally {
    loadingSlots.value = false
  }
}

const formatSlotTime = (isoString: string) => {
  return new Date(isoString).toLocaleTimeString(locale.value, {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const getSlotClasses = (slot: SlotData) => {
  if (selectedSlot.value?.slot_start === slot.slot_start) {
    return 'border-primary-600 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-2 ring-primary-600'
  }
  if (slot.status === 'full') {
    return 'border-gray-200 bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
  }
  if (slot.status === 'limited') {
    return 'border-amber-300 bg-amber-50 dark:bg-amber-900/20 hover:border-amber-500'
  }
  return 'border-[var(--color-border)] hover:border-primary-500 bg-[var(--color-bg-primary)]'
}

const selectTimeSlot = (slot: SlotData) => {
  if (slot.status === 'full') return
  selectedSlot.value = slot
  form.value.slot_start = slot.slot_start
  form.value.slot_end = slot.slot_end
  form.value.scheduled_at = slot.slot_start
}


const selectDepartment = (dept: any) => {
  form.value.department_id = dept.id
  fetchDoctors(dept.id)
  currentStep.value = 1
}

const selectDoctor = (doc: any) => {
  form.value.doctor_id = doc.id
  currentStep.value = 2
}

const nextStep = () => {
  if (canProceed.value) currentStep.value++
}

const fetchDepartments = async () => {
  loadingDepts.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/departments`)
    // Handle both wrapped resource { data: [...] } and direct array [...]
    departments.value = res.data || res || []
  } catch (e) {
    console.error(e)
    departments.value = []
  } finally {
    loadingDepts.value = false
  }
}

const fetchDoctors = async (deptId: string) => {
  loadingDoctors.value = true
  doctors.value = []
  try {
    // API returns array directly, not { data: [...] }
    const res = await $fetch<any[] | { data: any[] }>(`${config.public.apiBase}/staff/doctors`, {
      headers: { Authorization: `Bearer ${token.value}` },
      params: { department_id: deptId }
    })
    
    // Handle both array response and wrapped response { data: [...] }
    const allDocs = Array.isArray(res) ? res : ((res as { data?: any[] }).data || [])
    
    // Filter by department_id (API should already filter, but double-check)
    doctors.value = allDocs.filter((d: any) => String(d.department_id) === String(deptId))
    
    if (import.meta.dev && doctors.value.length === 0) {
      console.warn('[fetchDoctors] No doctors found for department:', deptId, 'Response:', res)
    }
  } catch (e: any) {
    console.error('[fetchDoctors] Error:', e.message || e)
    if (e.status === 403 || e.status === 401) {
      console.error('[fetchDoctors] Auth error - patient may not have access to this endpoint')
    }
  } finally {
    loadingDoctors.value = false
  }
}

const showPaymentModal = ref(false) // Kept for backward compatibility but unused
const paymentProcessing = ref(false)
const currentEncounterId = ref('')

const submitTicket = async () => {
  submitting.value = true
  paymentProcessing.value = true
  
  try {
    // If payments are enabled and type is appointment, handle payment flow
    if (paymentsEnabled.value && form.value.type === 'appointment' && form.value.payment_method) {
      // 1. Create encounter (do NOT send patient_id, backend infers from token)
      const encounterRes: any = await $fetch(`${config.public.apiBase}/encounters`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` },
        body: {
          amount: APPOINTMENT_AMOUNT, // 5000 YER
          source: 'patient'
        }
      })
      
      currentEncounterId.value = encounterRes.id
      
      // 2. Initiate payment
      await $fetch(`${config.public.apiBase}/payments/initiate`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` },
        body: {
          encounter_id: encounterRes.id,
          amount: APPOINTMENT_AMOUNT, // 5000 YER
          payment_method: form.value.payment_method
        }
      })
      
      // 3. If online (pay now), confirm immediately (stub mode)
      if (form.value.payment_method === 'online') {
        await $fetch(`${config.public.apiBase}/payments/confirm`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${token.value}` },
          body: {
            encounter_id: encounterRes.id,
            status: 'paid'
          }
        })
      }
      // If pay_at_hospital, encounter remains in 'pending' - ticket will be awaiting_payment
      
      // 4. Create ticket with encounter_id
      await createTicketDirectly(encounterRes.id)
    } else {
      // Payments disabled or maintenance ticket - proceed without payment flow
      await createTicketDirectly()
    }
  } catch (e: unknown) {
    const error = e as { data?: { message?: string }; message?: string }
    console.error('[submitTicket] Error:', e)
    alert(error?.data?.message || error?.message || t('payment.error') || 'Failed to create ticket')
    submitting.value = false
    paymentProcessing.value = false
  }
}

// Legacy function - kept for backward compatibility but no longer used
const handlePaymentMethodSelect = async (method: 'online' | 'pay_at_hospital') => {
  form.value.payment_method = method
  await submitTicket()
}

const createTicketDirectly = async (encounterId?: string) => {
  submitting.value = true
  try {
    const payload: any = {
      ...form.value,
      assigned_to: form.value.doctor_id
    }
    
    if (encounterId) {
      payload.encounter_id = encounterId
    }
    
    await $fetch(`${config.public.apiBase}/tickets`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: payload
    })
    
    router.push('/patient/tickets')
  } catch (e: any) {
    alert(e.data?.message || 'Failed to create ticket')
  } finally {
    submitting.value = false
    paymentProcessing.value = false
  }
}

onMounted(() => {
  fetchDepartments()
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>
