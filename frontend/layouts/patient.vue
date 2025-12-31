<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)]">
    <!-- Top Navigation -->
    <header class="sticky top-0 z-50 glass border-b border-[var(--color-border)]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <NuxtLink to="/patient" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-[var(--color-text-primary)]">
              {{ $t('app.name') }}
            </span>
          </NuxtLink>

          <!-- Navigation Tabs -->
          <nav class="hidden md:flex items-center gap-1">
            <NuxtLink
              v-for="tab in tabs"
              :key="tab.to"
              :to="tab.to"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="[
                isActive(tab.to)
                  ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                  : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'
              ]"
            >
              {{ $t(tab.label) }}
            </NuxtLink>
          </nav>

          <!-- Right Side Actions -->
          <div class="flex items-center gap-2">
            <!-- Language Switcher -->
            <LanguageSwitcher />
            
            <!-- Theme Toggle -->
            <ThemeToggle />

            <!-- User Menu (placeholder) -->
            <button class="btn-ghost p-2 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <nav class="md:hidden flex items-center justify-around border-t border-[var(--color-border)] px-2 py-1">
        <NuxtLink
          v-for="tab in tabs"
          :key="tab.to"
          :to="tab.to"
          class="flex flex-col items-center gap-1 p-2 rounded-lg text-xs"
          :class="[
            isActive(tab.to)
              ? 'text-primary-600 dark:text-primary-400'
              : 'text-[var(--color-text-muted)]'
          ]"
        >
          <component :is="tab.icon" class="w-5 h-5" />
          <span>{{ $t(tab.label) }}</span>
        </NuxtLink>
      </nav>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()

const tabs = [
  { to: '/patient', label: 'nav.home', icon: 'IconHome' },
  { to: '/patient/tickets', label: 'nav.tickets', icon: 'IconTicket' },
  { to: '/patient/chatbot', label: 'nav.chatbot', icon: 'IconChat' },
  { to: '/patient/about', label: 'nav.about', icon: 'IconInfo' },
]

const isActive = (path: string) => {
  if (path === '/patient') {
    return route.path === '/patient' || route.path === '/patient/'
  }
  return route.path.startsWith(path)
}
</script>
