import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';

export const useAuthStore = defineStore('auth', () => {
  const isAuthenticated = ref(false);
  const currentUser = ref<any>(null);

  const login = async (email: string, password: string) => {
    try {
      const res = await api.post('/auth/login', {
        email,
        mot_de_passe: password
      });
      localStorage.setItem('token', res.data.token);
      currentUser.value = res.data.user;
      isAuthenticated.value = true;
      return 'success';
    } catch (err: any) {
      const msg = err.response?.data?.message || '';
      if (msg.includes('attente')) return 'en_attente';
      if (msg.includes('rejeté')) return 'rejete';
      return 'error';
    }
  };

  const logout = async () => {
    try { await api.post('/auth/logout'); } catch {}
    isAuthenticated.value = false;
    currentUser.value = null;
    localStorage.removeItem('token');
  };

  const addUser = async (userData: any) => {
    await api.post('/auth/register', {
      nom:          userData.name.split(' ')[1] || userData.name,
      prenom:       userData.name.split(' ')[0],
      email:        userData.email,
      mot_de_passe: userData.password,
      role:         userData.role === 'Développeur' ? 'developpeur' : 'testeur',
      github_link:  userData.link || null,
    });
  };

  return { isAuthenticated, currentUser, login, logout, addUser };
});