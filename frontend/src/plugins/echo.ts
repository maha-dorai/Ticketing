import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Rendre Pusher disponible globalement (requis par Laravel Echo)
(window as any).Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'pusher',
  key:         import.meta.env.VITE_PUSHER_APP_KEY,
  cluster:     import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
  forceTLS:    true,
});

export default echo;