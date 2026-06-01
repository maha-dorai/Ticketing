<template>
  <header :class="rootClass">
    <div class="ds-page-header-bar__inner">
      <div
        class="ds-page-header-bar__main"
        :class="{ 'ds-page-header-bar__main--back-inline': backInline }"
      >
        <div v-if="$slots.back" class="ds-page-header-bar__back-wrap">
          <slot name="back" />
        </div>
        <div class="ds-page-header-bar__text">
          <h1 v-if="$slots.title" class="ds-page-header-bar__title">
            <slot name="title" />
          </h1>
          <p v-if="$slots.subtitle" class="ds-page-header-bar__subtitle">
            <slot name="subtitle" />
          </p>
        </div>
      </div>
      <div v-if="$slots.actions" class="ds-page-header-bar__actions">
        <slot name="actions" />
      </div>
    </div>
    <div v-if="$slots.toolbar" class="ds-page-header-bar__toolbar">
      <slot name="toolbar" />
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    /** default: white bar · hero: title row + toolbar · glass: sticky blurred bar */
    variant?: 'default' | 'hero' | 'glass';
    /** Tighter padding for Kanban / fixed layouts */
    compact?: boolean;
    /** Title block only (no side-by-side actions row); use when actions slot is empty */
    stacked?: boolean;
    align?: 'start' | 'center';
    /** Place back control beside title (e.g. edit forms) */
    backInline?: boolean;
  }>(),
  {
    variant: 'default',
    compact: false,
    stacked: false,
    align: 'start',
    backInline: false,
  },
);

const rootClass = computed(() => [
  'ds-page-header-bar',
  `ds-page-header-bar--${props.variant}`,
  {
    'ds-page-header-bar--compact': props.compact,
    'ds-page-header-bar--stacked': props.stacked,
    [`ds-page-header-bar--align-${props.align}`]: true,
  },
]);
</script>
