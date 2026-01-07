<template>
  <div class="min-h-screen bg-[var(--color-bg-secondary)] font-sans antialiased text-[var(--color-text-primary)] transition-colors duration-300">
    <!-- Sticky Header using glassmorphism -->
    <header class="sticky top-0 z-50 bg-[var(--color-bg-primary)]/80 backdrop-blur-md border-b border-[var(--color-border)] shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          
          <!-- Logo & Brand -->
          <NuxtLink to="/patient" class="flex items-center gap-3 hover:opacity-90 transition-opacity group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center shadow-lg group-hover:shadow-primary-500/30 transition-shadow">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div class="hidden sm:block">
              <span class="block text-sm font-bold text-[var(--color-text-primary)] leading-none">{{ $t('app.name') }}</span>
              <span class="block text-[10px] font-medium text-[var(--color-text-muted)] mt-0.5 tracking-wider uppercase">Patient Portal</span>
            </div>
          </NuxtLink>

          <!-- Desktop Navigation -->
          <nav class="hidden md:flex items-center gap-1.5 bg-[var(--color-bg-tertiary)]/50 p-1.5 rounded-xl border border-[var(--color-border)]/50">
            <NuxtLink 
              v-for="tab in tabs" 
              :key="tab.to" 
              :to="tab.to" 
              class="relative px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
              :class="isActive(tab.to) 
                ? 'bg-[var(--color-bg-primary)] text-primary-600 dark:text-primary-400 shadow-sm' 
                : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-primary)]/50'"
            >
              {{ $t(tab.label) }}
            </NuxtLink>
          </nav>

          <!-- Right: Actions & User -->
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 border-e rtl:border-l rtl:border-r-0 border-[var(--color-border)] pe-3 rtl:pl-3 rtl:pr-0">
              <LanguageSwitcher />
              <ThemeToggle />
            </div>
            
            <!-- User Dropdown -->
            <div class="relative group" ref="dropdownRef">
              <button 
                @click="showDropdown = !showDropdown" 
                class="flex items-center gap-2.5 pl-1 pr-2 py-1.5 rounded-xl hover:bg-[var(--color-bg-tertiary)] transition-colors border border-transparent hover:border-[var(--color-border)] outline-none focus:ring-2 focus:ring-primary-500/20"
              >
                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-sm font-bold text-primary-700 dark:text-primary-400 border border-primary-200 dark:border-primary-800">
                  {{ userInitials }}
                </div>
                <div class="hidden sm:block text-start">
                  <span class="block text-xs font-semibold text-[var(--color-text-primary)] max-w-[100px] truncate">{{ user?.name || $t('common.guest') }}</span>
                  <span class="block text-[10px] text-[var(--color-text-muted)]">Patient Account</span>
                </div>
                <svg class="w-4 h-4 text-[var(--color-text-muted)] transition-transform duration-200" :class="{ 'rotate-180': showDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              
              <!-- Dropdown Menu -->
              <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div 
                  v-if="showDropdown" 
                  class="absolute end-0 mt-2 w-56 rounded-xl bg-[var(--color-bg-primary)] shadow-xl ring-1 ring-black/5 dark:ring-white/10 py-1 z-50 divide-y divide-[var(--color-border)]"
                >
                  <div class="px-4 py-3">
                    <p class="text-xs font-medium text-[var(--color-text-muted)] uppercase tracking-wider mb-1">Signed in as</p>
                    <p class="text-sm font-semibold text-[var(--color-text-primary)] truncate">{{ user?.email }}</p>
                  </div>
                  <div class="py-1">
                    <NuxtLink to="/patient/profile" class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] hover:text-[var(--color-text-primary)] transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                      {{ $t('nav.profile') }}
                    </NuxtLink>
                    <NuxtLink to="/patient/settings" class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)] hover:text-[var(--color-text-primary)] transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                      {{ $t('nav.settings') }}
                    </NuxtLink>
                  </div>
                  <div class="py-1">
                    <button 
                      @click="handleLogout" 
                      :disabled="loggingOut"
                      class="w-full flex items-center gap-3 px-4 py-2 text-sm text-[var(--color-danger)] hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                      <svg v-if="loggingOut" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                      {{ $t('nav.logout') }}
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Mobile nav -->
      <nav class="md:hidden flex items-center justify-around border-t border-[var(--color-border)] bg-[var(--color-bg-primary)] px-2 py-2 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] sticky bottom-0 z-40">
        <NuxtLink 
          v-for="tab in tabs" 
          :key="tab.to" 
          :to="tab.to" 
          class="flex flex-col items-center gap-1 p-2 rounded-xl text-[10px] font-medium transition-all min-w-[64px]"
          :class="isActive(tab.to) ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'text-[var(--color-text-muted)]'"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" /></svg>
          <span>{{ $t(tab.label) }}</span>
        </NuxtLink>
      </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fadeIn">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { user, logout } = useAuth()

const showDropdown = ref(false)
const loggingOut = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase()
})

const tabs = [
  { to: '/patient', label: 'nav.home', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { to: '/patient/tickets', label: 'nav.tickets', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
  { to: '/patient/chatbot', label: 'nav.chatbot', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
]

const isActive = (path: string) => path === '/patient' 
  ? route.path === '/patient' || route.path === '/patient/' 
  : route.path.startsWith(path)

// Close dropdown when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
      showDropdown.value = false
    }
  })
})

const handleLogout = async () => {
  loggingOut.value = true
  showDropdown.value = false
  try {
    await logout()
  } catch (err) {
    console.error('Logout failed:', err)
    localStorage.removeItem('auth_token')
    navigateTo('/auth/login')
  } finally {
    loggingOut.value = false
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.4s ease-out;
}
</style>
