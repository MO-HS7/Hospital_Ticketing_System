<template>
  <header class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-b border-[var(--color-border)]">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between h-16">
        <!-- Logo & Name -->
        <NuxtLink to="/" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:scale-105 transition-transform">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <span class="font-bold text-lg text-[var(--color-text-primary)] hidden sm:block">{{ $t('app.name') }}</span>
        </NuxtLink>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-6">
          <NuxtLink to="/#features" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors">
            {{ $t('nav.features') || 'Features' }}
          </NuxtLink>
          <NuxtLink to="/#how-it-works" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors">
            {{ $t('nav.howItWorks') || 'How It Works' }}
          </NuxtLink>
          <NuxtLink to="/#faq" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors">
            {{ $t('nav.faq') || 'FAQ' }}
          </NuxtLink>
          <NuxtLink to="/#contact" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors">
            {{ $t('nav.contact') || 'Contact' }}
          </NuxtLink>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-2">
          <ClientOnly>
            <LanguageSwitcher />
            <ThemeToggle />
          </ClientOnly>
          
          <div class="hidden sm:flex items-center gap-2 ms-2">
            <NuxtLink to="/auth/login" class="btn-ghost text-sm">
              {{ $t('auth.login') }}
            </NuxtLink>
            <NuxtLink to="/auth/register" class="btn-primary text-sm">
              {{ $t('auth.register') }}
            </NuxtLink>
          </div>

          <!-- Mobile Menu Button -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden btn-ghost p-2">
            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
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
        <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-[var(--color-border)]">
          <nav class="flex flex-col gap-2">
            <NuxtLink to="/#features" class="px-4 py-2 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] rounded-lg" @click="mobileMenuOpen = false">
              {{ $t('nav.features') || 'Features' }}
            </NuxtLink>
            <NuxtLink to="/#how-it-works" class="px-4 py-2 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] rounded-lg" @click="mobileMenuOpen = false">
              {{ $t('nav.howItWorks') || 'How It Works' }}
            </NuxtLink>
            <NuxtLink to="/#faq" class="px-4 py-2 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] rounded-lg" @click="mobileMenuOpen = false">
              {{ $t('nav.faq') || 'FAQ' }}
            </NuxtLink>
            <NuxtLink to="/#contact" class="px-4 py-2 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] rounded-lg" @click="mobileMenuOpen = false">
              {{ $t('nav.contact') || 'Contact' }}
            </NuxtLink>
            <div class="border-t border-[var(--color-border)] mt-2 pt-2 flex flex-col gap-2">
              <NuxtLink to="/auth/login" class="px-4 py-2 text-[var(--color-text-primary)] font-medium hover:bg-[var(--color-bg-tertiary)] rounded-lg" @click="mobileMenuOpen = false">
                {{ $t('auth.login') }}
              </NuxtLink>
              <NuxtLink to="/auth/register" class="mx-4 btn-primary text-center" @click="mobileMenuOpen = false">
                {{ $t('auth.register') }}
              </NuxtLink>
            </div>
          </nav>
        </div>
      </Transition>
    </div>
  </header>
</template>

<script setup lang="ts">
const mobileMenuOpen = ref(false)
</script>
