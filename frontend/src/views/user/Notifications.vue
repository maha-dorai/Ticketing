<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">

      <div class="page-header">
        <div>
          <h1 class="page-title">Notifications</h1>
          <p class="page-sub">Gardez un œil sur l'activité de vos tickets</p>
        </div>
        <button v-if="unreadCount > 0" @click="markAllAsRead" :disabled="marking" class="mark-btn">
          ✓ Tout marquer comme lu
        </button>
      </div>

      <div class="page-content">

        <div v-if="loading" class="loading">Chargement...</div>

        <div v-else-if="notifications.length === 0" class="empty">
          <div class="empty-icon">📭</div>
          <p class="empty-title">Aucune notification.</p>
        </div>

        <div v-else class="notif-list">
          <div
            v-for="notif in notifications"
            :key="notif.id"
            @click="goToTicket(notif)"
            class="notif-item"
            :class="notif.lu ? 'is-read' : 'is-unread'"
          >
            <span class="notif-dot">{{ notif.lu ? '⚪' : '🔵' }}</span>
            <div class="notif-body">
              <p class="notif-msg">{{ notif.message }}</p>
              <p class="notif-time">{{ formatTime(notif.created_at) }}</p>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const router = useRouter();
const notifications = ref([]);
const loading = ref(true);
const marking = ref(false);

const unreadCount = computed(() => notifications.value.filter(n => !n.lu).length);

const fetchNotifications = async () => {
  try {
    const res = await api.get('/notifications');
    notifications.value = res.data;
  } catch (e) {
    console.error('Erreur notifications', e);
  } finally {
    loading.value = false;
  }
};

const markAllAsRead = async () => {
  const unreadIds = notifications.value.filter(n => !n.lu).map(n => n.id);
  if (!unreadIds.length) return;
  marking.value = true;
  try {
    await api.put('/notifications/read', { notification_ids: unreadIds });
    notifications.value.forEach(n => (n.lu = true));
    // Refresh badge dans le sidebar
    document.dispatchEvent(new CustomEvent('notifications-read'));
  } catch (e) {
    console.error(e);
  } finally {
    marking.value = false;
  }
};

const goToTicket = async (notif) => {
  if (!notif.lu) {
    try {
      await api.put('/notifications/read', { notification_ids: [notif.id] });
      notif.lu = true;
      document.dispatchEvent(new CustomEvent('notifications-read'));
    } catch (e) {}
  }

  if (!notif.ticket_id) return;

  const projectId = notif.ticket?.project_id;

  if (projectId) {
    router.push({ name: 'TicketDetails', params: { projectId, id: notif.ticket_id } });
    return;
  }

  try {
    const res = await api.get(`/tickets/${notif.ticket_id}`);
    const pid = res.data?.project_id ?? res.data?.project?.id;
    if (pid) {
      router.push({ name: 'TicketDetails', params: { projectId: pid, id: notif.ticket_id } });
    }
  } catch (e) {
    console.error('Impossible de naviguer vers le ticket', e);
  }
};

onMounted(() => fetchNotifications());

const formatTime = (d) =>
  d ? new Date(d).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
.layout { display: flex; min-height: 100vh; background: #f8fafc; }
.main { flex: 1; overflow-y: auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; padding: 2rem 2.5rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: white; gap: 1rem; flex-wrap: wrap; }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -.02em; }
.page-sub { font-size: .875rem; color: #64748b; margin: .25rem 0 0; }
.mark-btn { padding: .5rem 1.125rem; background: #eff6ff; color: #2563eb; border: none; border-radius: 8px; font-size: .875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: background .15s; }
.mark-btn:hover:not(:disabled) { background: #dbeafe; }
.mark-btn:disabled { opacity: .5; cursor: not-allowed; }
.page-content { padding: 2rem 2.5rem; max-width: 760px; }
.loading { color: #94a3b8; font-size: .875rem; padding: 3rem 0; }
.empty { text-align: center; padding: 5rem 2rem; }
.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-title { font-size: 1rem; font-weight: 600; color: #94a3b8; margin: 0; }
.notif-list { background: white; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
.notif-item { display: flex; align-items: flex-start; gap: .875rem; padding: 1rem 1.25rem; cursor: pointer; transition: background .15s; border-bottom: 1px solid #f1f5f9; }
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #f8fafc; }
.is-unread { background: #eff6ff; }
.is-unread:hover { background: #dbeafe; }
.notif-dot { font-size: 1rem; padding-top: 2px; flex-shrink: 0; }
.notif-body { flex: 1; }
.notif-msg { font-size: .875rem; font-weight: 500; color: #1e293b; margin: 0 0 .25rem; line-height: 1.5; }
.is-read .notif-msg { color: #64748b; font-weight: 400; }
.notif-time { font-size: .75rem; color: #94a3b8; margin: 0; }
</style>