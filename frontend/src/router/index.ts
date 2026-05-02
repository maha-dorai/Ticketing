import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

const routes = [
  { path: '/', redirect: '/login' },

  { path: '/login',                 name: 'Login',          component: () => import('../views/Login.vue') },
  { path: '/register',              name: 'Register',       component: () => import('../views/Register.vue') },
  { path: '/forgot-password',       name: 'ForgotPassword', component: () => import('../views/ForgotPassword.vue') },
  { path: '/reset-password/:token', name: 'ResetPassword',  component: () => import('../views/ResetPassword.vue') },

  // ── Utilisateur ───────────────────────────────────────────────────────────────
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('../views/user/Profile.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/projects',
    name: 'Projects',
    component: () => import('../views/user/Projects.vue'),
    meta: { requiresAuth: true }
  },

  // ── Admin ─────────────────────────────────────────────────────────────────────
  {
    path: '/admin/users',
    name: 'UserManagement',
    component: () => import('../views/admin/UserManagement.vue'),
    meta: { requiresAuth: true, requiredRole: 'admin' }
  },
  {
    path: '/admin/users/:id/edit',
    name: 'EditUser',
    component: () => import('../views/admin/EditUser.vue'),
    meta: { requiresAuth: true, requiredRole: 'admin' }
  },
  {
    path: '/admin/projects',
    name: 'ProjectManagement',
    component: () => import('../views/admin/ProjectManagement.vue'),
    meta: { requiresAuth: true, requiredRole: 'admin' }
  },

  // Redirection silencieuse pour toute route inconnue
  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated)
    return next({ name: 'Login' });

  if (to.meta.requiredRole && authStore.currentUser?.role !== to.meta.requiredRole)
    return next({ name: 'Login' });

  next();
});

export default router;