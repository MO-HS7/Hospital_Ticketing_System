<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <Transition name="fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
        @click="$emit('update:modelValue', false)"
      />
    </Transition>

    <!-- Bottom Sheet -->
    <Transition name="slide-up">
      <div
        v-if="modelValue"
        class="fixed inset-x-0 bottom-0 z-50 lg:hidden"
        :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
      >
        <div
          class="bg-white dark:bg-slate-900 rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col"
        >
          <!-- Handle -->
          <div class="flex justify-center py-2 shrink-0">
            <div class="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600" />
          </div>

          <!-- Header -->
          <div class="px-5 pb-3 flex items-center justify-between shrink-0">
            <div>
              <h3 class="font-semibold text-base text-slate-900 dark:text-white">
                {{ $t('chatbot.contextPanel.title') || 'Booking Summary' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $t('chatbot.contextPanel.subtitle') || 'Your current selections' }}
              </p>
            </div>
            <button
              @click="$emit('update:modelValue', false)"
              class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              :aria-label="$t('common.close') || 'Close'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto px-5 pb-5 space-y-3">
            <!-- Steps -->
            <template v-for="(step, index) in steps" :key="step.key">
              <div
                :class="[
                  'flex items-center gap-3 p-3 rounded-xl',
                  currentStepIndex > index
                    ? 'bg-emerald-50 dark:bg-emerald-900/20'
                    : currentStepIndex === index
                      ? 'bg-primary-50 dark:bg-primary-900/20'
                      : 'bg-slate-50 dark:bg-white/5',
                ]"
              >
                <!-- Step Number -->
                <div
                  :class="[
                    'w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0',
                    currentStepIndex > index
                      ? 'bg-emerald-500 text-white'
                      : currentStepIndex === index
                        ? 'bg-primary-500 text-white'
                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500',
                  ]"
                >
                  <svg v-if="currentStepIndex > index" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                  <span v-else>{{ index + 1 }}</span>
                </div>

                <!-- Step Info -->
                <div class="flex-1 min-w-0">
                  <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ step.label }}
                  </span>
                  <p
                    v-if="step.value"
                    class="text-sm font-semibold text-slate-900 dark:text-white truncate"
                  >
                    {{ step.value }}
                  </p>
                </div>
              </div>
            </template>
          </div>

          <!-- Actions -->
          <div
            v-if="currentStepIndex > 0"
            class="px-5 pb-5 pt-2 border-t border-slate-200 dark:border-white/10 shrink-0"
          >
            <button
              @click="$emit('cancel')"
              class="w-full px-4 py-3 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
            >
              {{ $t('chatbot.cancelBooking') || 'Cancel Booking' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
interface StepData {
  key: string
  label: string
  value: string | null
}

const props = defineProps<{
  modelValue: boolean
  currentStep: number
  departmentName?: string | null
  doctorName?: string | null
  preferredTime?: string | null
  paymentMethod?: string | null
}>()

defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'cancel'): void
}>()

const { t } = useI18n()

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
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
