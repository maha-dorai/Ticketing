<template>
  <div class="sidebar">
    <div class="sidebar-brand">
      <Ticket class="brand-logo" aria-hidden="true" />
      <span class="brand-text">Ticketing</span>
    </div>

    <nav class="sidebar-nav" aria-label="Navigation principale">
      <template v-if="isAdmin">
        <p class="nav-section">Administration</p>
        <router-link to="/admin/chefs" class="nav-item" active-class="nav-active">
          <Shield class="nav-icon" aria-hidden="true" />
          Chefs de projet
        </router-link>
        <p class="nav-section">Gestion</p>
        <router-link to="/manager/dashboard" class="nav-item" active-class="nav-active">
          <BarChart3 class="nav-icon" aria-hidden="true" />
          Tableau de bord
        </router-link>
        <router-link to="/admin/users" class="nav-item" active-class="nav-active">
          <Users class="nav-icon" aria-hidden="true" />
          Membres
        </router-link>
        <router-link to="/manager/projects" class="nav-item" active-class="nav-active">
          <FolderKanban class="nav-icon" aria-hidden="true" />
          Projets
        </router-link>
      </template>

      <template v-else-if="isManager">
        <p class="nav-section">Gestion</p>
        <router-link to="/manager/dashboard" class="nav-item" active-class="nav-active">
          <BarChart3 class="nav-icon" aria-hidden="true" />
          Tableau de bord
        </router-link>
        <router-link to="/manager/projects" class="nav-item" active-class="nav-active">
          <FolderKanban class="nav-icon" aria-hidden="true" />
          Projets
        </router-link>
      </template>

      <template v-else>
        <p class="nav-section">Espace membre</p>
        <router-link to="/projects" class="nav-item" active-class="nav-active">
          <FolderKanban class="nav-icon" aria-hidden="true" />
          Mes Projets
        </router-link>
        <router-link to="/my-stats" class="nav-item" active-class="nav-active">
          <TrendingUp class="nav-icon" aria-hidden="true" />
          Mes Statistiques
        </router-link>
      </template>

      <p class="nav-section">Compte</p>
      <router-link to="/notifications" class="nav-item" active-class="nav-active">
        <Bell class="nav-icon" aria-hidden="true" />
        Notifications
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  Ticket,
  Shield,
  BarChart3,
  Users,
  FolderKanban,
  TrendingUp,
  Bell,
} from 'lucide-vue-next';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();
const isAdmin = computed(() => authStore.isAdmin());
const isManager = computed(() => authStore.isManager());
</script>

<style scoped>
.sidebar {
  width: 240px;
  min-width: 240px;
  height: 100vh;
  background: #0f172a;
  border-right: 1px solid #1e293b;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  overflow-y: auto;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 1.25rem 1.25rem 1rem;
  border-bottom: 1px solid #1e293b;
}

.brand-logo {
  width: 1.375rem;
  height: 1.375rem;
  color: var(--color-brand-400, #60a5fa);
  flex-shrink: 0;
}

.brand-text {
  font-size: 1.125rem;
  font-weight: 800;
  color: #f8fafc;
  letter-spacing: -0.02em;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-section {
  font-size: 0.6875rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 0.875rem 0.625rem 0.375rem;
  margin: 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5625rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #94a3b8;
  text-decoration: none;
  transition: all 0.15s;
}

.nav-item:hover {
  background: #1e293b;
  color: #f1f5f9;
}

.nav-active {
  background: #1e3a5f !important;
  color: #60a5fa !important;
  font-weight: 600;
}

.nav-icon {
  width: 1.125rem;
  height: 1.125rem;
  flex-shrink: 0;
}

.nav-active .nav-icon {
  color: #60a5fa;
}
</style>
