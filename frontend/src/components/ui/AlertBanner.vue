<template>
  <div :class="['alert-banner-row', $attrs.class]">
    <component :is="icon" class="alert-banner-row__icon" aria-hidden="true" />
    <span class="alert-banner-row__text"><slot /></span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle, CheckCircle2, Clock, Sparkles, XCircle } from 'lucide-vue-next';
import type { Component } from 'vue';

const props = withDefaults(
  defineProps<{
    variant?: 'success' | 'error' | 'warning' | 'pending' | 'sparkle';
  }>(),
  { variant: 'warning' },
);

defineOptions({ inheritAttrs: false });

const icon = computed<Component>(() => {
  const map: Record<string, Component> = {
    success: CheckCircle2,
    error: XCircle,
    warning: AlertTriangle,
    pending: Clock,
    sparkle: Sparkles,
  };
  return map[props.variant] ?? AlertTriangle;
});
</script>

<style scoped>
.alert-banner-row {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.alert-banner-row__icon {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.alert-banner-row__text {
  flex: 1;
  min-width: 0;
}
</style>
