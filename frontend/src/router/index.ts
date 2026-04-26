// On importe les fonctions fondamentales pour créer un écosystème de pages virtuelles (Router) de chez Vue
import { createRouter, createWebHistory } from 'vue-router';
// On importe le store contenant la gestion avancée de connexion afin de valider les accès
import { useAuthStore } from '../stores/authStore';

// Le dictionnaire officiel : un catalogue classé décrivant formellement l'index de toutes vos pages
const routes = [
  // Redirection d'office des requêtes racines
  { path: '/', redirect: '/login' },
  // Route classique de la page de Connexion
  { path: '/login', name: 'Login', component: () => import('../views/Login.vue') },
  // NOUVELLE ROUTE : Création d'une page '/register' purement accessible par tous qui lancera le "Register.vue"
  { path: '/register', name: 'Register', component: () => import('../views/Register.vue') },
  
  // La route classifiée "Admin" sécurisée
  { 
    path: '/admin/users',
    name: 'UserManagement', 
    component: () => import('../views/admin/UserManagement.vue'),
    // Tagging personnalisable untuk l'intercepteur de sécurité global 
    meta: { 
      requiresAuth: true, 
      requiredRole: 'admin' 
    }
  }
];

// Instanciation logicielle du router 
const router = createRouter({
  // Efface le '#' des URLs ! Rendu propre. 
  history: createWebHistory(),
  routes,
});

// Gardien (Guard Navigation). Intercepte toute interaction ou click menant vers n'importe quel "to" (Géré par les routeurs)
router.beforeEach((to, from, next) => {
  // L'historisation du Store permet un croisement direct des infos : Sommes nous connectés ? 
  const authStore = useAuthStore();
  
  // Règle 1 :  On exige d'être connécté MAIS l'utilisateur NE L'EST PAS (Il essaye de forcer le chemin /admin/users à la main)
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Redirection stricte par code au composant nomé 'Login' 
    return next({ name: 'Login' });
  }

  // Règle 2 : Ségrégation de Pouvoirs (SuperAdmins Only). Validation d'un Méta rôle.
// بدل 'Administrateur' → 'admin'
if (to.meta.requiredRole && authStore.currentUser?.role !== to.meta.requiredRole) {
      alert("Accès Refusé : Cette page est réservée aux Administrateurs.");
    // Interdiction ferme 
    return next({ name: 'Login' });
  }

  // Fin. Si tout est correct, le router achève le processus de téléportation par "next" 
  next();
});

// Offre l'outil validé (Vue Router) prêt pour le point d'entrée "main.ts" de l'application !
export default router;
