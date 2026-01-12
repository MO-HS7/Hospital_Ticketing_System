import { ref, reactive, computed, watch } from 'vue'
import type { Ref, ComputedRef } from 'vue'

interface AuditActor {
    id: number
    name: string
    email?: string
    role?: string
}

interface AuditTicket {
    id: number
    number: string
    subject: string
    type: string
    priority?: string
    status?: string
    department_id: string
    department_name_en?: string
    department_name_ar?: string
    department_slug?: string
    patient_id?: number
    patient_name?: string
}

export interface AuditEvent {
    id: number
    auditable_type?: string
    auditable_id?: number
    event_type: string
    created_at: string
    actor: AuditActor | null
    ticket: AuditTicket | null
    department_id?: number
    department?: {
        id: number
        name_en: string
        name_ar: string
    } | null
    summary_key: string
    summary_params: Record<string, any>
    old_value: Record<string, any> | null
    new_value: Record<string, any> | null
    meta: Record<string, any>
    // Phase 2: Request metadata
    ip_address?: string | null
    user_agent?: string | null
    route?: string | null
    method?: string | null
    request_id?: string | null
}

interface AuditFilters {
    q: string
    eventType: string
    from: string
    to: string
    actorId: string
    role: string
    departmentId: string
    hasDiff: boolean
}

interface AuditMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface UseAuditLogReturn {
    events: Ref<AuditEvent[]>
    loading: Ref<boolean>
    error: Ref<string | null>
    meta: Ref<AuditMeta>
    filters: AuditFilters
    hasActiveFilters: ComputedRef<boolean>
    fetchEvents: () => Promise<void>
    applyFilters: () => void
    resetFilters: () => void
    nextPage: () => void
    prevPage: () => void
    setPerPage: (perPage: number) => void
}

export function useAuditLog(): UseAuditLogReturn {
    const config = useRuntimeConfig()
    const { token } = useAuth()

    const events = ref<AuditEvent[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)
    const meta = ref<AuditMeta>({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    })

    const filters = reactive<AuditFilters>({
        q: '',
        eventType: '',
        from: '',
        to: '',
        actorId: '',
        role: '',
        departmentId: '',
        hasDiff: false,
    })

    const hasActiveFilters = computed(() =>
        !!(filters.q || filters.eventType || filters.from || filters.to ||
            filters.actorId || filters.role || filters.departmentId || filters.hasDiff)
    )

    let debounceTimer: ReturnType<typeof setTimeout> | null = null

    const fetchEvents = async () => {
        loading.value = true
        error.value = null

        try {
            const params = new URLSearchParams()
            params.set('page', String(meta.value.current_page))
            params.set('per_page', String(meta.value.per_page))

            if (filters.q) params.set('q', filters.q)
            if (filters.eventType) params.set('event_type', filters.eventType)
            if (filters.from) params.set('from', filters.from)
            if (filters.to) params.set('to', filters.to)
            if (filters.actorId) params.set('actor_id', filters.actorId)
            if (filters.role) params.set('role', filters.role)
            if (filters.departmentId) params.set('department_id', filters.departmentId)
            if (filters.hasDiff) params.set('has_diff', '1')

            const response = await $fetch<any>(
                `${config.public.apiBase}/admin/audit-log?${params}`,
                {
                    headers: { Authorization: `Bearer ${token.value}` },
                }
            )

            // Log raw response in dev mode for debugging
            if (process.dev) {
                console.log('[useAuditLog] Raw response:', response)
            }

            // Safely extract data with guards
            const responseData = response?.data ?? response ?? []
            const responseMeta = response?.meta ?? null

            // Set events - handle both { data: [...] } and direct array response
            events.value = Array.isArray(responseData) ? responseData : []

            // Set meta with safe defaults if missing/invalid
            if (responseMeta && typeof responseMeta === 'object') {
                meta.value = {
                    current_page: responseMeta.current_page ?? 1,
                    last_page: responseMeta.last_page ?? 1,
                    per_page: responseMeta.per_page ?? 25,
                    total: responseMeta.total ?? events.value.length,
                }
            } else {
                // Keep current page, estimate total from data length
                meta.value = {
                    ...meta.value,
                    total: events.value.length,
                    last_page: Math.max(1, Math.ceil(events.value.length / meta.value.per_page)),
                }
            }
        } catch (e: any) {
            error.value = e.data?.message || e.message || 'Failed to fetch audit log'
            console.error('[useAuditLog] fetchEvents error:', e)
            // Ensure events is empty array on error
            events.value = []
        } finally {
            loading.value = false
        }
    }

    const applyFilters = () => {
        meta.value.current_page = 1
        fetchEvents()
    }

    const debouncedApplyFilters = () => {
        if (debounceTimer) clearTimeout(debounceTimer)
        debounceTimer = setTimeout(() => applyFilters(), 350)
    }

    const resetFilters = () => {
        filters.q = ''
        filters.eventType = ''
        filters.from = ''
        filters.to = ''
        filters.actorId = ''
        filters.role = ''
        filters.departmentId = ''
        filters.hasDiff = false
        meta.value.current_page = 1
        fetchEvents()
    }

    const nextPage = () => {
        if (meta.value.current_page < meta.value.last_page) {
            meta.value.current_page++
            fetchEvents()
        }
    }

    const prevPage = () => {
        if (meta.value.current_page > 1) {
            meta.value.current_page--
            fetchEvents()
        }
    }

    const setPerPage = (perPage: number) => {
        meta.value.per_page = perPage
        meta.value.current_page = 1
        fetchEvents()
    }

    // Watch search input with debounce
    watch(() => filters.q, () => {
        debouncedApplyFilters()
    })

    return {
        events,
        loading,
        error,
        meta,
        filters,
        hasActiveFilters,
        fetchEvents,
        applyFilters,
        resetFilters,
        nextPage,
        prevPage,
        setPerPage,
    }
}
