<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup lang="ts">
// Set up HTML attributes based on locale and color mode
const { locale } = useI18n()
const colorMode = useColorMode()

// Update HTML dir attribute when locale changes
useHead({
  titleTemplate: (titleChunk) => {
    return titleChunk ? `${titleChunk} | Masar (مسار)` : 'Masar (مسار)'
  },
  htmlAttrs: {
    dir: () => locale.value === 'ar' ? 'rtl' : 'ltr',
    lang: () => locale.value,
    class: () => colorMode.value,
  },
})
</script>
