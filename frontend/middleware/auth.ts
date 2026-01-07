// Auth middleware - blocks protected routes if not authenticated
export default defineNuxtRouteMiddleware(async (to, _from) => {
    if (process.server) return

    const { isAuthenticated, fetchUser } = useAuth()

    if (!isAuthenticated.value) {
        const ok = await fetchUser()
        if (!ok) {
            return navigateTo(`/auth/login?redirect=${encodeURIComponent(to.fullPath)}`)
        }
    }
})
