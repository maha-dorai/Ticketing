<template>
  <div class="ds-auth">
    <div class="ds-auth__container" :class="{ 'ds-auth__container--wide': wide }">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ wide?: boolean }>(), { wide: false });
</script>

<style scoped>
.ds-auth__container--wide {
  max-width: 28.75rem;
}
</style>
