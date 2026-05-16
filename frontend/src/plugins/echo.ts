import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Rendre Pusher disponible globalement (requis par Laravel Echo)
(window as any).Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'pusher',
  key:         import.meta.env.VITE_PUSHER_APP_KEY,
  cluster:     import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
  forceTLS:    true,
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