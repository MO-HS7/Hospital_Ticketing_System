<template>
  <header class="sticky top-0 z-50 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border-b border-white/10 dark:border-slate-800 transition-colors duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 sm:h-20">
        <!-- Logo & Brand -->
        <NuxtLink to="/" class="flex items-center gap-3 group">
          <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:scale-105 transition-transform duration-300">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
          </div>
          <span class="font-bold text-xl tracking-tight text-slate-800 dark:text-white hidden sm:block">
            {{ $t('app.name') }}
          </span>
        </NuxtLink>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-8">
          <NuxtLink v-for="link in links" :key="link.to" :to="link.to" 
            class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            {{ link.label }}
          </NuxtLink>
        </nav>

        <!-- Right Side Utils -->
        <div class="flex items-center gap-3">
          <ClientOnly>
            <LanguageSwitcher class="hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full p-1" />
            <ThemeToggle class="hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full p-1" />
          </ClientOnly>

          <div class="hidden sm:flex items-center gap-3 ms-2 pl-3 border-l border-slate-200 dark:border-slate-700">
            <NuxtLink to="/auth/login" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
              {{ $t('auth.login') }}
            </NuxtLink>
            <NuxtLink to="/auth/register" class="px-5 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-full shadow-md shadow-primary-500/20 transition-all hover:shadow-lg hover:-translate-y-0.5">
              {{ $t('landing.getStarted') || $t('auth.register') }}
            </NuxtLink>
          </div>

          <!-- Mobile Menu Button -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
            <span class="sr-only">Open menu</span>
            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-if="mobileMenuOpen" class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl absolute inset-x-0 top-full shadow-xl">
        <div class="container mx-auto px-4 py-6 space-y-4">
          <nav class="flex flex-col gap-2">
            <NuxtLink v-for="link in links" :key="link.to" :to="link.to" 
              class="px-4 py-3 text-base font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors"
              @click="mobileMenuOpen = false">
              {{ link.label }}
            </NuxtLink>
          </nav>
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
             <NuxtLink to="/auth/login" class="w-full px-4 py-3 text-center text-slate-700 dark:text-slate-200 font-medium border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" @click="mobileMenuOpen = false">
              {{ $t('auth.login') }}
            </NuxtLink>
            <NuxtLink to="/auth/register" class="w-full px-4 py-3 text-center text-white bg-primary-600 hover:bg-primary-700 rounded-xl font-medium shadow-lg shadow-primary-500/20" @click="mobileMenuOpen = false">
              {{ $t('landing.getStarted') || $t('auth.register') }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </Transition>
  </header>
</template>

<script setup lang="ts">
const mobileMenuOpen = ref(false)
const { t } = useI18n()

const links = computed(() => [
  { label: t('nav.features'), to: '/#features' },
  { label: t('nav.howItWorks'), to: '/#how-it-works' },
  { label: t('nav.faq'), to: '/#faq' },
  { label: t('nav.contact'), to: '/#contact' },
])
</script>
