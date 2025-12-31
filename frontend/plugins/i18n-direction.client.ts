export default defineNuxtPlugin(() => {
  const { locale } = useI18n()

  const updateDirection = (loc: string) => {
    const dir = loc === 'ar' ? 'rtl' : 'ltr'
    const lang = loc === 'ar' ? 'ar' : 'en'
    
    // Update document attributes
    if (typeof document !== 'undefined') {
      document.documentElement.dir = dir
      document.documentElement.lang = lang
    }
  }

  // Set initial direction
  updateDirection(locale.value)

  // Watch for locale changes
  watch(locale, (newLocale) => {
    updateDirection(newLocale)
  })
})
