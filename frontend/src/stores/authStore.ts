import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';

export const useAuthStore = defineStore('auth', () => {
  const isAuthenticated     = ref(false);
  const currentUser         = ref<any>(null);
  const forcePasswordChange = ref(false);

  const init = () => {
    const token = localStorage.getItem('token');
    const user  = localStorage.getItem('user');
    if (token && user) {
      isAuthenticated.value     = true;
      currentUser.value         = JSON.parse(user);
      forcePasswordChange.value = currentUser.value?.force_password_change ?? false;
    }
  };

  const login = async (email: string, password: string) => {
    try {
      const res = await api.post('/auth/login', { email, mot_de_passe: password });

      const user = res.data.user;
      localStorage.setItem('token', res.data.token);
      localStorage.setItem('user',  JSON.stringify(user));

      currentUser.value         = user;
      isAuthenticated.value     = true;
      forcePasswordChange.value = user.force_password_change ?? false;

      return 'success';
    } catch (err: any) {
      const msg = err.response?.data?.message || '';
      if (msg.includes('attente'))    return 'en_attente';
      if (msg.includes('rejet'))    return 'rejete';
      if (msg.includes('désactivé')) return 'desactive';
      return 'error';
    }
  };

  const logout = async () => {
    try { await api.post('/auth/logout'); } catch {}
    isAuthenticated.value     = false;
    currentUser.value         = null;
    forcePasswordChange.value = false;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  };

  const register = async (userData: {
    nom: string; prenom: string; email: string;
    mot_de_passe: string; role: string; github_link?: string | null;
  }) => {
    await api.post('/auth/register', userData);
  };

  const clearForcePasswordChange = () => {
    forcePasswordChange.value = false;
    if (currentUser.value) {
      currentUser.value.force_password_change = false;
      localStorage.setItem('user', JSON.stringify(currentUser.value));
    }
  };

  /** admin + chef_de_projet : accès aux vues de gestion */
  const isManager = () => ['chef_de_projet', 'admin'].includes(currentUser.value?.role);

  /** admin uniquement : gestion des chefs de projet */
  const isAdmin   = () => currentUser.value?.role === 'admin';

  return {
    isAuthenticated, currentUser, forcePasswordChange,
    login, logout, register, init,
    clearForcePasswordChange, isManager, isAdmin,
  };
});
