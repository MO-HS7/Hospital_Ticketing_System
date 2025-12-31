<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)]">
    <!-- Top Navigation -->
    <header class="sticky top-0 z-50 glass border-b border-[var(--color-border)]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <NuxtLink to="/staff" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <span class="text-lg font-semibold text-[var(--color-text-primary)]">
                {{ $t('app.name') }}
              </span>
              <span 
                v-if="staffType" 
                class="block text-xs text-[var(--color-text-muted)]"
              >
                {{ $t(`staffTypes.${staffType}`) }}
              </span>
            </div>
          </NuxtLink>

          <!-- Right Side Actions -->
          <div class="flex items-center gap-2">
            <LanguageSwitcher />
            <ThemeToggle />
            <NotificationBell />
            
            <!-- Logout -->
            <button class="btn-ghost p-2 rounded-lg text-[var(--color-danger)]">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
// Get staff type from route
const route = useRoute()

const staffType = computed(() => {
  if (route.path.includes('/doctor')) return 'doctor'
  if (route.path.includes('/maintenance')) return 'maintenance'
  if (route.path.includes('/reception')) return 'reception'
  return null
})
</script>
