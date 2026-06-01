<template>
  <button type="button" class="modal-close-btn" :class="$attrs.class" aria-label="Fermer" @click="$emit('click')">
    <X :size="18" aria-hidden="true" />
  </button>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next';

defineOptions({ inheritAttrs: false });

defineEmits<{ click: [] }>();
</script>

<style scoped>
.modal-close-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  color: var(--color-text-muted, #94a3b8);
  background: none;
  border: none;
  border-radius: var(--radius-md, 8px);
  cursor: pointer;
  transition: color 0.15s, background-color 0.15s;
}

.modal-close-btn:hover {
  color: var(--color-text-primary, #0f172a);
  background-color: var(--color-bg-subtle, #f1f5f9);
}

.modal-close-btn:focus-visible {
  box-shadow: var(--shadow-focus);
}
</style>
