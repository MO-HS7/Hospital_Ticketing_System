<template>
  <div 
    class="patient-overview-panel sticky top-0 z-10" 
    :class="[
      ticket.is_emergency 
        ? 'bg-gradient-to-r from-red-600 to-red-700 dark:from-red-800 dark:to-red-900 text-white' 
        : 'bg-gradient-to-r from-primary-600 to-primary-700 dark:from-primary-800 dark:to-primary-900 text-white'
    ]"
  >
    <!-- Emergency Banner -->
    <div v-if="ticket.is_emergency" class="bg-red-800/50 px-4 py-2 flex items-center justify-center gap-2 border-b border-red-500/30">
      <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <span class="font-bold text-sm tracking-wide">{{ $t('doctorPortal.emergencyCase') }}</span>
      <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>

    <!-- Patient Info Grid -->
    <div class="px-4 py-4 md:px-6">
      <div class="flex flex-col md:flex-row md:items-center gap-4">
        <!-- Patient Avatar & Name -->
        <div class="flex items-center gap-3">
          <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold">
            {{ patientInitials }}
          </div>
          <div>
            <h2 class="text-xl font-bold">{{ patientName }}</h2>
            <p class="text-sm opacity-80">{{ $t('doctorPortal.patient') }}</p>
          </div>
        </div>

        <!-- Patient Demographics -->
        <div class="flex flex-wrap gap-4 md:gap-6 md:ms-auto">
          <!-- Age -->
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div>
              <p class="text-xs opacity-70">{{ $t('patientInfo.age') }}</p>
              <p class="font-semibold">{{ ticket.patient_age || $t('common.notProvided') }}</p>
            </div>
          </div>

          <!-- Gender -->
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <p class="text-xs opacity-70">{{ $t('patientInfo.gender') }}</p>
              <p class="font-semibold">{{ genderLabel }}</p>
            </div>
          </div>

          <!-- Contact -->
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <div>
              <p class="text-xs opacity-70">{{ contactMethodLabel }}</p>
              <p class="font-semibold" dir="ltr">{{ ticket.contact_phone || $t('common.notProvided') }}</p>
            </div>
          </div>

          <!-- Chronic Conditions -->
          <div v-if="ticket.medical_conditions" class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-yellow-500/30 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <p class="text-xs opacity-70">{{ $t('patientInfo.medicalConditions') }}</p>
              <p class="font-semibold text-yellow-200">{{ ticket.medical_conditions }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  ticket: {
    patient?: { name?: string } | null
    patient_age?: number | null
    patient_gender?: 'male' | 'female' | null
    contact_method?: string | null
    contact_phone?: string | null
    is_emergency?: boolean
    medical_conditions?: string | null
  }
}>()

const { t } = useI18n()

const patientName = computed(() => props.ticket.patient?.name || t('common.unknownPatient'))

const patientInitials = computed(() => {
  const name = patientName.value
  if (!name || name === t('common.unknownPatient')) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
  return name.substring(0, 2).toUpperCase()
})

const genderLabel = computed(() => {
  if (!props.ticket.patient_gender) return t('common.notProvided')
  return t(`patientInfo.${props.ticket.patient_gender}`)
})

const contactMethodLabel = computed(() => {
  if (!props.ticket.contact_method) return t('patientInfo.contactPhone')
  const methodMap: Record<string, string> = {
    'phone': t('patientInfo.phone'),
    'whatsapp': t('patientInfo.whatsapp'),
    'sms': t('patientInfo.sms'),
    'in_app': t('patientInfo.inApp'),
  }
  return methodMap[props.ticket.contact_method] || t('patientInfo.contactPhone')
})
</script>

<style scoped>
.patient-overview-panel {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
