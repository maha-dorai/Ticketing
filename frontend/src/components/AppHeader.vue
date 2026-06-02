<template>
  <header class="app-header">
    <div class="header-right">
      
      <!-- Notifications Dropdown -->
      <div class="dropdown" @click.stop="toggleNotifs">
        <button class="icon-btn relative">
          <Bell :size="22" aria-hidden="true" />
          <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </button>

        <!-- Menu Notifications -->
        <div v-if="showNotifs" class="dropdown-menu notif-menu shadow-lg">
          <div class="dropdown-header">
            <h4>Notifications</h4>
            <span class="text-xs text-blue-600 font-bold bg-blue-50 px-2 py-1 rounded-md">{{ unreadCount }} non lues</span>
          </div>
          <div class="notif-list custom-scrollbar">
            <div v-if="notifications.length === 0" class="p-4 text-center text-sm text-gray-500">
              Aucune notification.
            </div>
            <div 
              v-for="n in notifications" 
              :key="n.id" 
              class="notif-item"
              :class="{ 'unread': !n.lu }"
              @click="markAsReadAndGo(n)"
            >
              <Bell class="notif-icon" aria-hidden="true" />
              <div class="notif-content">
                <p class="notif-text">{{ n.message }}</p>
                <span class="notif-time">{{ timeAgo(n.created_at) }}</span>
              </div>
            </div>
          </div>
          <div class="dropdown-footer">
            <button @click="goToNotifications" class="text-sm text-blue-600 font-bold hover:underline w-full text-center p-2">
              Voir toutes les notifications
            </button>
          </div>
        </div>
      </div>

      <!-- Profile Dropdown -->
      <div class="dropdown" @click.stop="toggleProfile">
        <button class="profile-btn">
          <div class="avatar">{{ initials }}</div>
          <div class="profile-info">
            <span class="profile-name">{{ user?.prenom }} {{ user?.nom }}</span>
            <span class="profile-role">{{ roleLabel }}</span>
          </div>
          <ChevronDown :size="16" class="ml-1 text-gray-400" aria-hidden="true" />
        </button>

        <!-- Menu Profile -->
        <div v-if="showProfile" class="dropdown-menu profile-menu shadow-lg">
          <router-link to="/profile" class="dropdown-item">
            <User :size="16" aria-hidden="true" />
            Mon Profil
          </router-link>
          <div class="divider"></div>
          <button @click="doLogout" class="dropdown-item text-red-600 hover:bg-red-50">
            <LogOut :size="16" aria-hidden="true" />
            Se déconnecter
          </button>
        </div>
      </div>

    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Bell, ChevronDown, LogOut, User } from "lucide-vue-next";
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

import api from '../services/api';
import echo from '../plugins/echo';

const authStore = useAuthStore();
const router = useRouter();

const user = computed(() => authStore.currentUser);
const unreadCount = ref(0);
const notifications = ref([]);

const showNotifs = ref(false);
const showProfile = ref(false);

const initials = computed(() => {
  const u = user.value;
  if (!u) return '?';
  return (u.prenom?.[0] || '') + (u.nom?.[0] || '');
});

const roleLabel = computed(() => ({
  admin: 'Admin',
  chef_de_projet: 'Chef de projet',
  developpeur: 'Développeur',
  testeur: 'Testeur',
}[user.value?.role] || ''));

const toggleNotifs = () => {
  showNotifs.value = !showNotifs.value;
  showProfile.value = false;
  if (showNotifs.value) fetchRecentNotifs();
};

const toggleProfile = () => {
  showProfile.value = !showProfile.value;
  showNotifs.value = false;
};

// Close dropdowns on outside click
const closeDropdowns = () => {
  showNotifs.value = false;
  showProfile.value = false;
};

const fetchUnreadCount = async () => {
  try {
    const res = await api.get('/notifications/unread-count');
    unreadCount.value = res.data.count;
  } catch {}
};

const fetchRecentNotifs = async () => {
  try {
    // On récupère les 5 dernières notifs
    const res = await api.get('/notifications');
    notifications.value = res.data.slice(0, 5);
  } catch {}
};

const markAsReadAndGo = async (notif) => {
  if (!notif.lu) {
    try {
      await api.put('/notifications/read', { notification_ids: [notif.id] });
      notif.lu = true;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
      document.dispatchEvent(new Event('notifications-read'));
    } catch {}
  }
  showNotifs.value = false;
  if (notif.ticket_id) {
    const projectId = notif.ticket?.project_id;
    if (projectId) {
      router.push(`/projects/${projectId}/tickets/${notif.ticket_id}`);
    } else {
      router.push('/notifications');
    }
  }
};

const goToNotifications = () => {
  showNotifs.value = false;
  router.push('/notifications');
};

const doLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const timeAgo = (dateStr) => {
  if (!dateStr) return '';
  const diff = new Date() - new Date(dateStr);
  const minutes = Math.floor(diff / 60000);
  if (minutes < 60) return `${minutes} min`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} h`;
  return `${Math.floor(hours / 24)} j`;
};

let pollInterval = null;
let echoChannel = null;

onMounted(() => {
  fetchUnreadCount();
  pollInterval = setInterval(fetchUnreadCount, 30000);
  document.addEventListener('notifications-read', fetchUnreadCount);
  document.addEventListener('click', closeDropdowns);

  const userId = user.value?.id;
  if (userId) {
    echoChannel = echo.private(`user.${userId}`)
      .listen('.notification.new', (e) => {
        unreadCount.value++;
        if (showNotifs.value) {
          fetchRecentNotifs(); // Rafraichir le menu ouvert
        }
      });
  }
});

onUnmounted(() => {
  clearInterval(pollInterval);
  document.removeEventListener('notifications-read', fetchUnreadCount);
  document.removeEventListener('click', closeDropdowns);
  if (echoChannel) {
    echo.leave(`user.${user.value?.id}`);
  }
});
</script>

<style scoped>
.app-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  height: 64px;
  background-color: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 2rem;
  z-index: 40;
  position: relative;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

/* Icon Buttons */
.icon-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.icon-btn:hover {
  background-color: #f1f5f9;
  color: #3b82f6;
}
.notif-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background-color: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.1rem 0.3rem;
  border-radius: 999px;
  border: 2px solid white;
}

/* Profile Button */
.profile-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 99px;
  transition: all 0.2s;
}
.profile-btn:hover {
  background-color: #f8fafc;
}
.avatar {
  width: 36px;
  height: 36px;
  background-color: #eff6ff;
  color: #2563eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  border: 2px solid white;
  box-shadow: 0 0 0 1px #e2e8f0;
}
.profile-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
}
.profile-name {
  font-size: 0.875rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}
.profile-role {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

/* Dropdowns */
.dropdown {
  position: relative;
}
.dropdown-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  z-index: 50;
  overflow: hidden;
  animation: slideDown 0.2s ease-out;
}
.profile-menu {
  width: 200px;
  padding: 0.5rem;
}
.notif-menu {
  width: 320px;
  display: flex;
  flex-direction: column;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Dropdown items */
.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.15s;
  background: none;
  border: none;
  width: 100%;
  cursor: pointer;
  text-align: left;
}
.dropdown-item:hover {
  background-color: #f1f5f9;
  color: #1e293b;
}
.divider {
  height: 1px;
  background-color: #e2e8f0;
  margin: 0.25rem 0;
}

/* Notifications List */
.dropdown-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  background-color: #f8fafc;
}
.dropdown-header h4 {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
}
.notif-list {
  max-height: 300px;
  overflow-y: auto;
}
.notif-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.15s;
}
.notif-item:hover {
  background-color: #f8fafc;
}
.notif-item.unread {
  background-color: #eff6ff;
}
.notif-icon {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
  color: var(--color-brand-500, #3b82f6);
}
.notif-content {
  flex: 1;
}
.notif-text {
  margin: 0 0 0.25rem 0;
  font-size: 0.8125rem;
  color: #334155;
  line-height: 1.4;
}
.notif-item.unread .notif-text {
  font-weight: 700;
  color: #1e293b;
}
.notif-time {
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 500;
}
.dropdown-footer {
  border-top: 1px solid #e2e8f0;
  background: #ffffff;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
</style>
