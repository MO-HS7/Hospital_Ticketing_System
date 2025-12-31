<template>
  <NuxtLayout name="public">
    <div class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4">
      <div class="w-full max-w-md">
        <div class="card p-8">
          <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('auth.forgotPassword') }}</h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-2">Enter your email to reset your password</p>
          </div>
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label for="email" class="block text-sm font-medium mb-1.5">{{ $t('auth.email') }}</label>
              <input id="email" v-model="email" type="email" class="input" placeholder="name@example.com" required />
            </div>
            <button type="submit" class="btn-primary w-full py-3">Send Reset Link</button>
          </form>
          <div class="mt-6 text-center">
            <NuxtLink to="/auth/login" class="text-sm text-primary-600 hover:underline">Back to Login</NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })
const email = ref('')
const handleSubmit = () => { console.log('Password reset requested for:', email.value) }
</script>
