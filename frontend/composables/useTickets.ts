export type TicketStatus = 'pending' | 'assigned' | 'awaiting_payment' | 'in_progress' | 'completed' | 'overdue' | 'closed_late'
export type TicketType = 'appointment' | 'maintenance'
export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent'

export interface TicketUser {
  id: number
  name: string
  email?: string
  phone?: string | null
}

export interface TicketNote {
  id: number
  ticket_id: number
  user_id: number | null
  body: string
  created_at: string
  updated_at: string
  user?: TicketUser | null
}

export interface TicketEvent {
  id: number
  ticket_id: number
  user_id: number | null
  event_type: string
  meta: Record<string, any> | null
  created_at: string
  updated_at: string
  user?: TicketUser | null
}

export interface TicketSla {
  is_overdue: boolean
  minutes_remaining: number | null
}

export interface TicketDepartment {
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
  name?: string
  description?: string | null
}

export interface TicketBase {
  id: number
  patient_id: number
  creator_id?: number | null
  department_id: string
  encounter_id: string | null
  assigned_to: number | null
  type: TicketType
  status: TicketStatus
  priority: TicketPriority
  subject: string
  description: string | null
  scheduled_at: string | null
  deadline: string | null
  accepted_at: string | null
  started_at: string | null
  completed_at: string | null
  time_spent_minutes: number | null
  created_at: string
  updated_at: string
  patient?: TicketUser
  department?: TicketDepartment
  assignee?: TicketUser | null
  sla?: TicketSla
  // Patient Info fields (Step 3 enhancement)
  patient_age?: number | null
  patient_gender?: 'male' | 'female' | null
  contact_method?: 'phone' | 'whatsapp' | 'sms' | 'in_app' | null
  contact_phone?: string | null
  is_emergency?: boolean
  medical_conditions?: string | null
  additional_notes?: string | null
}

export interface TicketDetail extends TicketBase {
  patient: TicketUser
  department: TicketDepartment
  assignee: TicketUser | null
  notes: TicketNote[]
  events: TicketEvent[]
  sla: TicketSla
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface TicketListParams {
  page?: number
  status?: TicketStatus
  department_id?: string
}

export interface CreateTicketPayload {
  department_id: string
  type: TicketType
  subject: string
  description?: string | null
  scheduled_at?: string | null
  priority?: TicketPriority
  patient_id?: number
  assigned_to?: number | null
}

export const useTickets = () => {
  const config = useRuntimeConfig()
  const { token } = useAuth()

  const loading = ref(false)
  const error = ref<string | null>(null)

  const authHeaders = () => ({
    Authorization: `Bearer ${token.value}`,
  })

  const emptyPage: PaginatedResponse<TicketBase> = {
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: null,
    to: null,
  }

  const fetchTickets = async (params: TicketListParams = {}): Promise<PaginatedResponse<TicketBase>> => {
    loading.value = true
    error.value = null
    try {
      const query = new URLSearchParams()
      if (params.page) query.set('page', String(params.page))
      if (params.status) query.set('status', params.status)
      if (params.department_id) query.set('department_id', params.department_id)

      const qs = query.toString()
      const url = `${config.public.apiBase}/tickets${qs ? `?${qs}` : ''}`

      return await $fetch<PaginatedResponse<TicketBase>>(url, {
        headers: authHeaders(),
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to fetch tickets'
      return emptyPage
    } finally {
      loading.value = false
    }
  }

  const createTicket = async (payload: CreateTicketPayload): Promise<TicketBase | null> => {
    loading.value = true
    error.value = null
    try {
      return await $fetch<TicketBase>(`${config.public.apiBase}/tickets`, {
        method: 'POST',
        headers: authHeaders(),
        body: payload,
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to create ticket'
      return null
    } finally {
      loading.value = false
    }
  }

  const getTicket = async (ticketId: number): Promise<TicketDetail | null> => {
    loading.value = true
    error.value = null
    try {
      return await $fetch<TicketDetail>(`${config.public.apiBase}/tickets/${ticketId}`, {
        headers: authHeaders(),
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to fetch ticket'
      return null
    } finally {
      loading.value = false
    }
  }

  const acceptTicket = async (ticketId: number): Promise<TicketBase | null> => {
    loading.value = true
    error.value = null
    try {
      return await $fetch<TicketBase>(`${config.public.apiBase}/tickets/${ticketId}/accept`, {
        method: 'POST',
        headers: authHeaders(),
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to accept ticket'
      return null
    } finally {
      loading.value = false
    }
  }

  const startTicket = async (ticketId: number): Promise<TicketBase | null> => {
    loading.value = true
    error.value = null
    try {
      return await $fetch<TicketBase>(`${config.public.apiBase}/tickets/${ticketId}/start`, {
        method: 'POST',
        headers: authHeaders(),
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to start ticket'
      return null
    } finally {
      loading.value = false
    }
  }

  const completeTicket = async (ticketId: number): Promise<TicketBase | null> => {
    loading.value = true
    error.value = null
    try {
      return await $fetch<TicketBase>(`${config.public.apiBase}/tickets/${ticketId}/complete`, {
        method: 'POST',
        headers: authHeaders(),
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to complete ticket'
      return null
    } finally {
      loading.value = false
    }
  }

  const addNote = async (ticketId: number, body: string): Promise<TicketNote | null> => {
    loading.value = true
    error.value = null
    try {
      return await $fetch<TicketNote>(`${config.public.apiBase}/tickets/${ticketId}/notes`, {
        method: 'POST',
        headers: authHeaders(),
        body: { body },
      })
    } catch (e: any) {
      error.value = e.data?.message || e.message || 'Failed to add note'
      return null
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    fetchTickets,
    getTicket,
    createTicket,
    acceptTicket,
    startTicket,
    completeTicket,
    addNote,
  }
}
