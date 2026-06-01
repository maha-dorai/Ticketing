<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="ds-modal-backdrop"
      role="presentation"
      @click.self="onBackdropClick"
    >
      <div
        class="ds-modal"
        :class="sizeClass"
        role="dialog"
        :aria-modal="true"
        :aria-labelledby="titleId"
        @keydown.esc="onEscape"
      >
        <header v-if="$slots.header || title" class="ds-modal__header">
          <slot name="header">
            <h2 :id="titleId" class="ds-modal__title">{{ title }}</h2>
          </slot>
          <button
            v-if="closable"
            type="button"
            class="ds-modal__close"
            aria-label="Fermer"
            @click="close"
          >
            <X class="ds-icon ds-icon--sm" aria-hidden="true" />
          </button>
        </header>
        <div class="ds-modal__body">
          <slot />
        </div>
        <footer v-if="$slots.footer" class="ds-modal__footer">
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, useId, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg';
    closable?: boolean;
    closeOnBackdrop?: boolean;
    closeOnEscape?: boolean;
  }>(),
  {
    size: 'md',
    closable: true,
    closeOnBackdrop: true,
    closeOnEscape: true,
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  close: [];
}>();

const uid = useId();
const titleId = computed(() => `ds-modal-title-${uid}`);

const sizeClass = computed(() => {
  if (props.size === 'sm') return 'ds-modal--sm';
  if (props.size === 'lg') return 'ds-modal--lg';
  return undefined;
});

function close() {
  emit('update:modelValue', false);
  emit('close');
}

function onBackdropClick() {
  if (props.closeOnBackdrop) close();
}

function onEscape() {
  if (props.closeOnEscape) close();
}

watch(
  () => props.modelValue,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
  },
);
</script>
