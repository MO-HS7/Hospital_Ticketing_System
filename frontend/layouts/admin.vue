<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)] flex">
    <aside 
      class="fixed inset-y-0 start-0 z-40 w-64 bg-[var(--color-bg-primary)] border-e border-[var(--color-border)] transition-transform duration-200"
      :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'lg:translate-x-0']"
    >
      <div class="h-16 flex items-center gap-3 px-4 border-b border-[var(--color-border)]">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <span class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $t('app.name') }}</span>
      </div>

      <nav class="p-4 space-y-1">
        <NuxtLink to="/admin/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors" :class="isActive('/admin/dashboard') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
          {{ $t('nav.dashboard') }}
        </NuxtLink>
        <NuxtLink to="/admin/departments" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors" :class="isActive('/admin/departments') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
          {{ $t('nav.departments') }}
        </NuxtLink>
        <NuxtLink to="/admin/staff" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors" :class="isActive('/admin/staff') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
          {{ $t('nav.staff') }}
        </NuxtLink>
        <NuxtLink to="/admin/audit" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors" :class="isActive('/admin/audit') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
          {{ $t('nav.audit') }}
        </NuxtLink>
      </nav>

      <div class="absolute bottom-0 start-0 end-0 p-4 border-t border-[var(--color-border)]">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
            <span class="text-primary-700 dark:text-primary-400 font-medium">{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">{{ user?.name || $t('roles.admin') }}</p>
            <p class="text-xs text-[var(--color-text-muted)] truncate">{{ user?.email || '' }}</p>
          </div>
        </div>
        <button class="btn-ghost w-full justify-start text-[var(--color-danger)]" @click="handleLogout">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
          {{ $t('nav.logout') }}
        </button>
      </div>
    </aside>

    <div class="flex-1 lg:ps-64">
      <header class="sticky top-0 z-30 h-16 bg-[var(--color-bg-primary)] border-b border-[var(--color-border)] flex items-center justify-between px-4 lg:px-6">
        <button class="lg:hidden btn-ghost p-2" @click="sidebarOpen = !sidebarOpen">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <h1 class="text-lg font-semibold text-[var(--color-text-primary)] hidden lg:block">{{ $t('nav.dashboard') }}</h1>
        <div class="flex items-center gap-2">
          <LanguageSwitcher />
          <ThemeToggle />
          <NotificationBell />
        </div>
      </header>
      <main class="p-4 lg:p-6"><slot /></main>
    </div>

    <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen = false" />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const sidebarOpen = ref(false)
const { user, logout } = useAuth()
const userInitials = computed(() => user.value?.name ? user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : 'AD')
const isActive = (path: string) => route.path.startsWith(path)
const handleLogout = async () => { await logout() }
watch(() => route.path, () => { sidebarOpen.value = false })
</script>
