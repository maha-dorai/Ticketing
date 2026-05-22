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
              
              <!-- Boutons Admin pour l'auto-assignation -->
              <div v-if="!notif.lu && (notif.message.toLowerCase().includes('validation') || notif.message.toLowerCase().includes('assignation'))" class="notif-actions" @click.stop>
                <button @click="acceptAssignment(notif)" class="btn-sm btn-accept">✅ Valider</button>
                <button @click="rejectAssignment(notif)" class="btn-sm btn-reject">❌ Refuser</button>
              </div>
            </div>
          </div>
        </div>

      </div>
      
      <!-- Toast de notification -->
      <div v-if="toast.show" class="toast-message" :class="`toast-${toast.type}`">
        {{ toast.message }}
      </div>

      <!-- Modale de Confirmation Générique -->
      <div v-if="confirmDialog.show" class="modal-overlay" @click.self="cancelConfirm">
        <div class="modal-content modal-sm">
          <h2 class="modal-title">{{ confirmDialog.title }}</h2>
          <p class="modal-desc">{{ confirmDialog.message }}</p>
          <div class="modal-actions">
            <button @click="cancelConfirm" class="btn-sm btn-cancel">Annuler</button>
            <button @click="executeConfirm" class="btn-sm btn-primary">Confirmer</button>
          </div>
        </div>
      </div>
      
      <!-- Modale d'assignation manuelle -->
      <div v-if="showAssignModal" class="modal-overlay" @click.self="closeAssignModal">
        <div class="modal-content">
          <h2 class="modal-title">Assigner Manuellement</h2>
          <p class="modal-desc">Veuillez choisir un développeur pour ce ticket.</p>
          
          <div v-if="loadingDevs" class="loading">Chargement des développeurs...</div>
          <div v-else-if="projectDevs.length === 0" class="empty">Aucun développeur disponible sur ce projet.</div>
          <div v-else class="dev-list">
            <select v-model="selectedDevId" class="form-select">
              <option disabled value="">Sélectionnez un développeur...</option>
              <option v-for="dev in projectDevs" :key="dev.id" :value="dev.id">
                {{ dev.prenom }} {{ dev.nom }} ({{ dev.active_tickets_count }} tickets actifs)
              </option>
            </select>
          </div>
          
          <div class="modal-actions">
            <button @click="closeAssignModal" class="btn-sm btn-cancel">Annuler</button>
            <button @click="submitReassign" class="btn-sm btn-primary" :disabled="!selectedDevId || assignLoading">
              {{ assignLoading ? 'Assignation...' : 'Assigner' }}
            </button>
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

const showAssignModal = ref(false);
const selectedTicketId = ref(null);
const projectDevs = ref([]);
const selectedDevId = ref('');
const loadingDevs = ref(false);
const assignLoading = ref(false);

const toast = ref({ show: false, message: '', type: 'success' });
let toastTimeout = null;

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

const confirmDialog = ref({ show: false, title: '', message: '', onConfirm: null });

const showConfirm = (title, message, onConfirmCallback) => {
  confirmDialog.value = { show: true, title, message, onConfirm: onConfirmCallback };
};

const cancelConfirm = () => {
  confirmDialog.value.show = false;
};

const executeConfirm = () => {
  if (confirmDialog.value.onConfirm) {
    confirmDialog.value.onConfirm();
  }
  confirmDialog.value.show = false;
};

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

const acceptAssignment = async (notif) => {
  if (!notif.ticket_id) return;
  showConfirm('Confirmation', 'Voulez-vous vraiment valider cette assignation ?', async () => {
    try {
      await api.patch(`/tickets/${notif.ticket_id}/accept`);
      showToast('Assignation validée avec succès !', 'success');
      notifications.value = notifications.value.filter(n => n.id !== notif.id);
      document.dispatchEvent(new CustomEvent('notifications-read'));
    } catch (e) {
      showToast("Erreur lors de la validation : " + (e.response?.data?.message || e.message || "Erreur inconnue"), 'error');
    }
  });
};

const rejectAssignment = async (notif) => {
  if (!notif.ticket_id) return;
  showConfirm('Refuser l\'assignation', 'Voulez-vous refuser cette assignation ? Vous devrez assigner manuellement un développeur.', async () => {
    try {
      await api.patch(`/tickets/${notif.ticket_id}/reject`);
      
      // Supprimer la notification pour empêcher le double-clic
      notifications.value = notifications.value.filter(n => n.id !== notif.id);
      document.dispatchEvent(new CustomEvent('notifications-read'));
      
      // Ouvrir la modale d'assignation
      selectedTicketId.value = notif.ticket_id;
      if (notif.ticket && notif.ticket.project_id) {
        await fetchProjectDevs(notif.ticket.project_id);
      }
      showAssignModal.value = true;
      
    } catch (e) {
      showToast("Erreur lors du refus : " + (e.response?.data?.message || e.message || "Erreur inconnue"), 'error');
    }
  });
};

const fetchProjectDevs = async (projectId) => {
  if (!projectId) return;
  loadingDevs.value = true;
  projectDevs.value = [];
  try {
    const res = await api.get(`/projects/${projectId}/developers/workload`);
    // res.data should be an array directly or an object with data? ProjectController returns `return response()->json($developers, 200);` if mapped.
    // Wait, let's look closely at `ProjectController@getDevelopersWorkload`.
    projectDevs.value = res.data;
  } catch (error) {
    console.error('Erreur devs', error);
  } finally {
    loadingDevs.value = false;
  }
};

const closeAssignModal = () => {
  showAssignModal.value = false;
  selectedTicketId.value = null;
  selectedDevId.value = '';
  projectDevs.value = [];
};

const submitReassign = async () => {
  if (!selectedDevId.value || !selectedTicketId.value) return;
  assignLoading.value = true;
  try {
    await api.patch(`/tickets/${selectedTicketId.value}/reassign`, {
      developpeur_id: selectedDevId.value
    });
    showToast('Le ticket a été réassigné manuellement avec succès.', 'success');
    closeAssignModal();
  } catch (e) {
    showToast("Erreur lors de la réassignation : " + (e.response?.data?.message || e.message), 'error');
  } finally {
    assignLoading.value = false;
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
.notif-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.btn-sm { padding: 4px 10px; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer; border: 1px solid transparent; transition: background 0.15s; }
.btn-accept { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
.btn-accept:hover { background: #bbf7d0; }
.btn-reject { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.btn-reject:hover { background: #fecaca; }

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

.modal-content {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-dark);
  margin-bottom: 0.5rem;
}

.modal-desc {
  font-size: 0.9rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}

.form-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
  background: #f8fafc;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-cancel {
  background: #f1f5f9;
  color: #475569;
}

.btn-cancel:hover {
  background: #e2e8f0;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-sm {
  max-width: 320px;
}

.toast-message {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  z-index: 9999;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  animation: slideUp 0.3s ease;
}

.toast-success {
  background-color: #10b981;
}

.toast-error {
  background-color: #ef4444;
}

@keyframes slideUp {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
</style>