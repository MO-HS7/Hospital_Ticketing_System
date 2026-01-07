const LOCALE_KEY = 'hospital_ticketing_locale'

export default defineNuxtPlugin((nuxtApp) => {
  const updateDirection = (loc: string) => {
    const dir = loc === 'ar' ? 'rtl' : 'ltr'
    const lang = loc === 'ar' ? 'ar' : 'en'

    if (typeof document !== 'undefined') {
      document.documentElement.dir = dir
      document.documentElement.lang = lang
    }
  }

  const saveLocale = (loc: string) => {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(LOCALE_KEY, loc)
    }
  }

  const getSavedLocale = (): string | null => {
    if (typeof localStorage !== 'undefined') {
      return localStorage.getItem(LOCALE_KEY)
    }
    return null
  }

  // Access i18n from nuxtApp.$i18n
  nuxtApp.hook('app:mounted', () => {
    const i18n = nuxtApp.$i18n as any
    if (i18n?.locale) {
      // Restore saved locale if exists
      const savedLocale = getSavedLocale()
      if (savedLocale && ['en', 'ar'].includes(savedLocale)) {
        const currentLocale = i18n.locale.value || i18n.locale
        if (currentLocale !== savedLocale) {
          i18n.setLocale(savedLocale)
        }
      }

      // Set initial direction
      const currentLocale = i18n.locale.value || i18n.locale
      updateDirection(currentLocale)

      // Watch for locale changes
      watch(() => i18n.locale.value || i18n.locale, (newLocale: string) => {
        updateDirection(newLocale)
        saveLocale(newLocale)
      })
    }
  })
})
