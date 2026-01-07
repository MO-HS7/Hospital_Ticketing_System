// Guest middleware - redirects authenticated users away from auth pages
export default defineNuxtRouteMiddleware(async (_to, _from) => {
    if (process.server) return

    const { isAuthenticated, fetchUser, redirectByRole } = useAuth()

    if (!isAuthenticated.value) {
        await fetchUser()
    }

    if (isAuthenticated.value) {
        redirectByRole()
        return abortNavigation()
    }
})
