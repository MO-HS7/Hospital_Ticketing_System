<template>
  <NuxtLayout name="patient">
    <div class="max-w-3xl mx-auto">
      <NuxtLink to="/patient/tickets" class="flex items-center gap-2 text-[var(--color-text-secondary)] mb-4">
        <svg class="w-5 h-5 icon-flip" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ $t('createTicket.back') }}
      </NuxtLink>
      <h1 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">{{ $t('createTicket.title') }}</h1>

      <!-- Steps -->
      <div class="flex justify-between mb-8">
        <div v-for="i in 4" :key="i" class="flex items-center">
          <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium', i <= step ? 'bg-primary-600 text-white' : 'bg-[var(--color-bg-tertiary)] text-[var(--color-text-muted)]']">{{ i }}</div>
          <div v-if="i < 4" class="w-12 h-0.5 mx-2" :class="i < step ? 'bg-primary-600' : 'bg-[var(--color-border)]'" />
        </div>
      </div>

      <div class="card p-6">
        <!-- Step 1 -->
        <div v-if="step === 1">
          <h2 class="font-semibold mb-4">{{ $t('createTicket.selectDepartment') }}</h2>
          <div class="grid grid-cols-2 gap-3">
            <button v-for="d in depts" :key="d.id" @click="dept = d" :class="['p-4 rounded-lg border-2 text-center', dept?.id === d.id ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'border-[var(--color-border)]']">
              <span class="text-2xl">{{ d.icon }}</span>
              <p class="text-sm mt-1">{{ d.name }}</p>
            </button>
          </div>
        </div>
        <!-- Step 2 -->
        <div v-if="step === 2">
          <h2 class="font-semibold mb-4">{{ $t('createTicket.selectDoctor') }}</h2>
          <div class="space-y-2">
            <button v-for="doc in docs" :key="doc.id" @click="doctor = doc" :class="['w-full p-4 rounded-lg border-2 text-start', doctor?.id === doc.id ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'border-[var(--color-border)]']">
              <p class="font-medium">{{ doc.name }}</p>
              <p class="text-sm text-[var(--color-text-muted)]">{{ doc.spec }}</p>
            </button>
          </div>
        </div>
        <!-- Step 3 -->
        <div v-if="step === 3">
          <h2 class="font-semibold mb-4">{{ $t('createTicket.selectTime') }}</h2>
          <input v-model="date" type="date" class="input mb-4" />
          <div class="grid grid-cols-4 gap-2">
            <button v-for="t in times" :key="t" @click="time = t" :class="['p-2 rounded border text-sm', time === t ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'border-[var(--color-border)]']">{{ t }}</button>
          </div>
        </div>
        <!-- Step 4 -->
        <div v-if="step === 4">
          <h2 class="font-semibold mb-4">{{ $t('createTicket.confirm') }}</h2>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-[var(--color-text-muted)]">Department</span><span>{{ dept?.name }}</span></div>
            <div class="flex justify-between"><span class="text-[var(--color-text-muted)]">Doctor</span><span>{{ doctor?.name }}</span></div>
            <div class="flex justify-between"><span class="text-[var(--color-text-muted)]">Date</span><span>{{ date }}</span></div>
            <div class="flex justify-between"><span class="text-[var(--color-text-muted)]">Time</span><span>{{ time }}</span></div>
          </div>
        </div>

        <div class="flex justify-between mt-6 pt-4 border-t border-[var(--color-border)]">
          <button v-if="step > 1" @click="step--" class="btn-ghost">{{ $t('createTicket.back') }}</button>
          <div v-else />
          <button v-if="step < 4" @click="step++" :disabled="!canNext" class="btn-primary">{{ $t('createTicket.next') }}</button>
          <button v-else @click="submit" class="btn-primary">{{ $t('createTicket.submit') }}</button>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })
const router = useRouter()
const step = ref(1)
const dept = ref<{id:number;name:string;icon:string}|null>(null)
const doctor = ref<{id:number;name:string;spec:string}|null>(null)
const date = ref('')
const time = ref('')
const depts = [{id:1,name:'Cardiology',icon:'🫀'},{id:2,name:'Orthopedics',icon:'🦴'},{id:3,name:'Neurology',icon:'🧠'},{id:4,name:'Pediatrics',icon:'👶'},{id:5,name:'Dermatology',icon:'🩹'},{id:6,name:'General',icon:'🩺'}]
const docs = [{id:1,name:'Dr. Ahmed Hassan',spec:'Senior'},{id:2,name:'Dr. Sara Mohamed',spec:'Specialist'},{id:3,name:'Dr. Khaled Ali',spec:'Consultant'}]
const times = ['09:00','09:30','10:00','10:30','11:00','14:00','14:30','15:00']
const canNext = computed(() => (step.value===1&&dept.value)||(step.value===2&&doctor.value)||(step.value===3&&date.value&&time.value)||step.value===4)
const submit = () => router.push('/patient/tickets')
</script>
