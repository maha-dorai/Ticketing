<template>
  <div class="layout" :class="{ 'layout--fixed': fixed }">
    <AppSidebar />
    <main class="main">
      <AppHeader />
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import AppSidebar from '../AppSidebar.vue';

withDefaults(
  defineProps<{
    /** Prevent page scroll; use on full-height Kanban views */
    fixed?: boolean;
  }>(),
  { fixed: false },
);
</script>