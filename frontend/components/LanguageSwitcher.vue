<template>
  <div class="relative">
    <button
      class="btn-ghost p-2 rounded-lg flex items-center gap-1"
      @click="showDropdown = !showDropdown"
    >
      <span class="text-sm font-medium">{{ currentLocale?.name }}</span>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        class="absolute end-0 mt-2 w-32 rounded-lg bg-[var(--color-bg-primary)] border border-[var(--color-border)] shadow-lg py-1 z-50"
      >
        <button
          v-for="loc in locales"
          :key="loc.code"
          class="w-full px-3 py-2 text-start text-sm hover:bg-[var(--color-bg-tertiary)] transition-colors flex items-center gap-2"
          :class="locale === loc.code ? 'text-primary-600 dark:text-primary-400 font-medium' : 'text-[var(--color-text-secondary)]'"
          @click="switchLocale(loc.code)"
        >
          <span>{{ loc.name }}</span>
          <svg v-if="locale === loc.code" class="w-4 h-4 ms-auto" fill="currentColor" viewBox="0 0 20 20">
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
const { locale, locales, setLocale } = useI18n()
const showDropdown = ref(false)

const currentLocale = computed(() => 
  locales.value.find(l => typeof l === 'object' && l.code === locale.value)
)

const switchLocale = async (code: string) => {
  await setLocale(code)
  showDropdown.value = false
}
</script>
