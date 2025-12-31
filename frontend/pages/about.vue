<template>
  <NuxtLayout name="public">
    <div class="container mx-auto px-4 py-16">
      <h1 class="text-3xl font-bold text-[var(--color-text-primary)] mb-6">{{ $t('about.title') }}</h1>
      <p class="text-[var(--color-text-secondary)]">
        Hospital Ticketing System - A modern platform for managing hospital appointments and support tickets.
      </p>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false })
</script>
