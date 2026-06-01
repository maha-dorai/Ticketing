<template>
  <div class="auth-brand">
    <Ticket class="auth-brand__logo" aria-hidden="true" />
    <h1 class="auth-brand__name">{{ title }}</h1>
    <p v-if="subtitle" class="auth-brand__sub">{{ subtitle }}</p>
  </div>
</template>

<script setup lang="ts">
import { withDefaults } from 'vue';
import { Ticket } from 'lucide-vue-next';

withDefaults(
  defineProps<{
    title?: string;
    subtitle?: string;
  }>(),
  { title: 'Ticketing' },
);
</script>

<style scoped>
.auth-brand {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-brand__logo {
  width: 2.5rem;
  height: 2.5rem;
  margin: 0 auto 0.5rem;
  color: var(--color-brand-400, #60a5fa);
}

.auth-brand__name {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--color-auth-text, #f8fafc);
  letter-spacing: -0.02em;
  margin: 0;
}

.auth-brand__sub {
  color: var(--color-auth-text-muted, #64748b);
  font-size: 0.875rem;
  margin: 0.25rem 0 0;
}
</style>
