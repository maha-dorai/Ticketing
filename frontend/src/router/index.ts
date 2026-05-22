import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

const routes = [
  { path: '/', redirect: '/login' },

  { path: '/login',                 name: 'Login',          component: () => import('../views/Login.vue') },
  { path: '/register',              name: 'Register',       component: () => import('../views/Register.vue') },
  { path: '/forgot-password',       name: 'ForgotPassword', component: () => import('../views/ForgotPassword.vue') },
  { path: '/reset-password/:token', name: 'ResetPassword',  component: () => import('../views/ResetPassword.vue') },

  {
    path: '/change-password-required',
    name: 'ForceChangePassword',
    component: () => import('../views/ForceChangePassword.vue'),
    meta: { requiresAuth: true },
  },

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
    path: '/projects/:id',
    name: 'ProjectDetail',
    component: () => import('../views/user/ProjectDetail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/projects/:projectId/tickets',
    name: 'Tickets',
    component: () => import('../views/user/Tickets.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/projects/:projectId/tickets/:id',
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

  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('../views/admin/Dashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/my-stats',
    name: 'MyStats',
    component: () => import('../views/user/MyStats.vue'),
    meta: { requiresAuth: true },
  },
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
  {
    path: '/admin/projects/:projectId/tickets',
    name: 'AdminTickets',
    component: () => import('../views/user/Tickets.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },

  {
    path: '/admin/admins',
    name: 'AdminManagement',
    component: () => import('../views/admin/AdminManagement.vue'),
    meta: { requiresAuth: true, requiresFullAdmin: true },
  },

  { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// الصفحات العامة — ما تتأثرش بأي guard
const publicRoutes = ['Login', 'Register', 'ForgotPassword', 'ResetPassword'];

router.beforeEach((to, _from) => {
  const authStore = useAuthStore();

  // 1. صفحة محمية وما دخلش بعد
  if (to.meta.requiresAuth && !authStore.isAuthenticated)
    return { name: 'Login' };

  // 2. مجبر يغيّر كلمة المرور
  if (
    authStore.isAuthenticated &&
    authStore.forcePasswordChange &&
    to.name !== 'ForceChangePassword' &&
    !publicRoutes.includes(to.name as string)
  ) {
    return { name: 'ForceChangePassword' };
  }

  // 3. صفحة admin
  if (to.meta.requiresAdmin && !authStore.isAdmin())
    return { name: 'Login' };

  // 4. صفحة admin فقط (ليس chef_de_projet)
  if (to.meta.requiresFullAdmin && !authStore.isSuperAdmin())
    return { name: 'Login' };

  return true;
});

export default router;