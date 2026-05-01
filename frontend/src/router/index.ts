// ============================================================
// router/index.ts — Le GPS de l'application
// Définit toutes les pages accessibles et protège certaines routes
// ============================================================

// createRouter : crée l'instance du router Vue
// createWebHistory : utilise de vraies URLs propres (sans le # dans l'URL)
import { createRouter, createWebHistory } from 'vue-router';

// On importe le store d'auth pour vérifier si l'utilisateur est connecté dans le gardien
import { useAuthStore } from '../stores/authStore';

// La liste de toutes les routes (pages) de l'application
const routes = [

  // Redirection automatique : si l'utilisateur va sur "/", il est envoyé vers "/login"
  { path: '/', redirect: '/login' },

  // Page de connexion — accessible par tout le monde
  { path: '/login', name: 'Login', component: () => import('../views/Login.vue') },

  // Page d'inscription — accessible par tout le monde
  { path: '/register', name: 'Register', component: () => import('../views/Register.vue') },

  // Page d'administration — PROTÉGÉE : nécessite d'être connecté ET d'être admin
  {
    path: '/admin/users',
    name: 'UserManagement',
    component: () => import('../views/admin/UserManagement.vue'),

    // meta : informations supplémentaires attachées à cette route
    // Le gardien (beforeEach) les lira pour décider si l'accès est autorisé
    meta: {
      requiresAuth: true,          // l'utilisateur doit être connecté
      requiredRole: 'admin'        // l'utilisateur doit avoir le rôle 'admin'
    }
  }
];

// On crée le router avec l'historique propre (pas de # dans l'URL)
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// ─────────────────────────────────────────────────────────
// GARDIEN DE NAVIGATION (Navigation Guard)
// S'exécute AVANT chaque changement de page
// "to" = la page où l'utilisateur veut aller
// "from" = la page d'où il vient
// "next" = la fonction pour autoriser ou bloquer la navigation
// ─────────────────────────────────────────────────────────
router.beforeEach((to, from, next) => {

  // On accède au store pour connaître l'état de connexion
  const authStore = useAuthStore();

  // Règle 1 : La page demande une connexion ET l'utilisateur n'est pas connecté
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // On bloque et on redirige vers la page de connexion
    return next({ name: 'Login' });
  }

  // Règle 2 : La page demande un rôle spécifique ET l'utilisateur n'a pas ce rôle
  if (to.meta.requiredRole && (authStore.currentUser as any)?.role !== to.meta.requiredRole) {
    // Accès interdit — on redirige vers le login
    alert("Accès Refusé : Cette page est réservée aux Administrateurs.");
    return next({ name: 'Login' });
  }

  // Tout est OK → on laisse l'utilisateur accéder à la page demandée
  next();
});

// On exporte le router pour qu'il soit branché sur l'application dans main.ts
export default router;
