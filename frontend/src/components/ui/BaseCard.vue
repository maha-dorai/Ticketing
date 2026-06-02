<template>
  <component
    :is="tag"
    :to="tag === 'router-link' ? to : undefined"
    :href="tag === 'a' ? href : undefined"
    :class="[cardClasses, attrs.class]"
    :style="attrs.style"
  >
    <header v-if="$slots.header" class="ds-card__header">
      <slot name="header" />
    </header>
    <div :class="bodyClass">
      <slot />
    </div>
    <footer v-if="$slots.footer" class="ds-card__footer">
      <slot name="footer" />
    </footer>
  </component>
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import type { RouteLocationRaw } from 'vue-router';

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();

const props = withDefaults(
  defineProps<{
    interactive?: boolean;
    flat?: boolean;
    accent?: 'open' | 'progress' | 'pending' | 'closed' | 'danger' | null;
    tag?: 'div' | 'article' | 'a' | 'router-link';
    to?: RouteLocationRaw;
    href?: string;
    bodyClass?: string;
  }>(),
  {
    interactive: false,
    flat: false,
    accent: null,
    tag: 'div',
    bodyClass: 'ds-card__body',
  },
);

const cardClasses = computed(() => [
  'ds-card',
  props.interactive && 'ds-card--interactive',
  props.flat && 'ds-card--flat',
  props.accent && `ds-card--accent-${props.accent}`,
]);
</script>
