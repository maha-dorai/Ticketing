import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

const routes = [
  { path: '/', redirect: '/login' },

  { path: '/login',                 name: 'Login',          component: () => import('../views/Login.vue') },
  { path: '/register',              name: 'Register',       component: () => import('../views/Register.vue') },
  { path: '/forgot-password',       name: 'ForgotPassword', component: () => import('../views/ForgotPassword.vue') },
  { path: '/reset-password/:token', name: 'ResetPassword',  component: () => import('../views/ResetPassword.vue') },

  // Page obligatoire si force_password_change = true
  {
    path: '/change-password-required',
    name: 'ForceChangePassword',
    component: () => import('../views/ForceChangePassword.vue'),
    meta: { requiresAuth: true },
  },

  // ── Utilisateur ──────────────────────────────────────────────────────────────
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('../views/user/Profile.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/projects',
    name: 'Projects',
    component: () => import('../views/user/Projects.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/tickets',
    name: 'Tickets',
    component: () => import('../views/user/Tickets.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/tickets/:id',
    name: 'TicketDetails',
    component: () => import('../views/user/TicketDetails.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('../views/user/Notifications.vue'),
    meta: { requiresAuth: true },
  },

  // ── Admin (admin + super_admin) ──────────────────────────────────────────────
  {
    path: '/admin/users',
    name: 'UserManagement',
    component: () => import('../views/admin/UserManagement.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/users/:id/edit',
    name: 'EditUser',
    component: () => import('../views/admin/EditUser.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/projects',
    name: 'ProjectManagement',
    component: () => import('../views/admin/ProjectManagement.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },

  // ── Super Admin uniquement ────────────────────────────────────────────────────
  {
    path: '/super-admin/admins',
    name: 'AdminManagement',
    component: () => import('../views/super-admin/AdminManagement.vue'),
    meta: { requiresAuth: true, requiresSuperAdmin: true },
  },

  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, _from) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated)
    return { name: 'Login' };

  if (
    authStore.isAuthenticated &&
    authStore.forcePasswordChange &&
    to.name !== 'ForceChangePassword' &&
    to.name !== 'Login'
  ) {
    return { name: 'ForceChangePassword' };
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin())
    return { name: 'Login' };

  if (to.meta.requiresSuperAdmin && !authStore.isSuperAdmin())
    return { name: 'Login' };

  return true;
});

export default router;