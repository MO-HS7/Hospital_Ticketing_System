<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="close">
    <div class="bg-[var(--color-bg-primary)] rounded-2xl shadow-2xl max-w-lg w-full p-6 animate-fadeIn">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">{{ $t('referral.title') }}</h3>
        <button @click="close" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Success State -->
      <div v-if="success" class="text-center py-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h4 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">{{ $t('referral.success') }}</h4>
        <p class="text-[var(--color-text-muted)] mb-4">
          {{ $t('referral.createdTicket') }}: #{{ result?.new_ticket?.id }}
        </p>
        <p class="text-sm text-[var(--color-text-secondary)] mb-2">
          {{ $t('referral.toDepartment') }}: <strong>{{ selectedDeptName }}</strong>
        </p>
        <p v-if="result?.assignment?.assigned" class="text-sm text-green-600">
          Assigned to doctor
        </p>
        <p v-else class="text-sm text-amber-600">
          Awaiting assignment
        </p>
        <div class="mt-6 flex gap-3 justify-center">
          <button @click="close" class="btn-outline px-4 py-2">{{ $t('common.close') }}</button>
          <button @click="viewNewTicket" class="btn-primary px-4 py-2">{{ $t('referral.viewTicket') }}</button>
        </div>
      </div>

      <!-- Form -->
      <div v-else>
        <!-- Department Select -->
        <div class="mb-4">
          <label class="label mb-2">{{ $t('referral.toDepartment') }} *</label>
          <select 
            v-model="form.to_department_id" 
            class="input w-full"
            :disabled="loading || loadingDepts"
          >
            <option value="">{{ $t('referral.selectDepartment') }}</option>
            <option 
              v-for="dept in availableDepartments" 
              :key="dept.id" 
              :value="dept.id"
            >
              {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
            </option>
          </select>
        </div>

        <!-- Reason -->
        <div class="mb-4">
          <label class="label mb-2">{{ $t('referral.reason') }} *</label>
          <textarea 
            v-model="form.reason" 
            class="input w-full" 
            rows="3"
            :placeholder="$t('referral.enterReason')"
            :disabled="loading"
          ></textarea>
        </div>

        <!-- Priority -->
        <div class="mb-6">
          <label class="label mb-2">{{ $t('referral.priority') }}</label>
          <select v-model="form.priority" class="input w-full" :disabled="loading">
            <option value="low">{{ $t('referral.low') }}</option>
            <option value="normal">{{ $t('referral.normal') }}</option>
            <option value="high">{{ $t('referral.high') }}</option>
          </select>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 text-sm">
          {{ error }}
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
          <button @click="close" :disabled="loading" class="btn-outline px-4 py-2">
            {{ $t('common.cancel') }}
          </button>
          <button 
            @click="submit" 
            :disabled="loading || !canSubmit" 
            class="btn-primary px-4 py-2 flex items-center gap-2"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? $t('referral.loading') : $t('referral.submit') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  ticketId: number | string
  currentDepartmentId: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', result: any): void
}>()

const config = useRuntimeConfig()
const { token } = useAuth()
const { t, locale } = useI18n()

const loading = ref(false)
const loadingDepts = ref(true)
const success = ref(false)
const error = ref('')
const result = ref<any>(null)
const departments = ref<any[]>([])

const form = ref({
  to_department_id: '',
  reason: '',
  priority: 'normal'
})

const availableDepartments = computed(() => 
  departments.value.filter(d => d.id !== props.currentDepartmentId)
)

const selectedDeptName = computed(() => {
  const dept = departments.value.find(d => d.id === form.value.to_department_id)
  if (!dept) return ''
  return locale.value === 'ar' ? dept.name_ar : dept.name_en
})

const canSubmit = computed(() => 
  form.value.to_department_id && form.value.reason.trim().length > 0
)

const fetchDepartments = async () => {
  loadingDepts.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/departments`)
    departments.value = res.data || res || []
  } catch (e) {
    console.error('Failed to fetch departments:', e)
  } finally {
    loadingDepts.value = false
  }
}

const submit = async () => {
  if (!canSubmit.value) return
  
  loading.value = true
  error.value = ''

  try {
    const res = await $fetch(`${config.public.apiBase}/tickets/${props.ticketId}/refer`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: {
        to_department_id: form.value.to_department_id,
        reason: form.value.reason,
        priority: form.value.priority
      }
    })
    
    result.value = res
    success.value = true
    emit('success', res)
  } catch (e: any) {
    const msg = e.data?.message || t('referral.error')
    if (e.data?.error_code === 'same_department') {
      error.value = t('referral.sameDepartment')
    } else {
      error.value = msg
    }
  } finally {
    loading.value = false
  }
}

const close = () => {
  emit('close')
}

const viewNewTicket = () => {
  // Emit close and let parent handle navigation or refresh
  emit('close')
}

onMounted(() => {
  fetchDepartments()
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.2s ease-out;
}
</style>
