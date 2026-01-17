<template>
  <button
    :class="[
      'group relative flex items-center gap-3 p-4 rounded-2xl border-2 transition-all duration-200',
      'bg-white dark:bg-white/5 hover:bg-primary-50 dark:hover:bg-primary-900/20',
      selected
        ? 'border-primary-500 ring-2 ring-primary-500/20 shadow-md shadow-primary-500/10'
        : 'border-slate-200 dark:border-white/10 hover:border-primary-300 dark:hover:border-primary-500/50',
    ]"
    @click="$emit('select', department)"
    :aria-label="$t('chatbot.selectDepartment', { name: displayName })"
  >
    <!-- Icon -->
    <div
      :class="[
        'w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors',
        selected
          ? 'bg-primary-500 text-white'
          : 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400',
      ]"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          v-if="iconPath"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          :d="iconPath"
        />
        <path
          v-else
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        />
      </svg>
    </div>

    <!-- Content -->
    <div class="flex-1 text-start min-w-0">
      <h4
        :class="[
          'font-semibold text-sm leading-tight truncate transition-colors',
          selected
            ? 'text-primary-700 dark:text-primary-300'
            : 'text-slate-900 dark:text-white group-hover:text-primary-700 dark:group-hover:text-primary-300',
        ]"
      >
        {{ displayName }}
      </h4>
      <p
        v-if="description"
        class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1"
      >
        {{ description }}
      </p>
    </div>

    <!-- Arrow -->
    <svg
      :class="[
        'w-5 h-5 shrink-0 transition-all rtl:rotate-180',
        selected
          ? 'text-primary-500 translate-x-1 rtl:-translate-x-1'
          : 'text-slate-300 dark:text-slate-600 group-hover:text-primary-400 group-hover:translate-x-1 rtl:group-hover:-translate-x-1',
      ]"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>

    <!-- Selected Check -->
    <div
      v-if="selected"
      class="absolute top-2 end-2 w-5 h-5 rounded-full bg-primary-500 flex items-center justify-center"
    >
      <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
      </svg>
    </div>
  </button>
</template>

<script setup lang="ts">
interface Department {
  id: string | number
  name?: string
  name_en?: string
  name_ar?: string
  slug?: string
  description?: string
}

const props = defineProps<{
  department: Department
  selected?: boolean
}>()

defineEmits<{
  (e: 'select', dept: Department): void
}>()

const { locale } = useI18n()

const displayName = computed(() => {
  if (locale.value === 'ar') {
    return props.department.name_ar || props.department.name || props.department.name_en || ''
  }
  return props.department.name_en || props.department.name || props.department.name_ar || ''
})

const description = computed(() => {
  return props.department.description || null
})

// Icon mapping based on department slug
const iconPath = computed(() => {
  const slug = props.department.slug?.toLowerCase() || ''
  const iconMap: Record<string, string> = {
    cardiology: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    neurology: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    orthopedics: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
    pediatrics: 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    gastroenterology: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
    dermatology: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    ophthalmology: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
    emergency: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    dental: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    psychiatry: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
  }
  return iconMap[slug] || null
})
</script>
