// ============================================================
// authStore.ts — Le cerveau de l'authentification
// Gère : connexion, déconnexion, inscription via l'API réelle
// ============================================================

// defineStore : la fonction Pinia pour créer un "magasin" de données global
import { defineStore } from 'pinia';

// ref : permet de créer des variables réactives (Vue se met à jour automatiquement quand elles changent)
import { ref } from 'vue';

// On importe le pont HTTP pour envoyer des requêtes au backend Laravel
import api from '../services/api';

// On définit et exporte le store d'authentification
// 'auth' est le nom unique du store — utilisé par Pinia en interne
export const useAuthStore = defineStore('auth', () => {

  // Variable réactive : est-ce que l'utilisateur est connecté ? (false par défaut)
  const isAuthenticated = ref(false);

  // Variable réactive : les informations de l'utilisateur connecté (null par défaut)
  const currentUser = ref<any>(null);

  // ─────────────────────────────────────────────
  // FONCTION : Se connecter
  // Reçoit email + mot de passe, appelle le backend, sauvegarde le token
  // ─────────────────────────────────────────────
  const login = async (email: string, password: string) => {
    try {
      // Envoie une requête POST à http://localhost:8000/api/auth/login
      // Attention : le backend attend "mot_de_passe", pas "password"
      const res = await api.post('/auth/login', {
        email,
        mot_de_passe: password
      });

      // Sauvegarde le token JWT reçu dans le localStorage du navigateur
      // Ce token sera automatiquement utilisé par api.js pour les prochaines requêtes
      localStorage.setItem('token', res.data.token);

      // Sauvegarde les infos de l'utilisateur (id, nom, prenom, email, role)
      currentUser.value = res.data.user;

      // Marque l'utilisateur comme connecté
      isAuthenticated.value = true;

      // Retourne 'success' pour que Login.vue sache quoi afficher
      return 'success';

    } catch (err: any) {
      // Récupère le message d'erreur envoyé par le backend
      const msg = err.response?.data?.message || '';

      // Si le compte est en attente de validation → retourne 'en_attente'
      if (msg.includes('attente')) return 'en_attente';

      // Si le compte a été rejeté par l'admin → retourne 'rejete'
      if (msg.includes('rejeté')) return 'rejete';

      // Sinon c'est un mauvais email ou mot de passe → retourne 'error'
      return 'error';
    }
  };

  // ─────────────────────────────────────────────
  // FONCTION : Se déconnecter
  // Invalide le token côté serveur et nettoie le navigateur
  // ─────────────────────────────────────────────
  const logout = async () => {
    try {
      // Demande au backend d'invalider le token JWT (il ne sera plus accepté)
      await api.post('/auth/logout');
    } catch {
      // Si la requête échoue (ex: token déjà expiré), on continue quand même
    }

    // Réinitialise l'état local : l'utilisateur n'est plus connecté
    isAuthenticated.value = false;
    currentUser.value = null;

    // Supprime le token du localStorage du navigateur
    localStorage.removeItem('token');
  };

  // ─────────────────────────────────────────────
  // FONCTION : Inscrire un nouvel utilisateur
  // Envoie les données du formulaire Register.vue au backend
  // ─────────────────────────────────────────────
  const addUser = async (userData: any) => {
    // Envoie une requête POST à http://localhost:8000/api/auth/register
    await api.post('/auth/register', {
      // Split le nom complet en deux : "Jean Dupont" → nom="Dupont", prenom="Jean"
      nom:          userData.name.split(' ')[1] || userData.name,
      prenom:       userData.name.split(' ')[0],

      email:        userData.email,
      mot_de_passe: userData.password,

      // Conversion des valeurs Vue → valeurs attendues par le backend
      // Vue utilise "Développeur" (avec majuscule), le backend attend "developpeur" (tout en minuscule)
      role:         userData.role === 'Développeur' ? 'developpeur' : 'testeur',

      // Le lien GitHub (obligatoire pour les développeurs, null pour les testeurs)
      github_link:  userData.link || null,
    });
  };

  // On exporte toutes les variables et fonctions pour qu'elles soient accessibles dans les vues
  return { isAuthenticated, currentUser, login, logout, addUser };
});