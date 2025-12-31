<template>
  <NuxtLayout name="public">
    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="card p-8 animate-fade-in">
          <!-- Header -->
          <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('auth.login') }}</h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-2">{{ $t('landing.loginSubtitle') || 'Sign in to access your account' }}</p>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">
            {{ error }}
          </div>

          <!-- Login Form -->
          <form @submit.prevent="handleLogin" class="space-y-4">
            <div>
              <label for="email" class="block text-sm font-medium mb-1.5">{{ $t('auth.email') }}</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="input"
                :placeholder="$t('auth.emailPlaceholder') || 'name@example.com'"
                required
                autocomplete="email"
              />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="text-sm font-medium">{{ $t('auth.password') }}</label>
                <NuxtLink to="/auth/forgot-password" class="text-xs text-primary-600 hover:underline">
                  {{ $t('auth.forgotPassword') }}
                </NuxtLink>
              </div>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="input"
                :placeholder="$t('auth.passwordPlaceholder') || '••••••••'"
                required
                autocomplete="current-password"
              />
            </div>

            <div class="flex items-center">
              <input id="remember" v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
              <label for="remember" class="ms-2 text-sm text-[var(--color-text-secondary)]">{{ $t('auth.rememberMe') }}</label>
            </div>

            <button
              type="submit"
              class="btn-primary w-full py-3"
              :disabled="loading"
            >
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ $t('common.loading') }}
              </span>
              <span v-else>{{ $t('auth.login') }}</span>
            </button>
          </form>

          <!-- Divider -->
          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-[var(--color-border)]" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)]">{{ $t('auth.noAccount') }}</span>
            </div>
          </div>

          <!-- Register Link -->
          <NuxtLink to="/auth/register" class="btn-secondary w-full text-center">
            {{ $t('landing.createAccount') || 'Create Patient Account' }}
          </NuxtLink>

          <!-- Demo Accounts (Development Only) -->
          <div v-if="isDev" class="mt-6 p-4 rounded-lg bg-[var(--color-bg-tertiary)] text-xs">
            <p class="font-medium text-[var(--color-text-primary)] mb-2">Demo Accounts (password: password)</p>
            <div class="space-y-1 text-[var(--color-text-muted)]">
              <p>admin@hospital.com → Admin</p>
              <p>patient@hospital.com → Patient</p>
              <p>doctor@hospital.com → Doctor</p>
              <p>maintenance@hospital.com → Maintenance</p>
              <p>reception@hospital.com → Reception</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ 
  layout: false,
  middleware: ['guest']
})

const { login, loading, error, redirectByRole } = useAuth()
const route = useRoute()

const isDev = process.dev

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const handleLogin = async () => {
  console.log('handleLogin called', { email: form.email, password: form.password.length + ' chars' })
  const success = await login({ email: form.email, password: form.password })
  console.log('login result:', success)
  if (success) {
    // Check for redirect query param
    const redirect = route.query.redirect as string
    if (redirect) {
      navigateTo(redirect)
    } else {
      redirectByRole()
    }
  }
}
</script>
