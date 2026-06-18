import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';

/**
 * [Sprint 1] Pinia Store : Gestion de l'état global d'authentification.
 * Ce fichier stocke "qui est connecté" pour que tous les composants Vue y aient accès.
 */
export const useAuthStore = defineStore('auth', () => {
  // --- ÉTAT GLOBAL ---
  const isAuthenticated     = ref(false); // Vrai si connecté
  const currentUser         = ref<any>(null); // Contient { id, nom, email, role... }
  const forcePasswordChange = ref(false); // Si Vrai, l'utilisateur est bloqué sur la page "Changer de mot de passe"

  /**
   * [Sprint 1] Initialisation au démarrage de l'app (App.vue).
   * Restaure la session depuis le localStorage si l'utilisateur recharge la page (F5).
   */
  const init = () => {
    const token = localStorage.getItem('token');
    const user  = localStorage.getItem('user');
    if (token && user) {
      isAuthenticated.value     = true;
      currentUser.value         = JSON.parse(user);
      forcePasswordChange.value = currentUser.value?.force_password_change ?? false;
    }
  };

  /**
   * [Sprint 1] Fonction de connexion
   * Communique avec AuthController@login via Axios
   */
  const login = async (email: string, password: string) => {
    try {
      const res = await api.post('/auth/login', { email, mot_de_passe: password });

      const user = res.data.user;
      // Stockage persistant du Token JWT
      localStorage.setItem('token', res.data.token);
      localStorage.setItem('user',  JSON.stringify(user));

      currentUser.value         = user;
      isAuthenticated.value     = true;
      forcePasswordChange.value = user.force_password_change ?? false;

      return 'success';
    } catch (err: any) {
      // Gestion intelligente des retours d'erreurs HTTP (Règles métier)
      const msg = err.response?.data?.message || '';
      if (msg.includes('attente'))   return 'en_attente';
      if (msg.includes('rejet'))     return 'rejete';
      if (msg.includes('désactivé')) return 'desactive';
      return 'error';
    }
  };

  /**
   * [Sprint 1] Déconnexion
   */
  const logout = async () => {
    try { await api.post('/auth/logout'); } catch {} // Demande au backend d'invalider le token
    
    // Nettoyage local absolu
    isAuthenticated.value     = false;
    currentUser.value         = null;
    forcePasswordChange.value = false;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  };

  /**
   * [Sprint 1] Inscription
   */
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

  /** [Sprint 2] Helper : Vérifie si le user courant a des droits d'administration étendus */
  const isManager = () => ['chef_de_projet', 'admin'].includes(currentUser.value?.role);

  /** [Sprint 1] Helper : Vérifie si le user est l'Admin Suprême */
  const isAdmin   = () => currentUser.value?.role === 'admin';

  return {
    isAuthenticated, currentUser, forcePasswordChange,
    login, logout, register, init,
    clearForcePasswordChange, isManager, isAdmin,
  };
});