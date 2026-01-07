<template>
  <div class="animate-pulse bg-gray-200 dark:bg-gray-700 rounded" :class="className"></div>
</template>

<script setup lang="ts">
const props = defineProps<{
  class?: string
  w?: string
  h?: string
}>()

const className = computed(() => {
  return [
    props.class,
    props.w ? `w-[${props.w}]` : '',
    props.h ? `h-[${props.h}]` : ''
  ].filter(Boolean).join(' ')
})
</script>
