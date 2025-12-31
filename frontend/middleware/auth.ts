// Auth middleware - blocks protected routes if not authenticated
export default defineNuxtRouteMiddleware((to, _from) => {
    const { isAuthenticated } = useAuth()

    if (!isAuthenticated.value) {
        return navigateTo(`/auth/login?redirect=${encodeURIComponent(to.fullPath)}`)
    }
})
