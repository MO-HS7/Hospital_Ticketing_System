<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)]">
    <header class="sticky top-0 z-50 glass border-b border-[var(--color-border)]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <NuxtLink to="/patient" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $t('app.name') }}</span>
          </NuxtLink>
          <nav class="hidden md:flex items-center gap-1">
            <NuxtLink v-for="tab in tabs" :key="tab.to" :to="tab.to" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors" :class="isActive(tab.to) ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'">
              {{ $t(tab.label) }}
            </NuxtLink>
          </nav>
          <div class="flex items-center gap-2">
            <LanguageSwitcher />
            <ThemeToggle />
            <button class="btn-ghost p-2 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </button>
          </div>
        </div>
      </div>
      <nav class="md:hidden flex items-center justify-around border-t border-[var(--color-border)] px-2 py-1">
        <NuxtLink v-for="tab in tabs" :key="tab.to" :to="tab.to" class="flex flex-col items-center gap-1 p-2 rounded-lg text-xs" :class="isActive(tab.to) ? 'text-primary-600 dark:text-primary-400' : 'text-[var(--color-text-muted)]'">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" /></svg>
          <span>{{ $t(tab.label) }}</span>
        </NuxtLink>
      </nav>
    </header>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"><slot /></main>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const tabs = [
  { to: '/patient', label: 'nav.home', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { to: '/patient/tickets', label: 'nav.tickets', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
  { to: '/patient/chatbot', label: 'nav.chatbot', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
  { to: '/patient/about', label: 'nav.about', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
]
const isActive = (path: string) => path === '/patient' ? route.path === '/patient' || route.path === '/patient/' : route.path.startsWith(path)
</script>
