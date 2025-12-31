<template>
  <div class="relative">
    <button
      class="btn-ghost p-2 rounded-lg relative"
      @click="showPanel = !showPanel"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      
      <!-- Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -end-1 w-5 h-5 bg-[var(--color-danger)] text-white text-xs font-medium rounded-full flex items-center justify-center"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Notification Panel -->
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="showPanel"
        class="absolute end-0 mt-2 w-80 rounded-xl bg-[var(--color-bg-primary)] border border-[var(--color-border)] shadow-xl z-50 overflow-hidden"
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-[var(--color-border)] flex items-center justify-between">
          <h3 class="font-semibold text-[var(--color-text-primary)]">Notifications</h3>
          <button
            v-if="notifications.length > 0"
            class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
            @click="markAllRead"
          >
            Mark all read
          </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-80 overflow-y-auto">
          <div
            v-if="notifications.length === 0"
            class="p-8 text-center"
          >
            <svg class="w-12 h-12 mx-auto text-[var(--color-text-muted)] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="text-sm text-[var(--color-text-muted)]">No notifications</p>
          </div>
          
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="px-4 py-3 border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg-tertiary)] cursor-pointer transition-colors"
            :class="{ 'bg-primary-50 dark:bg-primary-900/10': !notification.read }"
            @click="markRead(notification.id)"
          >
            <p class="text-sm text-[var(--color-text-primary)]">{{ notification.message }}</p>
            <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ notification.time }}</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Click outside to close -->
    <div
      v-if="showPanel"
      class="fixed inset-0 z-40"
      @click="showPanel = false"
    />
  </div>
</template>

<script setup lang="ts">
const showPanel = ref(false)

// Mock notifications (will be replaced with real data)
const notifications = ref([
  { id: 1, message: 'Your appointment ticket has been accepted', time: '5 minutes ago', read: false },
  { id: 2, message: 'New ticket assigned to you', time: '1 hour ago', read: false },
  { id: 3, message: 'Ticket #1234 has been completed', time: '2 hours ago', read: true },
])

const unreadCount = computed(() => 
  notifications.value.filter(n => !n.read).length
)

const markRead = (id: number) => {
  const notification = notifications.value.find(n => n.id === id)
  if (notification) notification.read = true
}

const markAllRead = () => {
  notifications.value.forEach(n => n.read = true)
}
</script>
