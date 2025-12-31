<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)] flex">
    <!-- Sidebar -->
    <aside 
      class="fixed inset-y-0 start-0 z-40 w-64 bg-[var(--color-bg-primary)] border-e border-[var(--color-border)] transform transition-transform lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full'"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center gap-3 px-4 border-b border-[var(--color-border)]">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <span class="text-lg font-semibold text-[var(--color-text-primary)]">
          {{ $t('app.name') }}
        </span>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1">
        <NuxtLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="[
            isActive(item.to)
              ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
              : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'
          ]"
        >
          <component :is="item.icon" class="w-5 h-5" />
          {{ $t(item.label) }}
        </NuxtLink>
      </nav>

      <!-- Sidebar Footer -->
      <div class="absolute bottom-0 start-0 end-0 p-4 border-t border-[var(--color-border)]">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
            <span class="text-primary-700 dark:text-primary-400 font-medium">AD</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">Admin User</p>
            <p class="text-xs text-[var(--color-text-muted)] truncate">admin@hospital.com</p>
          </div>
        </div>
        <button class="btn-ghost w-full justify-start text-[var(--color-danger)]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          {{ $t('nav.logout') }}
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 lg:ps-64">
      <!-- Top Bar -->
      <header class="sticky top-0 z-30 h-16 bg-[var(--color-bg-primary)] border-b border-[var(--color-border)] flex items-center justify-between px-4 lg:px-6">
        <!-- Mobile Menu Button -->
        <button 
          class="lg:hidden btn-ghost p-2"
          @click="sidebarOpen = !sidebarOpen"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Page Title -->
        <h1 class="text-lg font-semibold text-[var(--color-text-primary)] hidden lg:block">
          {{ $t('nav.dashboard') }}
        </h1>

        <!-- Right Actions -->
        <div class="flex items-center gap-2">
          <LanguageSwitcher />
          <ThemeToggle />
          <NotificationBell />
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-4 lg:p-6">
        <slot />
      </main>
    </div>

    <!-- Mobile Overlay -->
    <div 
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="sidebarOpen = false"
    />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const sidebarOpen = ref(false)

const navItems = [
  { to: '/admin/dashboard', label: 'nav.dashboard', icon: 'IconDashboard' },
  { to: '/admin/departments', label: 'nav.departments', icon: 'IconBuilding' },
  { to: '/admin/staff', label: 'nav.staff', icon: 'IconUsers' },
  { to: '/admin/audit', label: 'nav.audit', icon: 'IconClipboard' },
]

const isActive = (path: string) => route.path.startsWith(path)

// Close sidebar on route change
watch(() => route.path, () => {
  sidebarOpen.value = false
})
</script>
