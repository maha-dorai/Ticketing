<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="bm-backdrop" @click.self="close" role="dialog" aria-modal="true">
        <div class="bm-panel" :class="`bm-panel--${size}`">

          <!-- Header -->
          <div class="bm-header">
            <h2 class="bm-title">{{ title }}</h2>
            <button class="bm-close" @click="close" aria-label="Fermer">
              <X :size="18" />
            </button>
          </div>

          <!-- Body -->
          <div class="bm-body">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="bm-footer">
            <slot name="footer" />
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}>(), {
  title: '',
  size: 'md',
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

const close = () => emit('update:modelValue', false);

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.modelValue) close();
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<style scoped>
.bm-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.bm-panel {
  background: var(--ds-bg, #fff);
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  width: 100%;
}

.bm-panel--sm  { max-width: 400px; }
.bm-panel--md  { max-width: 560px; }
.bm-panel--lg  { max-width: 720px; }
.bm-panel--xl  { max-width: 960px; }

.bm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--ds-border, #e5e7eb);
  flex-shrink: 0;
}

.bm-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ds-text, #111827);
  margin: 0;
}

.bm-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: var(--ds-text-muted, #6b7280);
  cursor: pointer;
  transition: background .15s;
}
.bm-close:hover { background: var(--ds-surface, #f3f4f6); }

.bm-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.bm-footer {
  display: flex;
  justify-content: flex-end;
  gap: .75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--ds-border, #e5e7eb);
  flex-shrink: 0;
}

/* Transition */
.modal-enter-active,
.modal-leave-active { transition: opacity .2s ease; }
.modal-enter-active .bm-panel,
.modal-leave-active .bm-panel { transition: transform .2s ease, opacity .2s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
.modal-enter-from .bm-panel,
.modal-leave-to .bm-panel { transform: scale(.96); opacity: 0; }
</style>