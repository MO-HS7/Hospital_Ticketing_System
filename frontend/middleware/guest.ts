// Guest middleware - redirects authenticated users away from auth pages
export default defineNuxtRouteMiddleware((_to, _from) => {
    const { isAuthenticated, redirectByRole } = useAuth()

    if (isAuthenticated.value) {
        redirectByRole()
        return abortNavigation()
    }
})
