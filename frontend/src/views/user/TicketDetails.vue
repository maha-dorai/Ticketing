<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">

      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement du ticket...</div>

      <div v-else-if="ticket" class="space-y-6 max-w-4xl mx-auto">

        <!-- Header -->
        <div class="page-header">
          <div>
            <button @click="$router.push({ name: 'Tickets', params: { projectId: route.params.projectId } })" class="back-btn">
              ← Retour aux tickets
            </button>
            <h1 class="page-title">🎫 {{ ticket.titre }}</h1>
          </div>
        </div>

        <!-- Confirm dialog -->
        <div v-if="confirmDialog.show" class="confirm-overlay">
          <div class="confirm-box">
            <p class="confirm-msg">{{ confirmDialog.message }}</p>
            <div class="confirm-actions">
              <button @click="confirmDialog.show = false" class="btn-cancel">Annuler</button>
              <button @click="confirmDialog.onConfirm(); confirmDialog.show = false" :class="confirmDialog.danger ? 'btn-danger' : 'btn-confirm'">Confirmer</button>
            </div>
          </div>
        </div>

        <!-- Ticket card -->
        <div class="card flex flex-col md:flex-row gap-6">
          <div class="flex-1 space-y-3">
            <div class="flex flex-wrap gap-2 items-center">
              <span :class="etatClass(ticket.etat)" class="badge">{{ ticket.etat }}</span>
              <span :class="prioriteClass(ticket.priorite)" class="badge">{{ ticket.priorite }}</span>
            </div>
            <p class="text-gray-600 text-sm whitespace-pre-wrap leading-relaxed">{{ ticket.description || 'Aucune description fournie.' }}</p>
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500 pt-3 border-t">
              <span>📁 <b class="text-gray-700">{{ ticket.project?.nom }}</b></span>
              <span>✍️ {{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}</span>
              <span v-if="ticket.assignment_status === 'approved' && ticket.developpeur">
                👨‍💻 <b class="text-gray-700">{{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}</b>
              </span>
              <span v-else-if="ticket.assignment_status === 'pending' && ticket.proposed_developpeur" class="text-amber-600">
                ⏳ Proposition : {{ ticket.proposed_developpeur.prenom }} {{ ticket.proposed_developpeur.nom }}
                <span class="text-xs">(en attente validation chef de projet)</span>
              </span>
              <span v-else class="italic">Non assigné</span>
            </div>
          </div>

          <!-- Actions panel -->
          <div class="actions-panel">
            <h3 class="actions-title">Actions</h3>

            <!-- Chef de projet: valider/refuser assignation -->
            <div v-if="isAdmin && ticket.assignment_status === 'pending' && ticket.etat === 'OUVERT'" class="space-y-2">
              <p class="text-xs text-gray-500">Développeur proposé :</p>
              <p class="text-sm font-bold text-gray-800">{{ ticket.proposed_developpeur?.prenom }} {{ ticket.proposed_developpeur?.nom }}</p>
              <button @click="ask('Valider cette assignation et notifier le développeur ?', acceptTicket)" class="btn-green w-full">✅ Valider l'assignation</button>
              <button @click="ask('Refuser cette assignation ?', rejectTicket, true)" class="btn-gray w-full">❌ Refuser</button>
            </div>

            <!-- Chef de projet: assignation manuelle -->
            <div v-if="isAdmin && ticket.assignment_status !== 'pending' && ticket.assignment_status !== 'approved' && ticket.etat === 'OUVERT'" class="space-y-2">
              <h4 class="text-xs font-bold text-gray-700 uppercase">Assignation manuelle</h4>
              <div v-if="workloads.length === 0" class="text-xs text-gray-400">Aucun développeur disponible</div>
              <div v-else class="space-y-2 max-h-48 overflow-y-auto">
                <div v-for="dev in workloads" :key="dev.id" class="flex items-center justify-between p-2 bg-gray-50 rounded border">
                  <div>
                    <div class="text-xs font-bold">{{ dev.prenom }} {{ dev.nom }}</div>
                    <div class="text-[10px] text-gray-500">Tickets actifs : {{ dev.active_tickets_count }}</div>
                  </div>
                  <button @click="ask(`Assigner ce ticket à ${dev.prenom} ${dev.nom} ?`, () => reassignTicket(dev.id))" class="btn-blue-xs">Assigner</button>
                </div>
              </div>
            </div>

            <!-- Développeur: changer état -->
            <div v-if="currentUser?.role === 'developpeur' && ticket.assignment_status === 'approved' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME'" class="space-y-2">
              <label class="text-xs font-semibold text-gray-600">Changer l'état</label>
              <select v-model="selectedState" @change="changeState" class="w-full px-2 py-1.5 text-sm border rounded outline-none focus:ring-2 focus:ring-blue-200">
                <option value="OUVERT" disabled>OUVERT</option>
                <option value="EN_COURS">EN_COURS</option>
                <option value="RESOLU">RESOLU</option>
              </select>
            </div>

            <!-- Testeur: fermer -->
            <div v-if="currentUser?.role === 'testeur' && ticket.testeur_id === currentUser.id">
              <button v-if="ticket.etat !== 'FERME'" @click="ask('Fermer ce ticket définitivement ?', closeTicket, true)" class="btn-red w-full">Fermer le ticket</button>
              <div v-else class="text-sm text-red-600 font-bold text-center py-2 bg-red-50 rounded">TICKET FERMÉ</div>
            </div>

            <div v-if="stateUpdating" class="text-xs text-blue-500 text-center animate-pulse">Mise à jour...</div>
          </div>
        </div>

        <!-- Commentaires -->
        <div class="card p-0 overflow-hidden flex flex-col">
          <div class="px-6 py-4 border-b bg-gray-50">
            <h2 class="text-base font-bold text-gray-800">Commentaires ({{ ticket.comments?.length || 0 }})</h2>
          </div>

          <div class="p-6 overflow-y-auto space-y-4 max-h-[420px] bg-gray-50" ref="chatBox">
            <div v-if="!ticket.comments?.length" class="text-center text-sm text-gray-400 py-8">Aucun commentaire pour le moment.</div>

            <div
              v-for="comment in ticket.comments"
              :key="comment.id"
              class="flex flex-col max-w-[85%]"
              :class="comment.user_id === currentUser.id ? 'ml-auto items-end' : 'mr-auto items-start'"
            >
              <div class="flex items-center gap-2 mb-1" :class="comment.user_id === currentUser.id ? 'flex-row-reverse' : ''">
                <span class="text-xs font-bold text-gray-700">{{ comment.user?.prenom }} {{ comment.user?.nom }}</span>
                <span class="text-[10px] text-gray-400">{{ formatTime(comment.created_at) }}</span>
                <span v-if="comment.user_id === currentUser.id" class="flex gap-1">
                  <button @click="startEdit(comment)" class="text-gray-400 hover:text-blue-500 text-xs">✏️</button>
                  <button @click="ask('Supprimer ce commentaire ?', () => deleteComment(comment), true)" class="text-gray-400 hover:text-red-500 text-xs">🗑️</button>
                </span>
              </div>

              <div
                v-if="editingCommentId !== comment.id"
                class="px-4 py-2.5 rounded-2xl text-sm shadow-sm"
                :class="comment.user_id === currentUser.id ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white border text-gray-800 rounded-bl-none'"
              >{{ comment.contenu }}</div>

              <div v-else class="flex flex-col gap-2 w-full">
                <textarea v-model="editContent" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm resize-none outline-none focus:ring-2 focus:ring-blue-200 text-gray-800" @keydown.esc="cancelEdit"></textarea>
                <div class="flex gap-2 justify-end">
                  <button @click="cancelEdit" class="btn-gray-xs">Annuler</button>
                  <button @click="saveEdit(comment)" :disabled="!editContent.trim() || savingEdit" class="btn-blue-xs">{{ savingEdit ? '...' : 'Enregistrer' }}</button>
                </div>
              </div>
            </div>
          </div>

          <div class="p-4 border-t bg-white">
            <div class="flex items-end gap-2">
              <textarea v-model="newComment" rows="2" placeholder="Écrire un commentaire..." class="flex-1 px-3 py-2 border rounded-lg text-sm resize-none outline-none focus:ring-2 focus:ring-blue-200" @keydown.enter.prevent="submitComment"></textarea>
              <button @click="submitComment" :disabled="!newComment.trim() || submittingComment" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition disabled:opacity-50">Envoyer</button>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const route       = useRoute();
const router      = useRouter();
const authStore   = useAuthStore();
const currentUser = authStore.currentUser;
const isAdmin     = computed(() => ['chef_de_projet', 'admin'].includes(currentUser?.role));

const ticket        = ref(null);
const loading       = ref(true);
const selectedState = ref('');
const stateUpdating = ref(false);
const chatBox       = ref(null);
const workloads     = ref([]);

const newComment        = ref('');
const submittingComment = ref(false);
const editingCommentId  = ref(null);
const editContent       = ref('');
const savingEdit        = ref(false);

// Confirm dialog state (replaces browser confirm())
const confirmDialog = ref({ show: false, message: '', danger: false, onConfirm: () => {} });
const ask = (message, onConfirm, danger = false) => {
  confirmDialog.value = { show: true, message, onConfirm, danger };
};

const fetchTicket = async () => {
  try {
    const res = await api.get(`/tickets/${route.params.id}`);
    ticket.value = res.data;
    selectedState.value = ticket.value.etat;
    if (isAdmin.value && ticket.value.assignment_status !== 'pending' && ticket.value.assignment_status !== 'approved') {
      fetchWorkloads();
    }
  } catch (e) {
    console.error(e);
    router.push({ name: 'Tickets', params: { projectId: route.params.projectId } });
  } finally {
    loading.value = false;
  }
};

const fetchWorkloads = async () => {
  try {
    const res = await api.get(`/projects/${ticket.value.project_id}/developers/workload`);
    workloads.value = res.data;
  } catch (e) { console.error('Erreur workloads', e); }
};

const acceptTicket = async () => {
  stateUpdating.value = true;
  try { await api.patch(`/tickets/${ticket.value.id}/accept`); await fetchTicket(); }
  catch (e) { alert(e.response?.data?.message || "Erreur lors de l'acceptation"); }
  finally { stateUpdating.value = false; }
};

const rejectTicket = async () => {
  stateUpdating.value = true;
  try { await api.patch(`/tickets/${ticket.value.id}/reject`); await fetchTicket(); }
  catch { alert("Erreur lors du refus"); }
  finally { stateUpdating.value = false; }
};

const reassignTicket = async (devId) => {
  stateUpdating.value = true;
  try { await api.patch(`/tickets/${ticket.value.id}/reassign`, { developpeur_id: devId }); await fetchTicket(); }
  catch { alert("Erreur lors de la réassignation"); }
  finally { stateUpdating.value = false; }
};

const changeState = async () => {
  if (selectedState.value === ticket.value.etat) return;
  stateUpdating.value = true;
  try { await api.put(`/tickets/${ticket.value.id}/status`, { etat: selectedState.value }); ticket.value.etat = selectedState.value; }
  catch { alert("Erreur lors du changement d'état"); selectedState.value = ticket.value.etat; }
  finally { stateUpdating.value = false; }
};

const closeTicket = async () => {
  stateUpdating.value = true;
  try { await api.put(`/tickets/${ticket.value.id}/close`); ticket.value.etat = 'FERME'; }
  catch { alert('Erreur lors de la fermeture du ticket'); }
  finally { stateUpdating.value = false; }
};

const submitComment = async () => {
  if (!newComment.value.trim()) return;
  submittingComment.value = true;
  try {
    const res = await api.post('/comments', { ticket_id: ticket.value.id, contenu: newComment.value.trim() });
    if (!ticket.value.comments) ticket.value.comments = [];
    ticket.value.comments.push(res.data);
    newComment.value = '';
    await nextTick();
    if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight;
  } catch { alert("Erreur lors de l'envoi du commentaire"); }
  finally { submittingComment.value = false; }
};

const startEdit  = (c) => { editingCommentId.value = c.id; editContent.value = c.contenu; };
const cancelEdit = () => { editingCommentId.value = null; editContent.value = ''; };

const saveEdit = async (comment) => {
  if (!editContent.value.trim()) return;
  savingEdit.value = true;
  try { const res = await api.put(`/comments/${comment.id}`, { contenu: editContent.value.trim() }); comment.contenu = res.data.contenu; cancelEdit(); }
  catch { alert('Erreur lors de la modification'); }
  finally { savingEdit.value = false; }
};

const deleteComment = async (comment) => {
  try { await api.delete(`/comments/${comment.id}`); ticket.value.comments = ticket.value.comments.filter(c => c.id !== comment.id); }
  catch { alert('Erreur lors de la suppression'); }
};

onMounted(() => fetchTicket());

const formatTime    = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
const etatClass     = (e) => ({ OUVERT: 'badge-green', EN_COURS: 'badge-yellow', RESOLU: 'badge-blue', FERME: 'badge-gray' }[e] || 'badge-gray');
const prioriteClass = (p) => ({ BASSE: 'badge-gray', MOYENNE: 'badge-blue', HAUTE: 'badge-orange', CRITIQUE: 'badge-red' }[p] || 'badge-gray');
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

.layout { display: flex; min-height: 100vh; background: #f8fafc; }
.main   { flex: 1; padding: 2rem; overflow-y: auto; }

.page-header { margin-bottom: 1.5rem; }
.back-btn    { background: none; border: none; color: #64748b; font-size: 0.85rem; cursor: pointer; font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.25rem; padding: 0; }
.back-btn:hover { color: #1e293b; }
.page-title  { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; }

.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }

.actions-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; min-width: 200px; display: flex; flex-direction: column; gap: 0.75rem; }
.actions-title { font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; margin: 0; }

.badge        { padding: 0.25rem 0.625rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.badge-green  { background: #dcfce7; color: #166534; }
.badge-yellow { background: #fef9c3; color: #854d0e; }
.badge-blue   { background: #dbeafe; color: #1e40af; }
.badge-gray   { background: #f1f5f9; color: #475569; }
.badge-orange { background: #ffedd5; color: #c2410c; }
.badge-red    { background: #fee2e2; color: #991b1b; }

.w-full { width: 100%; }
.btn-green  { padding: 0.5rem 0.75rem; background: #16a34a; color: #fff; border: none; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.btn-green:hover  { background: #15803d; }
.btn-gray   { padding: 0.5rem 0.75rem; background: #e2e8f0; color: #374151; border: none; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.btn-gray:hover   { background: #cbd5e1; }
.btn-red    { padding: 0.5rem 0.75rem; background: #dc2626; color: #fff; border: none; border-radius: 7px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: background 0.15s; }
.btn-red:hover    { background: #b91c1c; }
.btn-blue-xs  { padding: 0.3rem 0.6rem; background: #2563eb; color: #fff; border: none; border-radius: 5px; font-size: 0.7rem; font-weight: 700; cursor: pointer; }
.btn-blue-xs:hover { background: #1d4ed8; }
.btn-gray-xs  { padding: 0.3rem 0.6rem; background: #e2e8f0; color: #374151; border: none; border-radius: 5px; font-size: 0.7rem; font-weight: 600; cursor: pointer; }

/* Confirm dialog */
.confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; z-index: 100; }
.confirm-box     { background: #fff; border-radius: 14px; padding: 2rem; max-width: 380px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.confirm-msg     { font-size: 0.95rem; color: #1e293b; font-weight: 600; margin: 0 0 1.5rem; text-align: center; }
.confirm-actions { display: flex; gap: 0.75rem; justify-content: center; }
.btn-cancel  { padding: 0.55rem 1.25rem; background: #f1f5f9; color: #374151; border: none; border-radius: 8px; font-weight: 600; font-size: 0.875rem; cursor: pointer; }
.btn-confirm { padding: 0.55rem 1.25rem; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; }
.btn-danger  { padding: 0.55rem 1.25rem; background: #dc2626; color: #fff; border: none; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; }
</style>