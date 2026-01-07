<template>
  <NuxtLayout name="patient">
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-2">{{ $t('settings.title') || $t('nav.settings') }}</h1>
      <p class="text-[var(--color-text-muted)] mb-8">{{ $t('settings.desc') || 'Manage your account and preferences.' }}</p>

      <div class="space-y-6">
        <!-- Appearance -->
        <div class="card p-4">
          <h2 class="font-semibold mb-4 text-[var(--color-text-primary)]">Theme</h2>
          <div class="flex gap-4">
            <button 
              @click="$colorMode.preference = 'light'"
              class="flex-1 p-3 rounded-xl border text-center transition-colors hover:border-primary-500"
              :class="$colorMode.value === 'light' ? 'bg-primary-50 border-primary-500 text-primary-700' : 'bg-transparent border-[var(--color-border)]'"
            >
              ☀️ {{ $t('theme.light') }}
            </button>
            <button 
              @click="$colorMode.preference = 'dark'"
              class="flex-1 p-3 rounded-xl border text-center transition-colors hover:border-primary-500"
              :class="$colorMode.value === 'dark' ? 'bg-primary-900/20 border-primary-500 text-primary-400' : 'bg-transparent border-[var(--color-border)]'"
            >
              🌙 {{ $t('theme.dark') }}
            </button>
            <button 
              @click="$colorMode.preference = 'system'"
              class="flex-1 p-3 rounded-xl border text-center transition-colors hover:border-primary-500"
              :class="$colorMode.value === 'system' ? 'bg-gray-100 border-gray-500' : 'bg-transparent border-[var(--color-border)]'"
            >
              💻 {{ $t('theme.system') }}
            </button>
          </div>
        </div>

        <!-- Language -->
        <div class="card p-4">
          <h2 class="font-semibold mb-4 text-[var(--color-text-primary)]">Language</h2>
          <div class="flex gap-4">
            <button 
              @click="setLocale('en')"
              class="flex-1 p-3 rounded-xl border text-center transition-colors hover:border-primary-500"
              :class="locale === 'en' ? 'bg-primary-50 border-primary-500 text-primary-700' : 'bg-transparent border-[var(--color-border)]'"
            >
              🇺🇸 English
            </button>
            <button 
              @click="setLocale('ar')"
              class="flex-1 p-3 rounded-xl border text-center transition-colors hover:border-primary-500"
              :class="locale === 'ar' ? 'bg-primary-50 border-primary-500 text-primary-700' : 'bg-transparent border-[var(--color-border)]'"
            >
              🇸🇦 العربية
            </button>
          </div>
        </div>

        <!-- Account -->
        <div class="card p-4">
          <h2 class="font-semibold mb-4 text-[var(--color-text-primary)]">Account</h2>
          <NuxtLink to="/patient/profile" class="flex items-center justify-between p-3 rounded-lg hover:bg-[var(--color-bg-secondary)] transition-colors">
            <span>{{ $t('nav.profile') }}</span>
            <span class="text-[var(--color-text-muted)]">&rarr;</span>
          </NuxtLink>
          <button @click="logout" class="w-full text-start mt-2 p-3 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
            {{ $t('nav.logout') }}
          </button>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const { setLocale, locale } = useI18n()
const { logout } = useAuth()
</script>
