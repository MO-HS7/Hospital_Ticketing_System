/**
 * Debounced ref composable for search/filter inputs
 * Prevents API spam by delaying updates until user stops typing
 */
export function useDebouncedRef<T>(initialValue: T, delay = 300) {
    const value = ref(initialValue) as Ref<T>
    const debouncedValue = ref(initialValue) as Ref<T>
    let timeout: ReturnType<typeof setTimeout> | null = null

    watch(value, (newValue) => {
        if (timeout) {
            clearTimeout(timeout)
        }
        timeout = setTimeout(() => {
            debouncedValue.value = newValue
        }, delay)
    })

    return {
        value,
        debouncedValue,
    }
}

/**
 * Debounced async function wrapper
 * Prevents multiple rapid calls to the same async function
 */
export function useDebouncedFn<T extends (...args: any[]) => Promise<any>>(
    fn: T,
    delay = 300
) {
    let timeout: ReturnType<typeof setTimeout> | null = null
    let lastCall = 0

    return (...args: Parameters<T>): Promise<ReturnType<T>> => {
        return new Promise((resolve, reject) => {
            const now = Date.now()

            if (timeout) {
                clearTimeout(timeout)
            }

            // If called within delay, debounce
            if (now - lastCall < delay) {
                timeout = setTimeout(() => {
                    lastCall = Date.now()
                    fn(...args).then(resolve).catch(reject)
                }, delay)
            } else {
                // First call or after delay, execute immediately
                lastCall = now
                fn(...args).then(resolve).catch(reject)
            }
        })
    }
}
