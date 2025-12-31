<template>
  <NuxtLayout name="staff">
    <div class="max-w-3xl mx-auto">
      <NuxtLink to="/staff/doctor/tickets" class="flex items-center gap-2 text-[var(--color-text-secondary)] mb-4">
        <svg class="w-5 h-5 icon-flip" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Tickets
      </NuxtLink>
      <h1 class="text-2xl font-bold text-[var(--color-text-primary)] mb-6">Report Maintenance Issue</h1>

      <div class="card p-4 mb-6 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
        <p class="text-sm text-amber-800 dark:text-amber-300">Use the chatbot below to describe the issue or create a ticket manually.</p>
      </div>

      <!-- Chat -->
      <div class="card overflow-hidden flex flex-col h-[400px]">
        <div class="p-3 border-b border-[var(--color-border)] bg-[var(--color-bg-tertiary)]">
          <h2 class="font-semibold">Maintenance Assistant</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-for="msg in messages" :key="msg.id" :class="['max-w-[80%] p-3 rounded-2xl text-sm', msg.role === 'user' ? 'message-user bg-primary-600 text-white' : 'message-bot bg-[var(--color-bg-tertiary)]']">
            {{ msg.content }}
          </div>
        </div>
        <div class="p-3 border-t border-[var(--color-border)]">
          <form @submit.prevent="send" class="flex gap-2">
            <input v-model="input" class="input flex-1" placeholder="Describe the issue..." />
            <button type="submit" class="btn-primary px-4">Send</button>
          </form>
        </div>
      </div>

      <!-- Manual Form -->
      <div class="card p-6 mt-6">
        <h3 class="font-semibold mb-4">Or Create Manually</h3>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Issue Type</label>
            <select v-model="form.type" class="input" required>
              <option value="">Select type</option>
              <option value="electrical">Electrical</option>
              <option value="equipment">Equipment</option>
              <option value="hvac">HVAC</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="form.description" class="input" rows="3" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Priority</label>
            <select v-model="form.priority" class="input">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <button type="submit" class="btn-primary w-full">Submit Ticket</button>
        </form>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })
const router = useRouter()
const input = ref('')
const messages = ref([{ id: 1, role: 'bot', content: 'Hello! Describe the maintenance issue and I will help create a ticket.' }])
const form = reactive({ type: '', description: '', priority: 'medium' })

const send = () => {
  if (!input.value.trim()) return
  messages.value.push({ id: Date.now(), role: 'user', content: input.value })
  messages.value.push({ id: Date.now() + 1, role: 'bot', content: 'Got it! I have created a maintenance ticket for you. Ticket #M-1001' })
  input.value = ''
}
const submit = () => router.push('/staff/doctor/tickets')
</script>
