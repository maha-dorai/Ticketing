<template>
  <div class="ds-field">
    <label v-if="label" :for="inputId" class="ds-field__label" :class="{ 'ds-field__label--required': required }">
      {{ label }}
    </label>
    <div
      v-if="$slots.prefix || $slots.suffix"
      class="ds-input-wrap"
      :class="{
        'ds-input-wrap--icon-left': $slots.prefix,
        'ds-input-wrap--icon-right': $slots.suffix,
      }"
    >
      <span v-if="$slots.prefix" class="ds-input-wrap__icon ds-input-wrap__icon--left">
        <slot name="prefix" />
      </span>
      <input
        :id="inputId"
        v-bind="$attrs"
        :value="modelValue"
        :type="type"
        :disabled="disabled"
        :required="required"
        :aria-invalid="error ? true : undefined"
        :aria-describedby="describedBy"
        class="inputClasses"
        @input="onInput"
      />
      <span v-if="$slots.suffix" class="ds-input-wrap__action">
        <slot name="suffix" />
      </span>
    </div>
    <input
      v-else
      :id="inputId"
      v-bind="$attrs"
      :value="modelValue"
      :type="type"
      :disabled="disabled"
      :required="required"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="describedBy"
      class="inputClasses"
      @input="onInput"
    />
    <p v-if="hint && !error" :id="hintId" class="ds-field__hint">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="ds-field__error" role="alert">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
  defineProps<{
    modelValue?: string | number;
    label?: string;
    hint?: string;
    error?: string;
    type?: string;
    size?: 'sm' | 'md' | 'lg';
    auth?: boolean;
    disabled?: boolean;
    required?: boolean;
  }>(),
  {
    modelValue: '',
    type: 'text',
    size: 'md',
    auth: false,
    disabled: false,
    required: false,
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const uid = useId();
const inputId = computed(() => `ds-input-${uid}`);
const hintId = computed(() => `ds-hint-${uid}`);
const errorId = computed(() => `ds-error-${uid}`);

const describedBy = computed(() => {
  if (props.error) return errorId.value;
  if (props.hint) return hintId.value;
  return undefined;
});

const inputClasses = computed(() => [
  'ds-input',
  props.size !== 'md' && `ds-input--${props.size}`,
  props.auth && 'ds-input--auth',
  props.error && 'ds-input--error',
]);

function onInput(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.value);
}
</script>
