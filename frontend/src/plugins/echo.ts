import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Pusher-js is required by Laravel Echo even when using Reverb
(window as any).Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key:         import.meta.env.VITE_REVERB_APP_KEY,
  wsHost:      import.meta.env.VITE_REVERB_HOST ?? 'localhost',
  wsPort:      Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  wssPort:     Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  forceTLS:    (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: `${import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'}/broadcasting/auth`,
  auth: {
    headers: {
      get Authorization() {
        return `Bearer ${localStorage.getItem('token') ?? ''}`;
      },
    },
  },
});

// ✅ Rendre Echo accessible dans la console du navigateur pour le debug
(window as any).Echo = echo;

export default echo;