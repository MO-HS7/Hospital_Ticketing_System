<template>
  <NuxtLayout name="public">
    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="card p-8 animate-fade-in">
          <!-- Header -->
          <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('auth.activateAccount') || 'Activate Your Account' }}</h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-2">{{ $t('auth.activateSubtitle') || 'Set your password to complete registration' }}</p>
          </div>

          <!-- Invalid Token -->
          <div v-if="tokenInvalid" class="text-center">
            <div class="p-4 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 mb-4">
              {{ $t('auth.invalidToken') || 'This activation link is invalid or has expired.' }}
            </div>
            <NuxtLink to="/auth/login" class="btn-primary">{{ $t('auth.login') }}</NuxtLink>
          </div>

          <!-- Activation Form -->
          <form v-else @submit.prevent="handleActivate" class="space-y-4">
            <!-- Success Message -->
            <div v-if="success" class="p-4 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
              {{ $t('auth.activateSuccess') || 'Account activated successfully! Redirecting...' }}
            </div>

            <!-- Error Alert -->
            <div v-if="error" class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">
              {{ error }}
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
              <span v-else>{{ $t('auth.activate') || 'Activate Account' }}</span>
            </button>
          </form>
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

const route = useRoute()
const { activateAccount, redirectByRole } = useAuth()

const token = computed(() => route.query.token as string)
const tokenInvalid = ref(false)
const loading = ref(false)
const success = ref(false)
const error = ref<string | null>(null)

const form = reactive({
  password: '',
  password_confirmation: ''
})

const passwordMismatch = computed(() => 
  form.password && form.password_confirmation && form.password !== form.password_confirmation
)

// Validate token on mount
onMounted(() => {
  if (!token.value) {
    tokenInvalid.value = true
  }
})

const handleActivate = async () => {
  if (passwordMismatch.value) return
  
  loading.value = true
  error.value = null
  
  try {
    const result = await activateAccount(token.value, form.password, form.password_confirmation)
    
    if (result) {
      success.value = true
      // Auto-redirect after short delay
      setTimeout(() => {
        redirectByRole()
      }, 1500)
    } else {
      error.value = 'Activation failed. Please try again.'
    }
  } catch (err: any) {
    error.value = err.message || 'Activation failed'
  } finally {
    loading.value = false
  }
}
</script>
