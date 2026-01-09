<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)] flex">
    <!-- Collapsible Sidebar -->
    <aside 
      class="fixed inset-y-0 start-0 z-40 bg-[var(--color-bg-primary)] border-e border-[var(--color-border)] transition-all duration-300 ease-in-out"
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:translate-x-0',
        collapsed ? 'lg:w-20' : 'lg:w-64',
        'w-64'
      ]"
    >
      <!-- Header -->
      <div class="h-16 flex items-center gap-3 px-4 border-b border-[var(--color-border)]">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center shrink-0">
          <Icon name="hospital" size="lg" class="text-white" />
        </div>
        <Transition name="fade">
          <span v-if="!collapsed" class="text-lg font-semibold text-[var(--color-text-primary)] truncate">{{ $t('app.name') }}</span>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="p-3 space-y-4 overflow-y-auto" style="max-height: calc(100vh - 180px)">
        <!-- Operations Group -->
        <div>
          <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.operations') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/dashboard" 
              class="nav-link" 
              :class="[navLinkClass('/admin/dashboard'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('commandCenter.commandCenter') : ''"
            >
              <Icon name="dashboard" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('commandCenter.commandCenter') }}</span>
            </NuxtLink>
            <NuxtLink 
              to="/admin/sla-monitor" 
              class="nav-link" 
              :class="[navLinkClass('/admin/sla-monitor'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('commandCenter.slaMonitor') : ''"
            >
              <Icon name="clock" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('commandCenter.slaMonitor') }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Management Group -->
        <div>
          <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.management') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/departments" 
              class="nav-link" 
              :class="[navLinkClass('/admin/departments'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('nav.departments') : ''"
            >
              <Icon name="building" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('nav.departments') }}</span>
            </NuxtLink>
            <NuxtLink 
              to="/admin/users" 
              class="nav-link" 
              :class="[navLinkClass('/admin/users'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('nav.users') : ''"
            >
              <Icon name="users" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('nav.users') }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Reports Group -->
        <div>
          <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.reports') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/audit" 
              class="nav-link" 
              :class="[navLinkClass('/admin/audit'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('nav.audit') : ''"
            >
              <Icon name="clipboard-list" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('nav.audit') }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- System Group -->
        <div>
          <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">
            {{ $t('commandCenter.system') }}
          </p>
          <div class="space-y-1">
            <NuxtLink 
              to="/admin/system-health" 
              class="nav-link" 
              :class="[navLinkClass('/admin/system-health'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('commandCenter.systemHealth') : ''"
            >
              <Icon name="shield-check" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('commandCenter.systemHealth') }}</span>
            </NuxtLink>
            <NuxtLink 
              to="/admin/maintenance-overview" 
              class="nav-link" 
              :class="[navLinkClass('/admin/maintenance-overview'), { 'justify-center': collapsed }]"
              :title="collapsed ? $t('commandCenter.maintenanceOverview') : ''"
            >
              <Icon name="tools" size="md" :fixed-width="true" />
              <span v-if="!collapsed">{{ $t('commandCenter.maintenanceOverview') }}</span>
            </NuxtLink>
          </div>
        </div>
      </nav>

      <!-- User Section -->
      <div class="absolute bottom-0 start-0 end-0 p-3 border-t border-[var(--color-border)]">
        <div v-if="!collapsed" class="flex items-center gap-3 mb-3 px-2">
          <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0">
            <span class="text-sm text-primary-700 dark:text-primary-400 font-medium">{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-[var(--color-text-primary)] truncate">{{ user?.name || $t('roles.admin') }}</p>
            <p class="text-xs text-[var(--color-text-muted)] truncate">{{ user?.email || '' }}</p>
          </div>
        </div>
        
        <!-- Collapse Toggle (Desktop) -->
        <button 
          @click="toggleCollapse" 
          class="hidden lg:flex nav-link text-[var(--color-text-secondary)] w-full"
          :class="{ 'justify-center': collapsed }"
        >
          <Icon name="angles-left" size="md" :class="{ 'rotate-180': collapsed }" class="transition-transform" />
          <span v-if="!collapsed">{{ $t('commandCenter.collapse') }}</span>
        </button>
        
        <!-- Logout -->
        <button 
          @click="handleLogout" 
          class="nav-link text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 w-full mt-1"
          :class="{ 'justify-center': collapsed }"
        >
          <Icon name="logout" size="md" :fixed-width="true" />
          <span v-if="!collapsed">{{ $t('nav.logout') }}</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 transition-all duration-300" :class="collapsed ? 'lg:ps-20' : 'lg:ps-64'">
      <!-- Header -->
      <header class="sticky top-0 z-30 h-16 bg-[var(--color-bg-primary)] border-b border-[var(--color-border)] flex items-center justify-between px-4 lg:px-6">
        <div class="flex items-center gap-3">
          <!-- Mobile menu toggle -->
          <button class="lg:hidden btn-ghost p-2" @click="sidebarOpen = !sidebarOpen">
            <Icon name="menu" size="lg" />
          </button>
          <h1 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ pageTitle }}</h1>
        </div>
        
        <div class="flex items-center gap-2">
          <!-- Quick Actions -->
          <div class="hidden md:flex items-center gap-2 me-2">
            <NuxtLink to="/admin/dashboard" class="btn-ghost text-sm px-3 py-1.5">
              <Icon name="plus" size="sm" class="me-1.5" />
              {{ $t('tickets.create') }}
            </NuxtLink>
          </div>
          
          <LanguageSwitcher />
          <ThemeToggle />
          <NotificationBell />
        </div>
      </header>
      
      <main class="p-4 lg:p-6"><slot /></main>
    </div>

    <!-- Mobile overlay -->
    <Transition name="fade">
      <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-30 lg:hidden" @click="sidebarOpen = false" />
    </Transition>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { t } = useI18n()
const { user, logout } = useAuth()

// Mobile sidebar state
const sidebarOpen = ref(false)

// Collapsed state with localStorage persistence
const collapsed = ref(false)

onMounted(() => {
  const saved = localStorage.getItem('admin-sidebar-collapsed')
  if (saved) collapsed.value = saved === 'true'
})

const toggleCollapse = () => {
  collapsed.value = !collapsed.value
  localStorage.setItem('admin-sidebar-collapsed', String(collapsed.value))
}

const userInitials = computed(() => 
  user.value?.name 
    ? user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) 
    : 'AD'
)

const pageTitle = computed(() => {
  const path = route.path
  if (path.includes('dashboard')) return t('commandCenter.commandCenter')
  if (path.includes('departments')) return t('nav.departments')
  if (path.includes('users')) return t('nav.users')
  if (path.includes('audit')) return t('nav.audit')
  if (path.includes('sla-monitor')) return t('commandCenter.slaMonitor')
  if (path.includes('system-health')) return t('commandCenter.systemHealth')
  if (path.includes('maintenance-overview')) return t('commandCenter.maintenanceOverview')
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
  @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
