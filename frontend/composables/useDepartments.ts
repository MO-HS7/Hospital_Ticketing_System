export interface Department {
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
}

export const useDepartments = () => {
  const config = useRuntimeConfig()
  const { locale } = useI18n()
  
  const departments = useState<Department[]>('departments', () => [])
  const loading = useState<boolean>('departments_loading', () => false)
  const error = useState<string | null>('departments_error', () => null)

  const getName = (dept: Department): string => {
    return locale.value === 'ar' ? dept.name_ar : dept.name_en
  }

  const getDescription = (dept: Department): string | null => {
    return locale.value === 'ar' ? dept.description_ar : dept.description_en
  }

  const fetchDepartments = async (search?: string): Promise<Department[]> => {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams()
      if (search) params.append('search', search)
      
      const res = await $fetch<{ data: Department[]; total: number }>(`${config.public.apiBase}/departments?${params}`)
      departments.value = res.data
      return res.data
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch departments'
      return []
    } finally {
      loading.value = false
    }
  }

  const getDepartmentById = (id: string): Department | undefined => {
    return departments.value.find(d => d.id === id)
  }

  const getDepartmentBySlug = (slug: string): Department | undefined => {
    return departments.value.find(d => d.slug === slug)
  }

  return {
    departments,
    loading,
    error,
    fetchDepartments,
    getDepartmentById,
    getDepartmentBySlug,
    getName,
    getDescription,
  }
}
