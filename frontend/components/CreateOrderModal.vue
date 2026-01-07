<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="close">
    <div class="bg-[var(--color-bg-primary)] rounded-2xl shadow-2xl max-w-lg w-full p-6 animate-fadeIn">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">{{ $t('orders.create') }}</h3>
        <button @click="close" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Success State -->
      <div v-if="success" class="text-center py-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h4 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">{{ $t('orders.success') }}</h4>
        <p class="text-[var(--color-text-muted)] mb-2">
          {{ $t('orders.type') }}: <strong>{{ $t(`orders.${result?.order?.type}`) }}</strong>
        </p>
        <p class="text-sm text-[var(--color-text-secondary)]">
          {{ result?.order?.items?.length }} {{ $t('orders.items') }}
        </p>
        <div class="mt-6">
          <button @click="close" class="btn-primary px-4 py-2">{{ $t('common.close') }}</button>
        </div>
      </div>

      <!-- Form -->
      <div v-else>
        <!-- Type Select -->
        <div class="mb-4">
          <label class="label mb-2">{{ $t('orders.type') }} *</label>
          <select v-model="form.type" class="input w-full" :disabled="loading">
            <option value="">{{ $t('orders.selectType') }}</option>
            <option value="lab">{{ $t('orders.lab') }}</option>
            <option value="radiology">{{ $t('orders.radiology') }}</option>
            <option value="pharmacy">{{ $t('orders.pharmacy') }}</option>
          </select>
        </div>

        <!-- Items -->
        <div class="mb-4">
          <label class="label mb-2">{{ $t('orders.items') }} *</label>
          <textarea 
            v-model="itemsText" 
            class="input w-full" 
            rows="4"
            :placeholder="$t('orders.enterItems')"
            :disabled="loading"
          ></textarea>
          <p class="text-xs text-[var(--color-text-muted)] mt-1">
            {{ itemsArray.length }} {{ $t('orders.items') }}
          </p>
        </div>

        <!-- Instructions -->
        <div class="mb-6">
          <label class="label mb-2">{{ $t('orders.instructions') }}</label>
          <textarea 
            v-model="form.instructions" 
            class="input w-full" 
            rows="2"
            :placeholder="$t('orders.enterInstructions')"
            :disabled="loading"
          ></textarea>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 text-sm">
          {{ error }}
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
          <button @click="close" :disabled="loading" class="btn-outline px-4 py-2">
            {{ $t('common.cancel') }}
          </button>
          <button 
            @click="submit" 
            :disabled="loading || !canSubmit" 
            class="btn-primary px-4 py-2 flex items-center gap-2"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ loading ? $t('orders.loading') : $t('orders.submit') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  ticketId: number | string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', result: any): void
}>()

const config = useRuntimeConfig()
const { token } = useAuth()
const { t } = useI18n()

const loading = ref(false)
const success = ref(false)
const error = ref('')
const result = ref<any>(null)
const itemsText = ref('')

const form = ref({
  type: '',
  instructions: ''
})

const itemsArray = computed(() => 
  itemsText.value.split('\n').map(s => s.trim()).filter(Boolean)
)

const canSubmit = computed(() => 
  form.value.type && itemsArray.value.length > 0
)

const submit = async () => {
  if (!canSubmit.value) return
  
  loading.value = true
  error.value = ''

  try {
    const res = await $fetch(`${config.public.apiBase}/tickets/${props.ticketId}/orders`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: {
        type: form.value.type,
        items: itemsArray.value,
        instructions: form.value.instructions || null
      }
    })
    
    result.value = res
    success.value = true
    emit('success', res)
  } catch (e: any) {
    error.value = e.data?.message || t('orders.error')
  } finally {
    loading.value = false
  }
}

const close = () => {
  emit('close')
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.2s ease-out;
}
</style>
