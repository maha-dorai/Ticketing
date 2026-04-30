import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';

export const useAuthStore = defineStore('auth', () => {
  const isAuthenticated = ref(false);
  const currentUser = ref<any>(null);

  const init = () => {
    const token = localStorage.getItem('token');
    const user  = localStorage.getItem('user');
    if (token && user) {
      isAuthenticated.value = true;
      currentUser.value = JSON.parse(user);
    }
  };

  const login = async (email: string, password: string) => {
    try {
      const res = await api.post('/auth/login', {
        email,
        mot_de_passe: password
      });
      localStorage.setItem('token', res.data.token);
      localStorage.setItem('user', JSON.stringify(res.data.user));
      currentUser.value = res.data.user;
      isAuthenticated.value = true;
      return 'success';
    } catch (err: any) {
      const msg = err.response?.data?.message || '';
      if (msg.includes('attente'))    return 'en_attente';
      if (msg.includes('rejeté'))     return 'rejete';
      if (msg.includes('désactivé')) return 'desactive';
      return 'error';
    }
  };

  const logout = async () => {
    try { await api.post('/auth/logout'); } catch {}
    isAuthenticated.value = false;
    currentUser.value = null;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  };

  const register = async (userData: {
    nom: string;
    prenom: string;
    email: string;
    mot_de_passe: string;
    role: string;
    github_link?: string | null;
  }) => {
    await api.post('/auth/register', userData);
  };

  return { isAuthenticated, currentUser, login, logout, register, init };
});