<template>
  <span :class="badgeClasses">
    <span v-if="dot" class="ds-badge__dot" aria-hidden="true" />
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    variant?:
      | 'brand'
      | 'neutral'
      | 'success'
      | 'warning'
      | 'error'
      | 'info'
      | 'open'
      | 'progress'
      | 'pending'
      | 'closed'
      | 'danger'
      | 'priority-critical'
      | 'priority-high'
      | 'priority-medium'
      | 'priority-low';
    pill?: boolean;
    count?: boolean;
    dot?: boolean;
  }>(),
  {
    variant: 'neutral',
    pill: false,
    count: false,
    dot: false,
  },
);

const badgeClasses = computed(() => [
  'ds-badge',
  `ds-badge--${props.variant}`,
  props.pill && 'ds-badge--pill',
  props.count && 'ds-badge--count',
]);
</script>
