// Auth composable for RBAC-based authentication
export interface User {
    id: number
    name: string
    email: string
    phone?: string
    role: 'admin' | 'patient' | 'doctor' | 'maintenance' | 'reception'
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

export interface ApiUser {
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

    // Role redirect mapping
    const roleRoutes: Record<User['role'], string> = {
        admin: '/admin/dashboard',
        patient: '/patient',
        doctor: '/staff/doctor/tickets',
        maintenance: '/staff/maintenance/tickets',
        reception: '/staff/reception/tickets',
    }

    // Helper to extract role from API response
    function extractRole(apiUser: ApiUser): User['role'] {
        const roleName = apiUser.roles?.[0]?.name
        if (roleName && ['admin', 'patient', 'doctor', 'maintenance', 'reception'].includes(roleName)) {
            return roleName as User['role']
        }
        return 'patient' // Default role
    }

    // Helper to extract permissions from API response
    function extractPermissions(apiUser: ApiUser): string[] {
        if (apiUser.permissions) {
            return apiUser.permissions.map(p => typeof p === 'string' ? p : p.name)
        }
        return []
    }

    // Transform API user to local User type
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

    // Real API login
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

            // Store token in localStorage for persistence
            if (import.meta.client) {
                localStorage.setItem('auth_token', response.token)
            }

            return true
        } catch (err: any) {
            const message = err.data?.message || err.message || 'Login failed'
            error.value = message
            return false
        } finally {
            loading.value = false
        }
    }

    // Real API register (patient only)
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

            // Store token in localStorage for persistence
            if (import.meta.client) {
                localStorage.setItem('auth_token', response.token)
            }

            return true
        } catch (err: any) {
            const message = err.data?.message || err.data?.errors?.email?.[0] || err.message || 'Registration failed'
            error.value = message
            return false
        } finally {
            loading.value = false
        }
    }

    // Fetch current user from /api/me
    async function fetchUser(): Promise<boolean> {
        if (!token.value) {
            // Try to restore from localStorage
            if (import.meta.client) {
                const storedToken = localStorage.getItem('auth_token')
                if (storedToken) {
                    token.value = storedToken
                } else {
                    return false
                }
            } else {
                return false
            }
        }

        try {
            const apiUser = await $fetch<ApiUser>(`${config.public.apiBase}/me`, {
                headers: {
                    Authorization: `Bearer ${token.value}`,
                },
            })

            user.value = transformUser(apiUser)
            return true
        } catch (err) {
            // Token invalid or expired
            logout()
            return false
        }
    }

    // Logout
    async function logout(): Promise<void> {
        if (token.value) {
            try {
                await $fetch(`${config.public.apiBase}/logout`, {
                    method: 'POST',
                    headers: {
                        Authorization: `Bearer ${token.value}`,
                    },
                })
            } catch {
                // Ignore logout errors
            }
        }

        token.value = null
        user.value = null
        error.value = null

        if (import.meta.client) {
            localStorage.removeItem('auth_token')
        }

        router.push('/auth/login')
    }

    // Redirect based on role
    function redirectByRole(): void {
        if (!user.value) {
            router.push('/auth/login')
            return
        }
        const route = roleRoutes[user.value.role]
        router.push(route)
    }

    // Check if user has permission
    function hasPermission(permission: string): boolean {
        if (!user.value) return false
        if (user.value.role === 'admin') return true // Admin has all permissions
        return user.value.permissions.includes(permission)
    }

    // Check if user has role
    function hasRole(role: User['role'] | User['role'][]): boolean {
        if (!user.value) return false
        if (Array.isArray(role)) {
            return role.includes(user.value.role)
        }
        return user.value.role === role
    }

    // Staff activation
    async function activateAccount(activationToken: string, password: string, passwordConfirmation: string): Promise<boolean> {
        loading.value = true
        error.value = null

        try {
            const response = await $fetch<{ token: string; user: ApiUser }>(`${config.public.apiBase}/auth/activate`, {
                method: 'POST',
                body: {
                    token: activationToken,
                    password,
                    password_confirmation: passwordConfirmation,
                },
            })

            token.value = response.token
            user.value = transformUser(response.user)

            if (import.meta.client) {
                localStorage.setItem('auth_token', response.token)
            }

            return true
        } catch (err: any) {
            const message = err.data?.message || err.message || 'Activation failed'
            error.value = message
            return false
        } finally {
            loading.value = false
        }
    }

    return {
        user,
        token,
        loading,
        error,
        isAuthenticated,
        login,
        register,
        logout,
        fetchUser,
        redirectByRole,
        hasPermission,
        hasRole,
        activateAccount,
    }
}
