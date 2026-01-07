<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)] flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-[var(--color-bg-primary)] border-e border-[var(--color-border)] shrink-0 hidden lg:block">
      <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="p-4 border-b border-[var(--color-border)]">
          <NuxtLink to="/staff" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div>
              <span class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $t('app.name') }}</span>
              <span class="block text-xs text-[var(--color-text-muted)]">{{ roleLabel }}</span>
            </div>
          </NuxtLink>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
          <template v-for="item in navItems" :key="item.to">
            <NuxtLink
              :to="item.to"
              class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
              :class="isActive(item.to) 
                ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600' 
                : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'"
            >
              <span v-html="item.icon" class="w-5 h-5"></span>
              <span>{{ item.label }}</span>
            </NuxtLink>
          </template>
        </nav>

        <!-- User Info -->
        <div class="p-4 border-t border-[var(--color-border)]">
          <NuxtLink to="/staff/profile" class="flex items-center gap-3 hover:bg-[var(--color-bg-tertiary)] -m-2 p-2 rounded-lg transition-colors group">
            <div class="w-10 h-10 rounded-full bg-[var(--color-bg-tertiary)] group-hover:bg-[var(--color-bg-secondary)] flex items-center justify-center transition-colors">
              <svg class="w-5 h-5 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">{{ user?.name }}</p>
              <p class="text-xs text-[var(--color-text-muted)] truncate">{{ user?.email }}</p>
            </div>
          </NuxtLink>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Top Bar -->
      <header class="sticky top-0 z-50 glass border-b border-[var(--color-border)] px-6 py-3">
        <div class="flex items-center justify-between">
          <!-- Mobile Menu -->
          <button @click="showMobileMenu = true" class="lg:hidden p-2 -ms-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <div class="hidden lg:block text-lg font-semibold text-[var(--color-text-primary)]">
            {{ pageTitle }}
          </div>

          <div class="flex items-center gap-2">
            <LanguageSwitcher />
            <ThemeToggle />
            <button 
              @click="handleLogout" 
              :disabled="loggingOut"
              class="btn-ghost p-2 rounded-lg text-[var(--color-danger)] hover:bg-red-50 dark:hover:bg-red-900/20"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </header>

      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>

    <!-- Mobile Menu Overlay -->
    <div v-if="showMobileMenu" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-black/50" @click="showMobileMenu = false"></div>
      <aside class="absolute inset-y-0 start-0 w-64 bg-[var(--color-bg-primary)]">
        <div class="flex flex-col h-full">
          <div class="p-4 flex items-center justify-between border-b border-[var(--color-border)]">
            <span class="font-semibold">{{ $t('app.name') }}</span>
            <button @click="showMobileMenu = false" class="p-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <nav class="flex-1 p-4 space-y-1">
            <template v-for="item in navItems" :key="item.to">
              <NuxtLink
                :to="item.to"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm"
                :class="isActive(item.to) ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600' : ''"
                @click="showMobileMenu = false"
              >
                <span v-html="item.icon" class="w-5 h-5"></span>
                <span>{{ item.label }}</span>
              </NuxtLink>
            </template>
          </nav>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { user, logout } = useAuth()
const { t } = useI18n()

const loggingOut = ref(false)
const showMobileMenu = ref(false)

const userRole = computed<string>(() => user.value?.role || '')

const roleLabel = computed(() => {
  const labels: Record<string, string> = {
    doctor: t('staffTypes.doctor'),
    maintenance: t('staffTypes.maintenance'),
    reception: t('staffTypes.reception'),
    lab_technician: t('orders.lab'),
    radiologist: t('orders.radiology'),
    pharmacist: t('orders.pharmacy'),
    admin: t('nav.admin'),
  }
  return labels[userRole.value] || userRole.value
})

const pageTitle = computed(() => {
  if (route.path.includes('/orders')) return t('orders.title')
  if (route.path.includes('/chat')) return t('staffChatbot.title')
  if (route.path.includes('/tickets')) return t('tickets.title')
  return t('nav.dashboard')
})

// Icons as SVG strings
const icons = {
  dashboard: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>',
  orders: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
  tickets: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>',
  chat: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>',
}

const navItems = computed(() => {
  const items: Array<{to: string, label: string, icon: string}> = []
  const role = userRole.value

  // Lab Technician
  if (role === 'lab_technician') {
    items.push({ to: '/staff/lab/orders', label: t('orders.lab'), icon: icons.orders })
  }

  // Radiologist
  if (role === 'radiologist') {
    items.push({ to: '/staff/radiology/orders', label: t('orders.radiology'), icon: icons.orders })
  }

  // Pharmacist
  if (role === 'pharmacist') {
    items.push({ to: '/staff/pharmacy/orders', label: t('orders.pharmacy'), icon: icons.orders })
  }

  // Doctor
  if (role === 'doctor') {
    items.push({ to: '/staff/doctor/tickets', label: t('tickets.title'), icon: icons.tickets })
  }

  // Maintenance
  if (role === 'maintenance') {
    items.push({ to: '/staff/maintenance/tickets', label: t('tickets.title'), icon: icons.tickets })
  }

  // Reception
  if (role === 'reception') {
    items.push({ to: '/staff/reception/tickets', label: t('tickets.title'), icon: icons.tickets })
  }

  // Admin sees all
  if (role === 'admin') {
    items.push({ to: '/staff/lab/orders', label: t('orders.lab'), icon: icons.orders })
    items.push({ to: '/staff/radiology/orders', label: t('orders.radiology'), icon: icons.orders })
    items.push({ to: '/staff/pharmacy/orders', label: t('orders.pharmacy'), icon: icons.orders })
  }

  // IT Chat for all staff
  items.push({ to: '/staff/chat', label: t('staffChatbot.title'), icon: icons.chat })

  return items
})

const isActive = (path: string) => route.path === path || route.path.startsWith(path + '/')

const handleLogout = async () => {
  loggingOut.value = true
  try {
    await logout()
  } catch {
    localStorage.removeItem('auth_token')
    navigateTo('/auth/login')
  } finally {
    loggingOut.value = false
  }
}
</script>
