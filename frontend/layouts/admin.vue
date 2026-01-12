<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)] flex overflow-x-hidden">
    <!-- Mobile Sidebar Overlay -->
    <Transition name="fade">
      <div 
        v-if="sidebarOpen" 
        class="fixed inset-0 bg-black/50 z-40 lg:hidden" 
        @click="sidebarOpen = false" 
        aria-hidden="true"
      />
    </Transition>

    <!-- Sidebar / Mobile Drawer -->
    <aside 
      class="fixed inset-y-0 start-0 z-50 bg-[var(--color-bg-primary)] border-e border-[var(--color-border)] transition-all duration-300 ease-in-out shadow-xl lg:shadow-none"
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full',
        'lg:translate-x-0 rtl:lg:translate-x-0',
        collapsed ? 'lg:w-20' : 'lg:w-64',
        'w-[280px] max-w-[85vw]'
      ]"
    >
      <!-- Header - Always show full on mobile, respect desktop collapse -->
      <div class="h-16 flex items-center gap-3 px-4 border-b border-[var(--color-border)]">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center shrink-0">
          <Icon name="hospital" size="lg" class="text-white" />
        </div>
        <!-- Always show on mobile, hide when collapsed on desktop -->
        <Transition name="fade">
          <span v-if="!isDesktopCollapsed" class="text-lg font-semibold text-[var(--color-text-primary)] truncate">{{ $t('app.name') }}</span>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="p-2 sm:p-3 space-y-3 sm:space-y-4 overflow-y-auto" style="max-height: calc(100vh - 180px)">
        <!-- Operations Group -->
        <div>
          <p v-if="!isDesktopCollapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.operations') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/dashboard" 
              class="nav-link" 
              :class="[navLinkClass('/admin/dashboard'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('commandCenter.commandCenter') : ''"
            >
              <Icon name="dashboard" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('commandCenter.commandCenter') }}</span>
            </NuxtLink>
            <NuxtLink 
              to="/admin/sla-monitor" 
              class="nav-link" 
              :class="[navLinkClass('/admin/sla-monitor'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('commandCenter.slaMonitor') : ''"
            >
              <Icon name="clock" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('commandCenter.slaMonitor') }}</span>
            </NuxtLink>
            <NuxtLink 
              to="/admin/tickets" 
              class="nav-link" 
              :class="[navLinkClass('/admin/tickets'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('nav.tickets') : ''"
            >
              <Icon name="ticket" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('nav.tickets') }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Management Group -->
        <div>
          <p v-if="!isDesktopCollapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.management') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/departments" 
              class="nav-link" 
              :class="[navLinkClass('/admin/departments'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('nav.departments') : ''"
            >
              <Icon name="building" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('nav.departments') }}</span>
            </NuxtLink>
            <NuxtLink 
              to="/admin/users" 
              class="nav-link" 
              :class="[navLinkClass('/admin/users'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('nav.users') : ''"
            >
              <Icon name="users" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('nav.users') }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Reports Group -->
        <div>
          <p v-if="!isDesktopCollapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.reports') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/audit" 
              class="nav-link" 
              :class="[navLinkClass('/admin/audit'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('nav.audit') : ''"
            >
              <Icon name="clipboard-list" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('nav.audit') }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- System Group -->
        <div>
          <p v-if="!isDesktopCollapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.system') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/system-health" 
              class="nav-link" 
              :class="[navLinkClass('/admin/system-health'), { 'lg:justify-center': isDesktopCollapsed }]"
              :title="isDesktopCollapsed ? $t('commandCenter.systemHealth') : ''"
            >
              <Icon name="shield-check" size="md" :fixed-width="true" />
              <span v-if="!isDesktopCollapsed">{{ $t('commandCenter.systemHealth') }}</span>
            </NuxtLink>
          </div>
        </div>
      </nav>

      <!-- User Section -->
      <div class="absolute bottom-0 start-0 end-0 p-3 border-t border-[var(--color-border)]">
        <!-- Always show on mobile drawer, hide when collapsed on desktop -->
        <div v-if="!isDesktopCollapsed" class="flex items-center gap-3 mb-3 px-2">
          <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0">
            <span class="text-sm text-primary-700 dark:text-primary-400 font-medium">{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">{{ user?.name || $t('roles.admin') }}</p>
            <p class="text-xs text-[var(--color-text-muted)] truncate">{{ user?.email || '' }}</p>
          </div>
        </div>
        
        <!-- Collapse Toggle (Desktop Only - MUST use !hidden to override any display:flex from other classes) -->
        <button 
          @click="toggleCollapse" 
          class="hidden lg:flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] w-full"
          :class="{ 'justify-center': isDesktopCollapsed }"
        >
          <Icon name="angles-left" size="md" :class="{ 'rotate-180': isDesktopCollapsed }" class="transition-transform" />
          <span v-if="!isDesktopCollapsed">{{ $t('commandCenter.collapse') }}</span>
        </button>
        
        <!-- Logout -->
        <button 
          @click="handleLogout" 
          class="nav-link text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 w-full mt-1"
          :class="{ 'lg:justify-center': isDesktopCollapsed }"
        >
          <Icon name="logout" size="md" :fixed-width="true" />
          <span v-if="!isDesktopCollapsed">{{ $t('nav.logout') }}</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 transition-all duration-300" :class="isDesktopCollapsed ? 'lg:ps-20' : 'lg:ps-64'">
      <!-- Header -->
      <header class="sticky top-0 z-30 h-14 sm:h-16 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-white/10 flex items-center justify-between px-3 sm:px-4 lg:px-6">
        <!-- Left Section: Menu + Page Context -->
        <div class="flex items-center gap-3 min-w-0">
          <!-- Mobile menu toggle -->
          <button 
            class="lg:hidden h-10 w-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/20 transition" 
            @click="sidebarOpen = !sidebarOpen"
            aria-label="Toggle menu"
          >
            <Icon name="menu" size="lg" />
          </button>
          
          <!-- Page Title / Breadcrumb Context -->
          <div class="flex items-center gap-2 min-w-0">
            <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white truncate">{{ pageTitle }}</h1>
          </div>
        </div>
        
        <!-- Right Section: Actions + Controls + User -->
        <div class="flex items-center gap-1 sm:gap-2">
          <!-- Quick Actions (desktop only) -->
          <NuxtLink 
            to="/admin/dashboard" 
            class="hidden md:flex h-9 px-3 items-center gap-1.5 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/10 transition"
          >
            <Icon name="plus" size="sm" />
            <span class="hidden lg:inline">{{ $t('tickets.create') }}</span>
          </NuxtLink>
          
          <!-- Divider (desktop) -->
          <div class="hidden md:block w-px h-6 bg-slate-200 dark:bg-white/10 mx-1" />
          
          <!-- Language Switcher -->
          <LanguageSwitcher />
          
          <!-- Theme Toggle -->
          <ThemeToggle />
          
          <!-- Notifications -->
          <NotificationBell />
          
          <!-- Divider -->
          <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-white/10 mx-1" />
          
          <!-- User Menu -->
          <div class="relative" ref="userMenuRef">
            <button 
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-2 h-10 px-2 sm:px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition"
            >
              <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center shrink-0">
                <span class="text-xs font-semibold text-primary-700 dark:text-primary-400">{{ userInitials }}</span>
              </div>
              <div class="hidden sm:flex flex-col items-start">
                <span class="text-sm font-medium text-slate-900 dark:text-white max-w-[100px] truncate">{{ user?.name || $t('roles.admin') }}</span>
              </div>
              <Icon name="chevron-down" size="sm" class="hidden sm:block text-slate-400 dark:text-white/40" />
            </button>
            
            <!-- Dropdown Menu -->
            <Transition name="dropdown">
              <div 
                v-if="userMenuOpen" 
                class="absolute end-0 top-full mt-2 w-56 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900 shadow-lg py-1 z-50"
              >
                <!-- User Info -->
                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/5">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">{{ user?.name || $t('roles.admin') }}</p>
                  <p class="text-xs text-slate-500 dark:text-white/50 truncate">{{ user?.email || '' }}</p>
                </div>
                
                <!-- Menu Items -->
                <div class="py-1">
                  <NuxtLink 
                    to="/admin/dashboard" 
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-white/70 hover:bg-slate-50 dark:hover:bg-white/5 transition"
                    @click="userMenuOpen = false"
                  >
                    <Icon name="dashboard" size="sm" />
                    {{ $t('commandCenter.commandCenter') }}
                  </NuxtLink>
                </div>
                
                <!-- Logout -->
                <div class="border-t border-slate-100 dark:border-white/5 pt-1">
                  <button 
                    @click="handleLogout; userMenuOpen = false" 
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
                  >
                    <Icon name="logout" size="sm" />
                    {{ $t('nav.logout') }}
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </header>
      
      <main class="p-3 sm:p-4 lg:p-6 overflow-x-hidden"><slot /></main>
    </div>

  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { t } = useI18n()
const { user, logout } = useAuth()

// Mobile sidebar state (drawer)
const sidebarOpen = ref(false)

// User menu dropdown state
const userMenuOpen = ref(false)
const userMenuRef = ref<HTMLElement | null>(null)

// Collapsed state with localStorage persistence (desktop only)
const collapsed = ref(false)

// Detect if we're on desktop (lg+) - collapsed only applies on desktop
const isDesktop = ref(false)

// SSR hydration guard - prevents mismatch for user-specific data
const isMounted = ref(false)

onMounted(() => {
  isMounted.value = true
  
  const saved = localStorage.getItem('admin-sidebar-collapsed')
  if (saved) collapsed.value = saved === 'true'
  
  // Check viewport size on mount and resize
  const checkDesktop = () => {
    isDesktop.value = window.innerWidth >= 1024 // lg breakpoint
  }
  checkDesktop()
  window.addEventListener('resize', checkDesktop)
  
  // Click outside handler for user menu
  document.addEventListener('click', (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target as Node)) {
      userMenuOpen.value = false
    }
  })
})

// Computed: collapsed state only applies on desktop (lg+)
// On mobile/tablet, sidebar is ALWAYS in expanded drawer mode
const isDesktopCollapsed = computed(() => isDesktop.value && collapsed.value)

const toggleCollapse = () => {
  collapsed.value = !collapsed.value
  localStorage.setItem('admin-sidebar-collapsed', String(collapsed.value))
}

// User initials - only show actual value after mount to prevent hydration mismatch
const userInitials = computed(() => {
  if (!isMounted.value) return '--'  // SSR placeholder
  return user.value?.name 
    ? user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) 
    : 'AD'
})

const pageTitle = computed(() => {
  const path = route.path
  if (path.includes('dashboard')) return t('commandCenter.commandCenter')
  if (path.includes('tickets')) return t('nav.tickets')
  if (path.includes('departments')) return t('nav.departments')
  if (path.includes('users')) return t('nav.users')
  if (path.includes('audit')) return t('nav.audit')
  if (path.includes('sla-monitor')) return t('commandCenter.slaMonitor')
  if (path.includes('system-health')) return t('commandCenter.systemHealth')
  return t('nav.dashboard')
})

const navLinkClass = (path: string) => 
  route.path.startsWith(path) 
    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400' 
    : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'

const handleLogout = async () => { await logout() }

watch(() => route.path, () => { sidebarOpen.value = false })
</script>

<style scoped>
.nav-link {
  @apply flex items-center gap-3 px-3 py-3 sm:py-2.5 rounded-lg text-sm font-medium transition-colors;
  min-height: 36px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.95);
}
</style>
