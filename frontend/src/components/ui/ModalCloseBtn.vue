<template>
  <button type="button" class="ds-modal__close" aria-label="Fermer" v-bind="$attrs" @click="$emit('click', $event)">
    <X :size="18" aria-hidden="true" />
  </button>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next';

defineEmits<{ click: [event: MouseEvent] }>();
</script>