<template>
  <Teleport to="body">
    <Transition name="ds-modal">
      <div
        v-if="modelValue"
        class="ds-modal-backdrop"
        @click="onBackdropClick"
      >
        <div
          ref="modalRef"
          class="ds-modal"
          :class="sizeClass"
          role="dialog"
          :aria-modal="true"
          :aria-labelledby="titleId"
          @click.stop
        >
          <!-- Header -->
          <div v-if="$slots.header || title" class="ds-modal__header">
            <h2 v-if="title" :id="titleId" class="ds-modal__title">
              <slot name="title-icon">
                <component v-if="titleIcon" :is="titleIcon" class="ds-modal__title-icon" aria-hidden="true" />
              </slot>
              {{ title }}
            </h2>
            <slot name="header" />
            <button
              v-if="showClose"
              type="button"
              class="ds-modal__close"
              :aria-label="closeLabel"
              @click="onClose"
            >
              <X :size="16" aria-hidden="true" />
            </button>
          </div>

          <!-- Body -->
          <div class="ds-modal__body">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="ds-modal__footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { X } from 'lucide-vue-next';
import type { Component } from 'vue';

const props = withDefaults(
  defineProps<{
    modelValue?: boolean;
    title?: string;
    titleIcon?: Component;
    size?: 'sm' | 'md' | 'lg';
    showClose?: boolean;
    closeOnBackdrop?: boolean;
    closeOnEscape?: boolean;
    closeLabel?: string;
  }>(),
  {
    modelValue: false,
    size: 'md',
    showClose: true,
    closeOnBackdrop: true,
    closeOnEscape: true,
    closeLabel: 'Fermer',
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  close: [];
}>();

const modalRef = ref<HTMLElement | null>(null);
const titleId = computed(() => `ds-modal-title-${Math.random().toString(36).substr(2, 9)}`);

const sizeClass = computed(() => {
  if (props.size === 'sm') return 'ds-modal--sm';
  if (props.size === 'lg') return 'ds-modal--lg';
  return '';
});

function onClose() {
  emit('update:modelValue', false);
  emit('close');
}

function onBackdropClick() {
  if (props.closeOnBackdrop) {
    onClose();
  }
}

function onEscapeKey(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.closeOnEscape && props.modelValue) {
    onClose();
  }
}

// Focus management
let previousActiveElement: HTMLElement | null = null;

async function focusModal() {
  await nextTick();
  if (modalRef.value) {
    modalRef.value.focus();
  }
}

function restoreFocus() {
  if (previousActiveElement) {
    previousActiveElement.focus();
  }
}

watch(() => props.modelValue, async (isOpen) => {
  if (isOpen) {
    previousActiveElement = document.activeElement as HTMLElement;
    await focusModal();
    document.addEventListener('keydown', onEscapeKey);
    document.body.style.overflow = 'hidden';
  } else {
    document.removeEventListener('keydown', onEscapeKey);
    document.body.style.overflow = '';
    restoreFocus();
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', onEscapeKey);
  document.body.style.overflow = '';
});
</script>

<style>
.ds-modal-enter-active,
.ds-modal-leave-active {
  transition: opacity 0.25s ease-out;
}

.ds-modal-enter-active .ds-modal,
.ds-modal-leave-active .ds-modal {
  transition: transform 0.25s ease-out, opacity 0.25s ease-out;
}

.ds-modal-enter-from,
.ds-modal-leave-to {
  opacity: 0;
}

.ds-modal-enter-from .ds-modal,
.ds-modal-leave-to .ds-modal {
  transform: translateY(0.5rem) scale(0.98);
  opacity: 0;
}
</style>