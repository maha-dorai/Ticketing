import { ref, readonly } from 'vue';

export type ToastVariant = 'success' | 'warning' | 'error' | 'info';

export interface ToastItem {
  id: number;
  message: string;
  variant: ToastVariant;
  title?: string;
  duration: number;
}

const toasts = ref<ToastItem[]>([]);
let nextId = 0;

function remove(id: number) {
  toasts.value = toasts.value.filter((t) => t.id !== id);
}

export function useToast() {
  function show(
    message: string,
    options: {
      variant?: ToastVariant;
      title?: string;
      duration?: number;
    } = {},
  ) {
    const { variant = 'info', title, duration = 4000 } = options;
    const id = ++nextId;
    const item: ToastItem = { id, message, variant, title, duration };
    toasts.value = [...toasts.value, item];

    if (duration > 0) {
      window.setTimeout(() => remove(id), duration);
    }

    return id;
  }

  return {
    toasts: readonly(toasts),
    show,
    success: (message: string, title?: string) => show(message, { variant: 'success', title }),
    warning: (message: string, title?: string) => show(message, { variant: 'warning', title }),
    error: (message: string, title?: string) => show(message, { variant: 'error', title }),
    info: (message: string, title?: string) => show(message, { variant: 'info', title }),
    dismiss: remove,
  };
}
