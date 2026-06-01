<template>
  <component
    :is="tag"
    :type="tag === 'button' ? type : undefined"
    :to="tag === 'router-link' ? to : undefined"
    :href="tag === 'a' ? href : undefined"
    :disabled="disabled || loading"
    :aria-disabled="disabled || loading ? true : undefined"
    :aria-busy="loading || undefined"
    :class="buttonClasses"
  >
    <slot />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { RouteLocationRaw } from 'vue-router';

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'danger-outline' | 'success' | 'slate';
    size?: 'sm' | 'md' | 'lg';
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
    block?: boolean;
    icon?: boolean;
    tag?: 'button' | 'a' | 'router-link';
    to?: RouteLocationRaw;
    href?: string;
  }>(),
  {
    variant: 'primary',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
    block: false,
    icon: false,
    tag: 'button',
  },
);

const buttonClasses = computed(() => [
  'ds-btn',
  `ds-btn--${props.variant}`,
  props.size !== 'md' && `ds-btn--${props.size}`,
  props.block && 'ds-btn--block',
  props.icon && 'ds-btn--icon',
  props.loading && 'ds-btn--loading',
]);
</script>
