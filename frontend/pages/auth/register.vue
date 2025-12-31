<template>
  <NuxtLayout name="public">
    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="card p-8 animate-fade-in">
          <!-- Header -->
          <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('auth.register') }}</h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-2">{{ $t('landing.registerSubtitle') || 'Create your patient account' }}</p>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">
            {{ error }}
          </div>

          <!-- Register Form -->
          <form @submit.prevent="handleRegister" class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium mb-1.5">{{ $t('auth.fullName') }}</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="input"
                :placeholder="$t('auth.namePlaceholder') || 'Your full name'"
                required
                autocomplete="name"
              />
            </div>

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
              <label for="phone" class="block text-sm font-medium mb-1.5">{{ $t('auth.phone') }}</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="input"
                :placeholder="$t('auth.phonePlaceholder') || '+966 5X XXX XXXX'"
                autocomplete="tel"
              />
            </div>

            <div>
              <label for="password" class="block text-sm font-medium mb-1.5">{{ $t('auth.password') }}</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                class="input"
                :placeholder="$t('auth.passwordPlaceholder') || '••••••••'"
                required
                autocomplete="new-password"
                minlength="8"
              />
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium mb-1.5">{{ $t('auth.confirmPassword') }}</label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="input"
                :placeholder="$t('auth.confirmPasswordPlaceholder') || '••••••••'"
                required
                autocomplete="new-password"
              />
              <p v-if="passwordMismatch" class="mt-1 text-xs text-red-600">{{ $t('auth.passwordMismatch') || 'Passwords do not match' }}</p>
            </div>

            <div class="flex items-start">
              <input id="terms" v-model="form.terms" type="checkbox" class="w-4 h-4 mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" required />
              <label for="terms" class="ms-2 text-sm text-[var(--color-text-secondary)]">
                {{ $t('auth.agreeTerms') || 'I agree to the' }}
                <NuxtLink to="/terms" class="text-primary-600 hover:underline">{{ $t('footer.terms') || 'Terms of Service' }}</NuxtLink>
                {{ $t('auth.and') || 'and' }}
                <NuxtLink to="/privacy" class="text-primary-600 hover:underline">{{ $t('footer.privacy') || 'Privacy Policy' }}</NuxtLink>
              </label>
            </div>

            <button
              type="submit"
              class="btn-primary w-full py-3"
              :disabled="Boolean(loading || passwordMismatch)"
            >
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ $t('common.loading') }}
              </span>
              <span v-else>{{ $t('auth.register') }}</span>
            </button>
          </form>

          <!-- Login Link -->
          <p class="text-center text-sm text-[var(--color-text-muted)] mt-6">
            {{ $t('auth.hasAccount') }}
            <NuxtLink to="/auth/login" class="text-primary-600 font-medium hover:underline">{{ $t('auth.login') }}</NuxtLink>
          </p>

          <!-- Staff Note -->
          <div class="mt-6 p-3 rounded-lg bg-[var(--color-bg-tertiary)] text-xs text-[var(--color-text-muted)] text-center">
            {{ $t('landing.staffAccountNote') || 'Staff accounts are created by hospital administration. If you are a staff member, please contact your HR department.' }}
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

const { register, loading, error, redirectByRole } = useAuth()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms: false
})

const passwordMismatch = computed(() => 
  form.password && form.password_confirmation && form.password !== form.password_confirmation
)

const handleRegister = async () => {
  if (passwordMismatch.value) return
  
  const success = await register({
    name: form.name,
    email: form.email,
    phone: form.phone,
    password: form.password,
    password_confirmation: form.password_confirmation
  })
  
  if (success) {
    redirectByRole()
  }
}
</script>
