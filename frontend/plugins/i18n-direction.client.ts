export default defineNuxtPlugin((nuxtApp) => {
  const updateDirection = (loc: string) => {
    const dir = loc === 'ar' ? 'rtl' : 'ltr'
    const lang = loc === 'ar' ? 'ar' : 'en'
    
    if (typeof document !== 'undefined') {
      document.documentElement.dir = dir
      document.documentElement.lang = lang
    }
  }

  // Access i18n from nuxtApp.$i18n
  nuxtApp.hook('app:mounted', () => {
    const i18n = nuxtApp.$i18n as any
    if (i18n?.locale) {
      // Set initial direction
      updateDirection(i18n.locale.value || i18n.locale)
      
      // Watch for locale changes
      watch(() => i18n.locale.value || i18n.locale, (newLocale: string) => {
        updateDirection(newLocale)
      })
    }
  })
})
