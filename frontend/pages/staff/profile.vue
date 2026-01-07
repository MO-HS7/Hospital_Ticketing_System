<template>
  <NuxtLayout name="staff">
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Profile & Settings</h1>
      
      <div class="card p-6 max-w-2xl">
        <h2 class="text-lg font-semibold mb-6 pb-2 border-b border-[var(--color-border)]">Account Information</h2>
        
        <div class="space-y-4">
          <div v-if="user" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm text-[var(--color-text-muted)] mb-1">Full Name</label>
              <div class="font-medium text-[var(--color-text-primary)]">{{ user.name }}</div>
            </div>
            
            <div>
              <label class="block text-sm text-[var(--color-text-muted)] mb-1">Email Address</label>
              <div class="font-medium text-[var(--color-text-primary)]">{{ user.email }}</div>
            </div>
            
            <div>
              <label class="block text-sm text-[var(--color-text-muted)] mb-1">Role</label>
              <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                {{ user.role }}
              </div>
            </div>

            <div>
              <label class="block text-sm text-[var(--color-text-muted)] mb-1">User ID</label>
              <div class="font-mono text-sm text-[var(--color-text-secondary)]">{{ user.id }}</div>
            </div>
          </div>
          
          <div v-else class="text-[var(--color-text-muted)]">
            Loading profile information...
          </div>
        </div>
      </div>

      <div class="card p-6 max-w-2xl">
        <h2 class="text-lg font-semibold mb-6 pb-2 border-b border-[var(--color-border)]">Application Settings</h2>
        
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <div class="font-medium">Language / اللغة</div>
              <div class="text-sm text-[var(--color-text-muted)]">Switch interface language</div>
            </div>
            <LanguageSwitcher />
          </div>

          <div class="flex items-center justify-between">
            <div>
              <div class="font-medium">Theme</div>
              <div class="text-sm text-[var(--color-text-muted)]">Toggle dark/light mode</div>
            </div>
            <ThemeToggle />
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const { user } = useAuth()
</script>
