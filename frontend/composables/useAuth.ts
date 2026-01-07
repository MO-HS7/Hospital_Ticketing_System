export interface User {
    id: number
    name: string
    email: string
    phone?: string
    role: 'admin' | 'patient' | 'doctor' | 'maintenance' | 'reception' | 'lab_technician' | 'radiologist' | 'pharmacist'
    permissions: string[]
    roles?: Array<{ name: string }>
}

export interface LoginCredentials {
    email: string
    password: string
}

export interface RegisterData {
    name: string
    email: string
    phone: string
    password: string
    password_confirmation: string
}

interface ApiUser {
    id: number
    name: string
    email: string
    phone?: string
    roles?: Array<{ name: string }>
    permissions?: Array<{ name: string }>
}

export const useAuth = () => {
    const user = useState<User | null>('auth_user', () => null)
    const token = useState<string | null>('auth_token', () => null)
    const loading = useState<boolean>('auth_loading', () => false)
    const error = useState<string | null>('auth_error', () => null)
    const router = useRouter()
    const config = useRuntimeConfig()

    const isAuthenticated = computed(() => !!user.value && !!token.value)

    const roleRoutes: Record<User['role'], string> = {
        admin: '/admin/dashboard',
        patient: '/patient',
        doctor: '/staff/doctor/tickets',
        maintenance: '/staff/maintenance/tickets',
        reception: '/staff/reception/tickets',
        lab_technician: '/staff/lab/orders',
        radiologist: '/staff/radiology/orders',
        pharmacist: '/staff/pharmacy/orders',
    }

    function extractRole(apiUser: ApiUser): User['role'] {
        const roleName = apiUser.roles?.[0]?.name
        if (roleName && ['admin', 'patient', 'doctor', 'maintenance', 'reception', 'lab_technician', 'radiologist', 'pharmacist'].includes(roleName)) {
            return roleName as User['role']
        }
        return 'patient'
    }

    function extractPermissions(apiUser: ApiUser): string[] {
        return apiUser.permissions?.map(p => typeof p === 'string' ? p : p.name) || []
    }

    function transformUser(apiUser: ApiUser): User {
        return {
            id: apiUser.id,
            name: apiUser.name,
            email: apiUser.email,
            phone: apiUser.phone,
            role: extractRole(apiUser),
            permissions: extractPermissions(apiUser),
            roles: apiUser.roles,
        }
    }

    async function login(credentials: LoginCredentials): Promise<boolean> {
        loading.value = true
        error.value = null
        try {
            const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/auth/login`, {
                method: 'POST',
                body: credentials,
            })
            token.value = response.token
            user.value = transformUser(response.user)
            if (import.meta.client) {
                localStorage.setItem('auth_token', response.token)
            }
            return true
        } catch (err: any) {
            error.value = err.data?.message || err.message || 'Login failed'
            return false
        } finally {
            loading.value = false
        }
    }

    async function register(data: RegisterData): Promise<boolean> {
        loading.value = true
        error.value = null
        try {
            const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/auth/register`, {
                method: 'POST',
                body: data,
            })
            token.value = response.token
            user.value = transformUser(response.user)
            if (import.meta.client) {
                localStorage.setItem('auth_token', response.token)
            }
            return true
        } catch (err: any) {
            error.value = err.data?.message || err.data?.errors?.email?.[0] || 'Registration failed'
            return false
        } finally {
            loading.value = false
        }
    }

    async function fetchUser(): Promise<boolean> {
        if (!token.value && import.meta.client) {
            const storedToken = localStorage.getItem('auth_token')
            if (storedToken) token.value = storedToken
            else return false
        }
        if (!token.value) return false
        try {
            const apiUser = await $fetch<ApiUser>(`${config.public.apiBase}/me`, {
                headers: { Authorization: `Bearer ${token.value}` },
            })
            user.value = transformUser(apiUser)
            return true
        } catch {
            logout()
            return false
        }
    }

    async function logout(): Promise<void> {
        if (token.value) {
            try {
                await $fetch(`${config.public.apiBase}/logout`, {
                    method: 'POST',
                    headers: { Authorization: `Bearer ${token.value}` },
                })
            } catch {}
        }
        token.value = null
        user.value = null
        error.value = null
        if (import.meta.client) localStorage.removeItem('auth_token')
        router.push('/auth/login')
    }

    function redirectByRole(): void {
        if (!user.value) {
            router.push('/auth/login')
            return
        }
        router.push(roleRoutes[user.value.role])
    }

    function hasPermission(permission: string): boolean {
        if (!user.value) return false
        if (user.value.role === 'admin') return true
        return user.value.permissions.includes(permission)
    }

    function hasRole(role: User['role'] | User['role'][]): boolean {
        if (!user.value) return false
        return Array.isArray(role) ? role.includes(user.value.role) : user.value.role === role
    }

    async function activateAccount(activationToken: string, password: string, passwordConfirmation: string): Promise<boolean> {
        loading.value = true
        error.value = null
        try {
            const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/auth/activate`, {
                method: 'POST',
                body: { token: activationToken, password, password_confirmation: passwordConfirmation },
            })
            token.value = response.token
            user.value = transformUser(response.user)
            if (import.meta.client) localStorage.setItem('auth_token', response.token)
            return true
        } catch (err: any) {
            error.value = err.data?.message || 'Activation failed'
            return false
        } finally {
            loading.value = false
        }
    }

    return {
        user, token, loading, error, isAuthenticated,
        login, register, logout, fetchUser, redirectByRole,
        hasPermission, hasRole, activateAccount,
    }
}
