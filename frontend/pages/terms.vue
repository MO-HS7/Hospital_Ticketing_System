<template>
  <NuxtLayout name="public">
    <div class="container mx-auto px-4 py-16">
      <h1 class="text-3xl font-bold text-[var(--color-text-primary)] mb-6">{{ $t('footer.terms') }}</h1>
      <p class="text-[var(--color-text-secondary)]">
        By using this service, you agree to our terms and conditions.
      </p>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })
</script>
