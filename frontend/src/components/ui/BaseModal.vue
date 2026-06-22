<template>
  <Teleport to="body">
    <div v-if="modelValue" class="ds-overlay" @click="close">
      <div class="ds-modal" :class="sizeClass" @click.stop>
        <div class="ds-modal-header">
          <h3 class="ds-modal-title">{{ title }}</h3>
          <button class="ds-close-btn" @click="close" aria-label="Fermer">×</button>
        </div>
        <div class="ds-modal-body">
          <slot />
        </div>
        <div v-if="$slots.footer" class="ds-modal-footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg';
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

const close = () => {
  emit('update:modelValue', false);
};

const sizeClass = computed(() => {
  if (props.size === 'lg') return 'ds-modal-lg';
  if (props.size === 'sm') return 'ds-modal-sm';
  return '';
});
</script>

<style scoped>
.ds-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.ds-modal {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  animation: modalIn 0.2s ease-out;
}

@keyframes modalIn {
  from { opacity: 0; transform: translateY(10px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.ds-modal-lg {
  max-width: 600px;
}

.ds-modal-sm {
  max-width: 320px;
}

.ds-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.ds-modal-title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.ds-close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ds-close-btn:hover {
  color: #1e293b;
}

.ds-modal-body {
  overflow-y: auto;
  flex: 1;
}

.ds-modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background-color: #f8fafc;
}
</style>