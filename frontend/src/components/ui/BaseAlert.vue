<template>
  <div :class="alertClasses" :role="role" :aria-live="live ? 'polite' : undefined">
    <component v-if="icon" :is="icon" class="ds-alert__icon ds-icon ds-icon--sm" aria-hidden="true" />
    <div class="ds-alert__content">
      <p v-if="title" class="ds-alert__title">{{ title }}</p>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';

const props = withDefaults(
  defineProps<{
    variant?: 'success' | 'warning' | 'error' | 'info';
    title?: string;
    icon?: Component;
    live?: boolean;
  }>(),
  {
    variant: 'info',
    live: false,
  },
);

const role = computed(() => (props.variant === 'error' ? 'alert' : 'status'));

const alertClasses = computed(() => ['ds-alert', `ds-alert--${props.variant}`]);
</script>
