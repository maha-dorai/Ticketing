<template>
  <div class="sidebar">
    <!-- Logo -->
    <div class="sidebar-brand">
      <span class="brand-icon">🎫</span>
      <span class="brand-text">Ticketing</span>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">
      <!-- Admin uniquement -->
      <template v-if="isAdmin">
        <p class="nav-section">Administration</p>
        <router-link to="/admin/chefs" class="nav-item" active-class="nav-active">
          <span class="nav-icon">🛡️</span> Chefs de projet
        </router-link>
        <p class="nav-section">Gestion</p>
        <router-link to="/manager/dashboard" class="nav-item" active-class="nav-active">
          <span class="nav-icon">📊</span> Tableau de bord
        </router-link>
        <router-link to="/admin/users" class="nav-item" active-class="nav-active">
          <span class="nav-icon">👥</span> Membres
        </router-link>
        <router-link to="/manager/projects" class="nav-item" active-class="nav-active">
          <span class="nav-icon">📁</span> Projets
        </router-link>
      </template>

      <!-- Chef de projet -->
      <template v-else-if="isManager">
        <p class="nav-section">Gestion</p>
        <router-link to="/manager/dashboard" class="nav-item" active-class="nav-active">
          <span class="nav-icon">📊</span> Tableau de bord
        </router-link>
        <router-link to="/manager/projects" class="nav-item" active-class="nav-active">
          <span class="nav-icon">📁</span> Projets
        </router-link>
      </template>

      <!-- Member -->
      <template v-else>
        <p class="nav-section">Espace membre</p>
        <router-link to="/projects" class="nav-item" active-class="nav-active">
          <span class="nav-icon">📁</span> Mes Projets
        </router-link>
        <router-link to="/my-stats" class="nav-item" active-class="nav-active">
          <span class="nav-icon">📈</span> Mes Statistiques
        </router-link>
      </template>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();
const isAdmin   = computed(() => authStore.isAdmin());
const isManager = computed(() => authStore.isManager());
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

.sidebar {
  width: 240px; min-width: 240px; height: 100vh;
  background: #0f172a;
  border-right: 1px solid #1e293b;
  display: flex; flex-direction: column;
  position: sticky; top: 0;
  overflow-y: auto;
}

.sidebar-brand {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 1.25rem 1.25rem 1rem;
  border-bottom: 1px solid #1e293b;
}
.brand-icon { font-size: 1.375rem; }
.brand-text { font-size: 1.125rem; font-weight: 800; color: #f8fafc; letter-spacing: -0.02em; }

.sidebar-nav { flex: 1; padding: 1rem 0.75rem; display: flex; flex-direction: column; gap: 2px; }

.nav-section {
  font-size: 0.6875rem; font-weight: 700; color: #475569;
  text-transform: uppercase; letter-spacing: 0.08em;
  padding: 0.875rem 0.625rem 0.375rem; margin: 0;
}

.nav-item {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 0.5625rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem; font-weight: 500; color: #94a3b8;
  text-decoration: none;
  transition: all 0.15s;
}
.nav-item:hover { background: #1e293b; color: #f1f5f9; }
.nav-active { background: #1e3a5f !important; color: #60a5fa !important; font-weight: 600; }
.nav-icon { font-size: 1rem; width: 1.25rem; text-align: center; }

.nav-divider { height: 1px; background: #1e293b; margin: 0.5rem 0; }

.sidebar-footer {
  padding: 0.875rem 1rem;
  border-top: 1px solid #1e293b;
  display: flex; align-items: center; gap: 0.625rem;
}
.avatar {
  width: 32px; height: 32px; border-radius: 8px;
  background: #1e3a5f; color: #60a5fa;
  font-size: 0.75rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; text-transform: uppercase;
}
.footer-info { flex: 1; min-width: 0; }
.footer-name { font-size: 0.8125rem; font-weight: 600; color: #e2e8f0; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.footer-role { font-size: 0.6875rem; color: #475569; }
.logout-btn {
  background: none; border: none; cursor: pointer;
  color: #475569; padding: 4px; border-radius: 6px;
  display: flex; align-items: center;
  transition: color 0.15s;
  flex-shrink: 0;
}
.logout-btn:hover { color: #ef4444; }

.nav-item { position: relative; }
.notif-badge {
  margin-left: auto;
  background: #ef4444;
  color: white;
  font-size: 0.625rem;
  font-weight: 800;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
  animation: pulse-badge 2s infinite;
}
@keyframes pulse-badge {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}
</style>