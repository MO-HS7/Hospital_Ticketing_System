<template>
  <NuxtLayout name="patient">
    <div class="max-w-2xl mx-auto">
      <div class="flex items-center gap-4 mb-8">
        <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-bold text-2xl">
          {{ user?.name?.charAt(0) || 'U' }}
        </div>
        <div>
          <h1 class="text-2xl font-bold">{{ user?.name }}</h1>
          <p class="text-[var(--color-text-muted)]">{{ user?.email }}</p>
        </div>
      </div>

      <div class="card divide-y divide-[var(--color-border)]">
        <div class="p-4 flex items-center justify-between">
          <span class="text-[var(--color-text-secondary)]">{{ $t('auth.fullName') }}</span>
          <span class="font-medium">{{ user?.name }}</span>
        </div>
        <div class="p-4 flex items-center justify-between">
          <span class="text-[var(--color-text-secondary)]">{{ $t('auth.email') }}</span>
          <span class="font-medium">{{ user?.email }}</span>
        </div>
        <div class="p-4 flex items-center justify-between">
          <span class="text-[var(--color-text-secondary)]">{{ $t('users.role') }}</span>
          <Badge color="primary" :label="user?.role" />
        </div>
      </div>

      <div class="mt-8">
        <button @click="logout" class="btn w-full bg-red-50 text-red-600 hover:bg-red-100 border border-red-200">
          {{ $t('auth.logout') }}
        </button>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import Badge from '~/components/ui/Badge.vue'

definePageMeta({ layout: false, middleware: ['auth'] })

const { user, logout } = useAuth()
</script>
