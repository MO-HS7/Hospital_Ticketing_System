<template>
  <NuxtLayout name="admin">
    <div class="space-y-4 md:space-y-6">
      <!-- Page Header: Subtitle + Actions (Title from layout) -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <p class="text-sm text-slate-500 dark:text-white/50">{{ $t('departments.subtitle') }}</p>
          <span class="text-xs text-slate-500 dark:text-white/40 bg-slate-100 dark:bg-white/10 px-2.5 py-1 rounded-lg whitespace-nowrap">
            {{ pagination.total }} {{ $t('departments.total') }}
          </span>
        </div>
        <button @click="openCreateModal" class="h-10 px-4 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-medium flex items-center gap-2 transition shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          {{ $t('departments.add') }}
        </button>
      </div>

      <!-- Filters Card -->
      <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <!-- Search -->
          <div class="md:col-span-6">
            <input 
              v-model="filters.search" 
              type="text" 
              class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-4 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-white/40 shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition" 
              :placeholder="$t('common.search') + '...'" 
              @input="debouncedFetch" 
            />
          </div>
          <!-- Status Filter -->
          <div class="md:col-span-3">
            <select 
              v-model="filters.is_active" 
              class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-4 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none" 
              @change="fetchDepartments()"
            >
              <option value="">{{ $t('filters.all') }}</option>
              <option value="1">{{ $t('departments.active') }}</option>
              <option value="0">{{ $t('departments.inactive') }}</option>
            </select>
          </div>
          <!-- Sort Filter -->
          <div class="md:col-span-3">
            <select 
              v-model="filters.sort_by" 
              class="h-11 w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-black/20 px-4 text-sm text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition cursor-pointer appearance-none" 
              @change="fetchDepartments()"
            >
              <option value="sort_order">{{ $t('departments.sortOrder') }}</option>
              <option :value="locale === 'ar' ? 'name_ar' : 'name_en'">{{ $t('departments.name') }}</option>
              <option value="updated_at">{{ $t('tickets.updatedAt') }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3" v-if="stats">
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 text-center">
          <p class="text-2xl font-bold text-primary-600">{{ stats.total }}</p>
          <p class="text-sm text-slate-500 dark:text-white/50">{{ $t('departments.total') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 text-center">
          <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
          <p class="text-sm text-slate-500 dark:text-white/50">{{ $t('departments.active') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 text-center">
          <p class="text-2xl font-bold text-red-500">{{ stats.inactive }}</p>
          <p class="text-sm text-slate-500 dark:text-white/50">{{ $t('departments.inactive') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 p-4 text-center">
          <p class="text-2xl font-bold text-amber-600">{{ stats.with_tickets }}</p>
          <p class="text-sm text-slate-500 dark:text-white/50">{{ $t('departments.withTickets') }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
          <p class="mt-3 text-slate-500 dark:text-white/50">{{ $t('common.loading') }}</p>
        </div>
        <div v-else-if="departments.length === 0" class="py-16 px-4 text-center">
          <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
          <p class="mt-3 text-slate-500 dark:text-white/50 font-medium">{{ $t('departments.noDepartments') }}</p>
        </div>
        <table v-else class="w-full">
          <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
            <tr>
              <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider">{{ $t('departments.name') }}</th>
              <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider hidden md:table-cell">{{ $t('departments.sortOrder') }}</th>
              <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider">{{ $t('tickets.status') }}</th>
              <th class="px-4 py-3 text-start text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider hidden lg:table-cell">{{ $t('tickets.updatedAt') }}</th>
              <th class="px-4 py-3 text-end text-xs font-semibold text-slate-600 dark:text-white/60 uppercase tracking-wider">{{ $t('departments.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-white/5">
            <tr v-for="dept in departments" :key="dept.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition">
              <td class="px-4 py-3.5 text-sm font-medium text-slate-900 dark:text-white">{{ getLocalizedName(dept) }}</td>
              <td class="px-4 py-3.5 text-sm text-slate-600 dark:text-white/60 hidden md:table-cell">{{ dept.sort_order }}</td>
              <td class="px-4 py-3.5">
                <span :class="dept.is_active ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'" class="px-2.5 py-1 rounded-full text-xs font-medium">
                  {{ dept.is_active ? $t('departments.active') : $t('departments.inactive') }}
                </span>
              </td>
              <td class="px-4 py-3.5 text-sm text-slate-500 dark:text-white/50 hidden lg:table-cell">{{ formatDate(dept.updated_at) }}</td>
              <td class="px-4 py-3.5 text-end">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openEditModal(dept)" class="h-8 w-8 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 flex items-center justify-center text-slate-500 dark:text-white/50 transition" :title="$t('common.edit')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </button>
                  <button @click="openDeleteModal(dept)" class="h-8 w-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 flex items-center justify-center text-red-500 transition" :title="$t('common.delete')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="p-4 border-t border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50 dark:bg-white/5">
          <p class="text-sm text-slate-500 dark:text-white/50">
            {{ $t('departments.showing') }} {{ pagination.from }}–{{ pagination.to }} {{ $t('departments.of') }} {{ pagination.total }}
          </p>
          <div class="flex gap-2">
            <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="h-9 px-3 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition">{{ $t('common.previous') }}</button>
            <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="h-9 px-3 rounded-lg text-sm font-medium text-slate-600 dark:text-white/60 hover:bg-slate-200 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition">{{ $t('common.next') }}</button>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal">
          <div class="fixed inset-0 bg-black/50" @click="closeModal" />
          <div class="relative bg-[var(--color-bg-primary)] rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-[var(--color-bg-primary)] p-6 border-b border-[var(--color-border)]">
              <h2 class="text-xl font-semibold">{{ editingDept ? $t('departments.edit') : $t('departments.add') }}</h2>
              <button @click="closeModal" class="absolute top-4 end-4 btn-ghost p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <form @submit.prevent="saveDepartment" class="p-6 space-y-4">
              <div v-if="formError" class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">{{ formError }}</div>
              
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1.5">{{ $t('departments.nameEn') }} *</label>
                  <input v-model="form.name_en" type="text" class="input w-full" required minlength="2" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1.5">{{ $t('departments.nameAr') }} *</label>
                  <input v-model="form.name_ar" type="text" class="input w-full" dir="rtl" required minlength="2" />
                </div>
              </div>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1.5">{{ $t('departments.descriptionEn') }}</label>
                  <textarea v-model="form.description_en" class="input w-full" rows="3"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1.5">{{ $t('departments.descriptionAr') }}</label>
                  <textarea v-model="form.description_ar" class="input w-full" dir="rtl" rows="3"></textarea>
                </div>
              </div>

              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1.5">{{ $t('departments.iconKey') }}</label>
                  <input v-model="form.icon_key" type="text" class="input w-full" placeholder="e.g. cardiology" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1.5">{{ $t('departments.sortOrder') }}</label>
                  <input v-model.number="form.sort_order" type="number" class="input w-full" min="0" />
                </div>
              </div>

              <div class="flex items-center gap-3">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600" />
                <label for="is_active" class="text-sm">{{ $t('departments.isActive') }}</label>
              </div>

              <div class="flex gap-3 pt-4 border-t border-[var(--color-border)]">
                <button type="button" @click="closeModal" class="btn-secondary flex-1">{{ $t('common.cancel') }}</button>
                <button type="submit" class="btn-primary flex-1" :disabled="saving">
                  <span v-if="saving" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  </span>
                  <span v-else>{{ $t('common.save') }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50" @click="closeDeleteModal" />
          <div class="relative bg-[var(--color-bg-primary)] rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-semibold text-[var(--color-text-primary)]">{{ $t('departments.deleteConfirm') }}</h2>
            <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ $t('departments.deleteWarning') }}</p>
            <p class="mt-2 font-medium">{{ deletingDept ? getLocalizedName(deletingDept) : '' }}</p>
            <div v-if="deleteError" class="mt-4 p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">{{ deleteError }}</div>
            <div class="flex gap-3 mt-6">
              <button @click="closeDeleteModal" class="btn-secondary flex-1">{{ $t('common.cancel') }}</button>
              <button @click="deleteDepartment" class="btn-primary bg-red-600 hover:bg-red-700 flex-1" :disabled="deleting">
                <span v-if="deleting">{{ $t('common.loading') }}</span>
                <span v-else>{{ $t('common.delete') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

interface Department {
  id: string
  name_en: string
  name_ar: string
  slug: string
  description_en: string | null
  description_ar: string | null
  icon_key: string | null
  is_active: boolean
  sort_order: number
  created_at: string
  updated_at: string
}

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()

const getLocalizedName = (dept: Department) => locale.value === 'ar' ? dept.name_ar : dept.name_en

const loading = ref(true)
const departments = ref<Department[]>([])
const stats = ref<{ total: number; active: number; inactive: number; with_tickets: number } | null>(null)
const pagination = ref({ current_page: 1, last_page: 1, from: 0, to: 0, total: 0 })
const filters = reactive({ search: '', is_active: '', sort_by: 'sort_order' })

const showModal = ref(false)
const editingDept = ref<Department | null>(null)
const saving = ref(false)
const formError = ref('')
const form = reactive({
  name_en: '',
  name_ar: '',
  description_en: '',
  description_ar: '',
  icon_key: '',
  sort_order: 0,
  is_active: true,
})

const showDeleteModal = ref(false)
const deletingDept = ref<Department | null>(null)
const deleting = ref(false)
const deleteError = ref('')

let debounceTimer: ReturnType<typeof setTimeout>
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchDepartments, 300)
}

const fetchDepartments = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '20', sort_by: filters.sort_by, sort_dir: 'asc' })
    if (filters.search) params.append('search', filters.search)
    if (filters.is_active !== '') params.append('is_active', filters.is_active)
    
    const res = await $fetch<any>(`${config.public.apiBase}/admin/departments?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    departments.value = res.data
    pagination.value = { current_page: res.current_page, last_page: res.last_page, from: res.from || 0, to: res.to || 0, total: res.total }
  } catch (e: any) {
    console.error('Failed to fetch departments:', e)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const res = await $fetch<{ data: typeof stats.value }>(`${config.public.apiBase}/admin/departments/stats`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    stats.value = res.data
  } catch (e) {
    console.error('Failed to fetch stats:', e)
  }
}

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) fetchDepartments(page)
}

const formatDate = (date: string) => new Date(date).toLocaleDateString()

const openCreateModal = () => {
  editingDept.value = null
  Object.assign(form, { name_en: '', name_ar: '', description_en: '', description_ar: '', icon_key: '', sort_order: 0, is_active: true })
  formError.value = ''
  showModal.value = true
}

const openEditModal = (dept: Department) => {
  editingDept.value = dept
  Object.assign(form, {
    name_en: dept.name_en,
    name_ar: dept.name_ar,
    description_en: dept.description_en || '',
    description_ar: dept.description_ar || '',
    icon_key: dept.icon_key || '',
    sort_order: dept.sort_order,
    is_active: dept.is_active,
  })
  formError.value = ''
  showModal.value = true
}

const closeModal = () => { showModal.value = false; editingDept.value = null }

const saveDepartment = async () => {
  saving.value = true
  formError.value = ''
  try {
    const url = editingDept.value
      ? `${config.public.apiBase}/admin/departments/${editingDept.value.id}`
      : `${config.public.apiBase}/admin/departments`
    const method = editingDept.value ? 'PUT' : 'POST'
    
    await $fetch(url, {
      method,
      headers: { Authorization: `Bearer ${token.value}` },
      body: form,
    })
    closeModal()
    fetchDepartments(pagination.value.current_page)
    fetchStats()
  } catch (e: any) {
    formError.value = e.data?.message || e.message || 'Failed to save department'
  } finally {
    saving.value = false
  }
}

const openDeleteModal = (dept: Department) => {
  deletingDept.value = dept
  deleteError.value = ''
  showDeleteModal.value = true
}

const closeDeleteModal = () => { showDeleteModal.value = false; deletingDept.value = null }

const deleteDepartment = async () => {
  if (!deletingDept.value) return
  deleting.value = true
  deleteError.value = ''
  try {
    await $fetch(`${config.public.apiBase}/admin/departments/${deletingDept.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token.value}` },
    })
    closeDeleteModal()
    fetchDepartments(pagination.value.current_page)
    fetchStats()
  } catch (e: any) {
    deleteError.value = e.data?.message || e.message || 'Failed to delete department'
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchDepartments()
  fetchStats()
})
</script>
