<template>
  <Teleport to="body">
    <div class="ds-toast" aria-live="polite" aria-relevant="additions">
      <BaseAlert
        v-for="t in toasts"
        :key="t.id"
        :variant="t.variant"
        :title="t.title"
        :icon="iconFor(t.variant)"
        live
      >
        {{ t.message }}
      </BaseAlert>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { CheckCircle2, AlertTriangle, XCircle, Info } from 'lucide-vue-next';
import type { Component } from 'vue';
import BaseAlert from './BaseAlert.vue';
import { useToast, type ToastVariant } from '../../composables/useToast';

const { toasts } = useToast();

function iconFor(variant: ToastVariant): Component {
  const map: Record<ToastVariant, Component> = {
    success: CheckCircle2,
    warning: AlertTriangle,
    error: XCircle,
    info: Info,
  };
  return map[variant];
}
</script>