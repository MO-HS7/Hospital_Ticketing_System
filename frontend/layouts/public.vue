<template>
  <div class="min-h-screen flex flex-col bg-[var(--color-bg-primary)]">
    <PublicHeader />
    <main class="flex-1">
      <slot />
    </main>
    <Footer />
  </div>
</template>

<script setup lang="ts">
</script>
