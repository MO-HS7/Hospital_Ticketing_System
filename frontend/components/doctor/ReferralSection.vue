<template>
  <div class="referral-section card p-5">
    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
      </svg>
      {{ $t('doctorPortal.patientReferral') }}
    </h3>

    <!-- Current Assignment -->
    <div class="bg-[var(--color-bg-tertiary)] rounded-lg p-4 mb-4">
      <p class="text-sm text-[var(--color-text-muted)] mb-2">{{ $t('doctorPortal.currentAssignment') }}</p>
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <span class="font-medium">{{ currentDepartment }}</span>
        </div>
        <svg class="w-4 h-4 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
        </svg>
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span class="font-medium">{{ currentDoctor }}</span>
        </div>
      </div>
    </div>

    <!-- Referral Form (Inline) -->
    <div v-if="showReferralForm" class="border border-[var(--color-border)] rounded-lg p-4 mb-4">
      <!-- Patient Summary Banner -->
      <div class="flex items-center gap-3 mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
        <div class="flex-1">
          <p class="font-medium text-primary-700 dark:text-primary-300">{{ patientName }}</p>
          <p class="text-xs text-primary-600/70 dark:text-primary-400/70">
            {{ patientAge ? `${patientAge} ${$t('doctorPortal.yearsOld')}` : '' }}
            {{ patientGender ? ` • ${$t(`patientInfo.${patientGender}`)}` : '' }}
          </p>
        </div>
        <span v-if="isEmergency" class="px-2 py-1 text-xs font-bold bg-red-500 text-white rounded">🚨</span>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('doctorPortal.targetDepartment') }} *</label>
          <select v-model="referralForm.department_id" class="input w-full">
            <option value="">{{ $t('common.select') }}</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('doctorPortal.referralReason') }} *</label>
          <textarea v-model="referralForm.reason" rows="3" class="input w-full" :placeholder="$t('doctorPortal.reasonPlaceholder')"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('doctorPortal.priority') }}</label>
          <div class="flex gap-4">
            <label v-for="p in priorities" :key="p.value" class="flex items-center gap-2 cursor-pointer">
              <input v-model="referralForm.priority" type="radio" :value="p.value" class="w-4 h-4" />
              <span :class="p.class">{{ p.label }}</span>
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="showReferralForm = false" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
          <button 
            @click="submitReferral" 
            :disabled="!referralForm.department_id || !referralForm.reason.trim() || submitting" 
            class="btn-primary text-sm"
          >
            <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ $t('doctorPortal.confirmReferral') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <div v-else-if="referralSuccess" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center gap-3">
      <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <p class="font-medium text-green-700 dark:text-green-300">{{ $t('doctorPortal.referralSuccess') }}</p>
        <p class="text-sm text-green-600 dark:text-green-400">{{ $t('doctorPortal.referralSuccessDesc') }}</p>
      </div>
    </div>

    <!-- Refer Button -->
    <button 
      v-else-if="canRefer" 
      @click="showReferralForm = true" 
      class="w-full py-3 px-4 border-2 border-dashed border-[var(--color-border)] rounded-lg text-[var(--color-text-muted)] hover:border-orange-500 hover:text-orange-600 transition-colors flex items-center justify-center gap-2"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
      </svg>
      {{ $t('doctorPortal.referToAnotherDepartment') }}
    </button>

    <p v-else class="text-sm text-[var(--color-text-muted)] text-center py-2">
      {{ $t('doctorPortal.referralNotAvailable') }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Department {
  id: string
  name_en: string
  name_ar: string
}

const props = defineProps<{
  ticketId: number
  currentDepartment: string
  currentDoctor: string
  patientName: string
  patientAge?: number | null
  patientGender?: string | null
  isEmergency?: boolean
  canRefer: boolean
}>()

const emit = defineEmits<{
  referralCreated: []
}>()

const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()

const showReferralForm = ref(false)
const submitting = ref(false)
const referralSuccess = ref(false)
const departments = ref<Department[]>([])

const referralForm = ref({
  department_id: '',
  reason: '',
  priority: 'normal',
})

const priorities = [
  { value: 'normal', label: t('doctorPortal.priorityNormal'), class: 'text-[var(--color-text-primary)]' },
  { value: 'high', label: t('doctorPortal.priorityHigh'), class: 'text-orange-600' },
  { value: 'urgent', label: t('doctorPortal.priorityUrgent'), class: 'text-red-600 font-semibold' },
]

// Fetch departments for dropdown
const fetchDepartments = async () => {
  try {
    const res: any = await $fetch(`${config.public.apiBase}/departments`)
    departments.value = res.data || res || []
  } catch (e) {
    console.error('Failed to fetch departments:', e)
  }
}

const submitReferral = async () => {
  if (!referralForm.value.department_id || !referralForm.value.reason.trim()) return
  submitting.value = true
  try {
    await $fetch(`${config.public.apiBase}/tickets/${props.ticketId}/refer`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: {
        department_id: referralForm.value.department_id,
        reason: referralForm.value.reason.trim(),
        priority: referralForm.value.priority,
      }
    })
    showReferralForm.value = false
    referralSuccess.value = true
    emit('referralCreated')
  } catch (e: any) {
    console.error('Failed to create referral:', e)
    alert(e.data?.message || 'Failed to create referral')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchDepartments()
})
</script>
