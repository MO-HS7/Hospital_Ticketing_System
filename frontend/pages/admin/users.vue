<template>
  <NuxtLayout name="admin">
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('users.title') }}</h1>
          <p class="text-[var(--color-text-muted)]">{{ $t('users.subtitle') }}</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ $t('users.createStaff') }}
        </button>
      </div>

      <!-- Role Tabs -->
      <div class="flex flex-wrap gap-2 border-b border-[var(--color-border)] pb-2">
        <button
          v-for="tab in roleTabs"
          :key="tab.value"
          @click="selectedRole = tab.value"
          class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="selectedRole === tab.value
            ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
            : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-tertiary)]'"
        >
          {{ tab.label }}
          <span v-if="tab.count !== undefined" class="ms-1.5 px-1.5 py-0.5 text-xs rounded-full bg-[var(--color-bg-tertiary)]">{{ tab.count }}</span>
        </button>
      </div>

      <!-- Search -->
      <div class="flex gap-4">
        <div class="flex-1 relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('users.searchPlaceholder')"
            class="input ps-10"
            @input="debouncedFetch"
          />
          <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-5 h-5 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="p-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm">
        {{ error }}
      </div>

      <!-- Users Table -->
      <div class="card overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <svg class="animate-spin w-8 h-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
          <p class="mt-2 text-[var(--color-text-muted)]">{{ $t('common.loading') }}</p>
        </div>

        <table v-else class="w-full">
          <thead class="bg-[var(--color-bg-tertiary)]">
            <tr>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('users.name') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium hidden md:table-cell">{{ $t('users.email') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium hidden lg:table-cell">{{ $t('users.role') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium hidden lg:table-cell">{{ $t('users.department') }}</th>
              <th class="px-4 py-3 text-start text-sm font-medium">{{ $t('users.status') }}</th>
              <th class="px-4 py-3 text-end text-sm font-medium">{{ $t('departments.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--color-border)]">
            <tr v-for="user in users" :key="user.id" class="hover:bg-[var(--color-bg-tertiary)]">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0">
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-400">{{ getInitials(user.name) }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium truncate">{{ user.name }}</p>
                    <p class="text-xs text-[var(--color-text-muted)] truncate md:hidden">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)] hidden md:table-cell">{{ user.email }}</td>
              <td class="px-4 py-3 hidden lg:table-cell">
                <span class="badge" :class="getRoleBadgeClass(getUserRole(user))">{{ $t(`roles.${getUserRole(user)}`) }}</span>
              </td>
              <td class="px-4 py-3 text-sm text-[var(--color-text-secondary)] hidden lg:table-cell">{{ getDepartmentName(user) }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
                  :class="user.is_active !== false
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active !== false ? 'bg-green-500' : 'bg-gray-400'" />
                  {{ user.is_active !== false ? $t('users.active') : $t('users.inactive') }}
                </span>
              </td>
              <td class="px-4 py-3 text-end">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="user.is_active === false"
                    @click="activateUser(user)"
                    class="btn-ghost text-xs text-green-600 dark:text-green-400"
                    :disabled="actionLoading === user.id"
                  >
                    {{ $t('users.activate') }}
                  </button>
                  <button
                    v-else
                    @click="deactivateUser(user)"
                    class="btn-ghost text-xs text-amber-600 dark:text-amber-400"
                    :disabled="actionLoading === user.id"
                  >
                    {{ $t('users.deactivate') }}
                  </button>
                  <button @click="openEditModal(user)" class="btn-ghost text-xs">{{ $t('common.edit') }}</button>
                </div>
              </td>
            </tr>
            <tr v-if="users.length === 0 && !loading">
              <td colspan="6" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ $t('common.noResults') }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="p-4 border-t border-[var(--color-border)] flex items-center justify-between">
          <span class="text-sm text-[var(--color-text-muted)]">{{ $t('departments.showing') }} {{ users.length }} {{ $t('departments.of') }} {{ total }}</span>
          <div class="flex gap-2">
            <button @click="prevPage" :disabled="currentPage === 1" class="btn-ghost text-sm">{{ $t('common.previous') }}</button>
            <button @click="nextPage" :disabled="currentPage >= totalPages" class="btn-ghost text-sm">{{ $t('common.next') }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal v-model="showModal" :title="editingUser ? $t('users.editUser') : $t('users.createStaff')" size="lg">
      <form @submit.prevent="submitForm" class="space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('users.name') }} *</label>
            <input v-model="form.name" type="text" class="input" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('users.email') }} *</label>
            <input v-model="form.email" type="email" class="input" :disabled="!!editingUser" required />
          </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">{{ $t('users.phone') }}</label>
            <input v-model="form.phone" type="tel" class="input" />
          </div>
          <div v-if="!editingUser">
            <label class="block text-sm font-medium mb-1">{{ $t('users.role') }} *</label>
            <select v-model="form.role" class="input" required>
              <option value="">{{ $t('common.select') }}</option>
              <option value="doctor">{{ $t('roles.doctor') }}</option>
              <option value="maintenance">{{ $t('roles.maintenance') }}</option>
              <option value="reception">{{ $t('roles.reception') }}</option>
              <option value="lab_technician">{{ $t('roles.lab_technician') }}</option>
              <option value="radiologist">{{ $t('roles.radiologist') }}</option>
              <option value="pharmacist">{{ $t('roles.pharmacist') }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ $t('users.department') }} *</label>
          <select v-model="form.department_id" class="input" required>
            <option value="">{{ $t('common.select') }}</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ getDeptName(dept) }}</option>
          </select>
          <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ $t('users.departmentRequired') }}</p>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button @click="showModal = false" class="btn-ghost">{{ $t('common.cancel') }}</button>
          <button @click="submitForm" class="btn-primary" :disabled="formLoading || !canSubmit">
            <svg v-if="formLoading" class="animate-spin w-4 h-4 me-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            {{ editingUser ? $t('common.save') : $t('users.createStaff') }}
          </button>
        </div>
      </template>
    </Modal>

    <!-- Activation Link Modal -->
    <Modal v-model="showLinkModal" :title="$t('users.activationLink')" size="md">
      <div class="space-y-4">
        <p class="text-sm text-[var(--color-text-secondary)]">{{ $t('users.activationLinkDesc') }}</p>
        <div class="p-3 bg-[var(--color-bg-tertiary)] rounded-lg break-all text-sm font-mono">
          {{ activationLink }}
        </div>
        <button @click="copyLink" class="btn-primary w-full">
          {{ copied ? $t('users.copied') : $t('users.copyLink') }}
        </button>
      </div>
    </Modal>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

interface UserRole { name: string }
interface UserDepartment { id: string; name_en: string; name_ar: string }
interface User {
  id: number
  name: string
  email: string
  phone?: string | null
  is_active?: boolean
  department_id?: string | null
  department?: UserDepartment | null
  roles?: UserRole[]
}

interface PaginatedUsers {
  data: User[]
  current_page: number
  last_page: number
  total: number
}

const { t, locale } = useI18n()
const config = useRuntimeConfig()
const { token } = useAuth()
const { departments, fetchDepartments, getName: getDeptName } = useDepartments()

const loading = ref(false)
const error = ref<string | null>(null)
const users = ref<User[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const total = ref(0)

const selectedRole = ref<string>('')
const searchQuery = ref('')
let searchTimer: ReturnType<typeof setTimeout>

const showModal = ref(false)
const editingUser = ref<User | null>(null)
const formLoading = ref(false)
const actionLoading = ref<number | null>(null)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  role: '',
  department_id: '',
})

const showLinkModal = ref(false)
const activationLink = ref('')
const copied = ref(false)

const roleTabs = computed(() => [
  { value: '', label: t('filters.all') },
  { value: 'admin', label: t('roles.admin') },
  { value: 'doctor', label: t('staffTypes.doctor') },
  { value: 'maintenance', label: t('staffTypes.maintenance') },
  { value: 'reception', label: t('staffTypes.reception') },
  { value: 'patient', label: t('roles.patient') },
])

const canSubmit = computed(() => {
  if (editingUser.value) {
    return form.name.trim()
  }
  return form.name.trim() && form.email.trim() && form.role && form.department_id
})

const fetchUsers = async () => {
  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    params.set('page', String(currentPage.value))
    if (selectedRole.value) params.set('role', selectedRole.value)
    if (searchQuery.value.trim()) params.set('search', searchQuery.value.trim())

    const res = await $fetch<PaginatedUsers>(`${config.public.apiBase}/users?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })

    users.value = res.data
    totalPages.value = res.last_page
    total.value = res.total
  } catch (e: any) {
    error.value = e.data?.message || e.message || 'Failed to fetch users'
  } finally {
    loading.value = false
  }
}

const debouncedFetch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchUsers()
  }, 300)
}

watch(selectedRole, () => {
  currentPage.value = 1
  fetchUsers()
})

const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchUsers() } }
const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchUsers() } }

onMounted(async () => {
  await fetchDepartments()
  await fetchUsers()
})

const getInitials = (name: string) => name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
const getUserRole = (user: User) => user.roles?.[0]?.name || 'patient'
const getDepartmentName = (user: User) => {
  if (!user.department) return '-'
  return locale.value === 'ar' ? user.department.name_ar : user.department.name_en
}

const getRoleBadgeClass = (role: string) => {
  const classes: Record<string, string> = {
    admin: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
    doctor: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    maintenance: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
    reception: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400',
    patient: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
  }
  return classes[role] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.role = ''
  form.department_id = ''
  editingUser.value = null
}

const openCreateModal = () => {
  resetForm()
  showModal.value = true
}

const openEditModal = (user: User) => {
  editingUser.value = user
  form.name = user.name
  form.email = user.email
  form.phone = user.phone || ''
  form.role = getUserRole(user)
  form.department_id = user.department_id || ''
  showModal.value = true
}

const submitForm = async () => {
  if (!canSubmit.value) return

  formLoading.value = true
  error.value = null

  try {
    if (editingUser.value) {
      await $fetch(`${config.public.apiBase}/users/${editingUser.value.id}`, {
        method: 'PUT',
        headers: { Authorization: `Bearer ${token.value}` },
        body: {
          name: form.name,
          phone: form.phone || null,
          department_id: form.department_id || null,
        },
      })
      showModal.value = false
      resetForm()
      await fetchUsers()
    } else {
      const res = await $fetch<{ user: User; activation_link: string }>(`${config.public.apiBase}/users`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` },
        body: {
          name: form.name,
          email: form.email,
          phone: form.phone || null,
          role: form.role,
          department_id: form.department_id || null,
        },
      })
      showModal.value = false
      resetForm()
      activationLink.value = res.activation_link
      showLinkModal.value = true
      await fetchUsers()
    }
  } catch (e: any) {
    error.value = e.data?.message || e.message || 'Failed to save user'
  } finally {
    formLoading.value = false
  }
}

const activateUser = async (user: User) => {
  actionLoading.value = user.id
  try {
    await $fetch(`${config.public.apiBase}/users/${user.id}/activate`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
    })
    await fetchUsers()
  } catch (e: any) {
    error.value = e.data?.message || 'Failed to activate user'
  } finally {
    actionLoading.value = null
  }
}

const deactivateUser = async (user: User) => {
  actionLoading.value = user.id
  try {
    await $fetch(`${config.public.apiBase}/users/${user.id}`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${token.value}` },
      body: { is_active: false },
    })
    await fetchUsers()
  } catch (e: any) {
    error.value = e.data?.message || 'Failed to deactivate user'
  } finally {
    actionLoading.value = null
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(activationLink.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {}
}
</script>
