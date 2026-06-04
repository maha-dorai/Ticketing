import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
(window as any).Pusher = Pusher;

const usePusher = !!import.meta.env.VITE_PUSHER_APP_KEY;

const echoConfig = (usePusher
  ? {
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
      forceTLS: true,
    }
  : {
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY ?? 'dseeky0h2wzg5cwdbdfb',
      wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
    }) as any;

const echo = new Echo({
  ...echoConfig,
  authEndpoint: `${import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'}/broadcasting/auth`,
  auth: {
    headers: {
      get Authorization() {
        return `Bearer ${localStorage.getItem('token') ?? ''}`;
      },
    },
  },
});

(window as any).Echo = echo;
export default echo;