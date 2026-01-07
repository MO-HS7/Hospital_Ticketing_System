<template>
  <NuxtLayout name="patient">
    <div v-if="loading" class="space-y-4 max-w-3xl mx-auto">
      <Skeleton class="h-8 w-1/3 mb-4" />
      <Skeleton class="h-64 w-full rounded-2xl" />
    </div>

    <div v-else-if="!ticket" class="max-w-3xl mx-auto py-12">
      <EmptyState 
        :title="$t('tickets.noTickets')" 
        :description="$t('common.error')"
      >
        <template #action>
          <button @click="$router.back()" class="btn-primary">{{ $t('common.back') }}</button>
        </template>
      </EmptyState>
    </div>

    <div v-else class="max-w-3xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <button @click="$router.back()" class="text-sm text-[var(--color-text-muted)] hover:text-primary-600 mb-2 flex items-center gap-1">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            {{ $t('common.back') }}
          </button>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">
            #{{ ticket.id }} - {{ ticket.subject }}
          </h1>
        </div>
        <Badge :color="getStatusColor(ticket.status)" :label="$t(`ticketStatus.${ticket.status}`)" dot />
      </div>

      <!-- Main Info -->
      <div class="card p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-medium text-[var(--color-text-muted)] mb-1">{{ $t('tickets.department') }}</h3>
          <p class="font-semibold">{{ locale === 'ar' ? ticket.department?.name_ar : ticket.department?.name_en }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-[var(--color-text-muted)] mb-1">{{ $t('tickets.doctor') }}</h3>
          <p class="font-semibold">{{ ticket.assignee?.name || '-' }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-[var(--color-text-muted)] mb-1">{{ $t('tickets.createdAt') }}</h3>
          <p class="font-semibold">{{ formatDate(ticket.created_at) }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-[var(--color-text-muted)] mb-1">{{ $t('tickets.updatedAt') }}</h3>
          <p class="font-semibold">{{ formatDate(ticket.updated_at) }}</p>
        </div>
      </div>

      <!-- Description -->
      <div class="card p-6">
        <h3 class="font-bold text-lg mb-4">{{ $t('tickets.description') }}</h3>
        <p class="text-[var(--color-text-primary)] whitespace-pre-wrap leading-relaxed">
          {{ ticket.description }}
        </p>
      </div>

      <!-- Notes / Timeline Placeholder (if api supported) -->
      <!-- Only showing status history if available, else simple block -->
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import Skeleton from '~/components/ui/Skeleton.vue'
import EmptyState from '~/components/ui/EmptyState.vue'
import Badge from '~/components/ui/Badge.vue'

definePageMeta({ layout: false, middleware: ['auth'] })

const route = useRoute()
const config = useRuntimeConfig()
const { token } = useAuth()
const { t, locale } = useI18n()

const loading = ref(true)
const ticket = ref<any>(null)

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending': return 'warning'
    case 'assigned': return 'primary'
    case 'awaiting_payment': return 'warning'
    case 'in_progress': return 'primary'
    case 'completed': return 'success'
    case 'closed_late': return 'warning'
    case 'overdue': return 'danger'
    default: return 'gray'
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString(locale.value, {
    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await $fetch<{ data: any }>(`${config.public.apiBase}/tickets/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    ticket.value = res.data || res // Handle if res itself is data or wrapper
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>
