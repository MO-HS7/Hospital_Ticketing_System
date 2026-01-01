<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="btn-ghost px-3 py-2 rounded-lg flex items-center gap-2"
      :aria-label="$t('language.' + locale)"
      aria-haspopup="listbox"
      :aria-expanded="showDropdown"
      @click="showDropdown = !showDropdown"
      @keydown.escape="showDropdown = false"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
      </svg>
      <span class="text-sm font-medium">{{ currentLocaleName }}</span>
      <svg class="w-3 h-3 transition-transform" :class="showDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="showDropdown"
        role="listbox"
        :aria-activedescendant="'lang-' + locale"
        class="absolute end-0 mt-2 w-40 rounded-lg bg-[var(--color-bg-primary)] border border-[var(--color-border)] shadow-lg py-1 z-50"
      >
        <button
          v-for="(loc, index) in availableLocales"
          :id="'lang-' + loc.code"
          :key="loc.code"
          role="option"
          :aria-selected="locale === loc.code"
          class="w-full px-3 py-2.5 text-start text-sm hover:bg-[var(--color-bg-tertiary)] transition-colors flex items-center justify-between"
          :class="locale === loc.code ? 'text-primary-600 dark:text-primary-400 font-medium bg-primary-50 dark:bg-primary-900/20' : 'text-[var(--color-text-secondary)]'"
          @click="switchLocale(loc.code)"
          @keydown.arrow-down.prevent="focusNext(index)"
          @keydown.arrow-up.prevent="focusPrev(index)"
          @keydown.enter.prevent="switchLocale(loc.code)"
          @keydown.escape="showDropdown = false"
        >
          <span :dir="loc.dir">{{ loc.name }}</span>
          <svg v-if="locale === loc.code" class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </Transition>

    <!-- Click outside to close -->
    <div
      v-if="showDropdown"
      class="fixed inset-0 z-40"
      @click="showDropdown = false"
    />
  </div>
</template>

<script setup lang="ts">
const { locale, setLocale } = useI18n()
const showDropdown = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

// Define locale options with names (since nuxt config uses simple string codes)
const availableLocales: Array<{ code: 'en' | 'ar'; name: string; shortName: string; dir: 'ltr' | 'rtl' }> = [
  { code: 'en', name: 'English', shortName: 'EN', dir: 'ltr' },
  { code: 'ar', name: 'العربية', shortName: 'AR', dir: 'rtl' },
]

const currentLocaleName = computed(() => {
  const loc = availableLocales.find(l => l.code === locale.value)
  return loc?.shortName || locale.value.toUpperCase()
})

const switchLocale = async (code: 'en' | 'ar') => {
  await setLocale(code)
  // Update document direction
  if (typeof document !== 'undefined') {
    const loc = availableLocales.find(l => l.code === code)
    document.documentElement.dir = loc?.dir || 'ltr'
    document.documentElement.lang = code
  }
  showDropdown.value = false
}

const focusNext = (currentIndex: number) => {
  const buttons = dropdownRef.value?.querySelectorAll('[role="option"]')
  if (buttons && currentIndex < buttons.length - 1) {
    (buttons[currentIndex + 1] as HTMLElement).focus()
  }
}

const focusPrev = (currentIndex: number) => {
  const buttons = dropdownRef.value?.querySelectorAll('[role="option"]')
  if (buttons && currentIndex > 0) {
    (buttons[currentIndex - 1] as HTMLElement).focus()
  }
}

// Close on click outside
onMounted(() => {
  const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
      showDropdown.value = false
    }
  }
  document.addEventListener('click', handleClickOutside)
  onUnmounted(() => document.removeEventListener('click', handleClickOutside))
})
</script>
