<template>
  <aside
    class="h-full bg-white dark:bg-slate-900 border-s border-slate-200 dark:border-white/10 flex flex-col"
    :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
  >
    <!-- Header -->
    <div class="px-4 py-3 border-b border-slate-200 dark:border-white/10 shrink-0">
      <h3 class="font-semibold text-sm text-slate-900 dark:text-white">
        {{ $t('chatbot.contextPanel.title') || 'Booking Summary' }}
      </h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
        {{ $t('chatbot.contextPanel.subtitle') || 'Your current selections' }}
      </p>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4">
      <!-- Stepper -->
      <div class="space-y-3">
        <template v-for="(step, index) in steps" :key="step.key">
          <div
            :class="[
              'relative flex items-start gap-3 p-3 rounded-xl transition-all',
              currentStepIndex > index
                ? 'bg-emerald-50 dark:bg-emerald-900/20'
                : currentStepIndex === index
                  ? 'bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-200 dark:ring-primary-800'
                  : 'bg-slate-50 dark:bg-white/5',
            ]"
          >
            <!-- Step Number -->
            <div
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shrink-0',
                currentStepIndex > index
                  ? 'bg-emerald-500 text-white'
                  : currentStepIndex === index
                    ? 'bg-primary-500 text-white'
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
              ]"
            >
              <svg v-if="currentStepIndex > index" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
              <span v-else>{{ index + 1 }}</span>
            </div>

            <!-- Step Content -->
            <div class="flex-1 min-w-0">
              <h4
                :class="[
                  'text-xs font-semibold uppercase tracking-wide',
                  currentStepIndex >= index
                    ? 'text-slate-700 dark:text-slate-200'
                    : 'text-slate-400 dark:text-slate-500',
                ]"
              >
                {{ step.label }}
              </h4>
              <p
                v-if="step.value"
                class="text-sm font-medium text-slate-900 dark:text-white mt-0.5 truncate"
              >
                {{ step.value }}
              </p>
              <p
                v-else-if="currentStepIndex < index"
                class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"
              >
                {{ $t('chatbot.contextPanel.pending') || 'Pending' }}
              </p>
              <p
                v-else-if="currentStepIndex === index"
                class="text-xs text-primary-600 dark:text-primary-400 mt-0.5"
              >
                {{ $t('chatbot.contextPanel.selectNow') || 'Select now' }}
              </p>
            </div>

            <!-- Edit Button -->
            <button
              v-if="currentStepIndex > index && step.value"
              @click="$emit('edit-step', index + 1)"
              class="p-1.5 rounded-lg text-slate-400 hover:text-primary-600 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-colors"
              :aria-label="$t('common.edit') || 'Edit'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
          </div>
        </template>
      </div>

      <!-- Next Action -->
      <div
        v-if="nextAction"
        class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800"
      >
        <div class="flex items-center gap-2 mb-1">
          <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-xs font-semibold text-amber-800 dark:text-amber-300">
            {{ $t('chatbot.contextPanel.nextStep') || 'Next Step' }}
          </span>
        </div>
        <p class="text-sm text-amber-700 dark:text-amber-200">
          {{ nextAction }}
        </p>
      </div>
    </div>

    <!-- Footer Actions -->
    <div
      v-if="currentStepIndex > 0"
      class="p-4 border-t border-slate-200 dark:border-white/10 shrink-0 space-y-2"
    >
      <button
        @click="$emit('cancel')"
        class="w-full px-4 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
      >
        {{ $t('chatbot.cancelBooking') || 'Cancel Booking' }}
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
interface StepData {
  key: string
  label: string
  value: string | null
}

const props = defineProps<{
  currentStep: number
  departmentName?: string | null
  doctorName?: string | null
  preferredTime?: string | null
  paymentMethod?: string | null
}>()

defineEmits<{
  (e: 'edit-step', step: number): void
  (e: 'cancel'): void
}>()

const { t, locale } = useI18n()

const currentStepIndex = computed(() => props.currentStep - 1)

const steps = computed<StepData[]>(() => [
  {
    key: 'department',
    label: t('chatbot.steps.department'),
    value: props.departmentName || null,
  },
  {
    key: 'doctor',
    label: t('chatbot.steps.doctor'),
    value: props.doctorName || null,
  },
  {
    key: 'info',
    label: t('chatbot.steps.info'),
    value: props.preferredTime || null,
  },
  {
    key: 'payment',
    label: t('chatbot.steps.payment'),
    value: props.paymentMethod 
      ? (props.paymentMethod === 'online' 
          ? t('payment.payNow') 
          : t('payment.payAtHospital'))
      : null,
  },
  {
    key: 'confirm',
    label: t('chatbot.steps.confirm'),
    value: null,
  },
])

const nextAction = computed(() => {
  const stepIndex = currentStepIndex.value
  if (stepIndex < 0) return null
  
  const actions = locale.value === 'ar'
    ? [
        'اختر القسم المناسب لحالتك',
        'اختر الطبيب المتاح',
        'أكمل معلوماتك',
        'اختر طريقة الدفع',
        'راجع الحجز وأكده',
      ]
    : [
        'Select the right department',
        'Choose your doctor',
        'Complete your information',
        'Select payment method',
        'Review and confirm',
      ]
  
  return actions[stepIndex] || null
})
</script>
