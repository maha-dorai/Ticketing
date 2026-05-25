<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">

      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement du ticket...</div>

      <div v-else-if="ticket" class="space-y-6 max-w-5xl mx-auto pb-12">

        <!-- Header -->
        <div class="flex items-center justify-between mb-2">
          <button @click="$router.push({ name: 'Tickets', params: { projectId: route.params.projectId } })" class="text-blue-500 hover:text-blue-700 font-bold text-sm flex items-center gap-2 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Retour aux tickets
          </button>
          <div class="flex gap-2">
            <span :class="prioriteClass(ticket.priorite)" class="px-3 py-1 text-xs font-bold rounded-full border bg-white shadow-sm">{{ ticket.priorite }}</span>
          </div>
        </div>

        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight leading-tight">🎫 {{ ticket.titre }}</h1>

        <!-- Confirm dialog -->
        <div v-if="confirmDialog.show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-sm w-full transform scale-100 transition-all">
            <p class="text-slate-800 font-bold mb-6 text-center text-lg">{{ confirmDialog.message }}</p>
            <div class="flex justify-center gap-3">
              <button @click="confirmDialog.show = false" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Annuler</button>
              <button @click="confirmDialog.onConfirm(); confirmDialog.show = false" :class="confirmDialog.danger ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-600 hover:bg-blue-700'" class="px-5 py-2.5 rounded-xl font-bold text-white transition shadow-md">Confirmer</button>
            </div>
          </div>
        </div>

        <!-- Timeline Interactive (Drag & Drop) -->
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-6 border border-slate-100">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-extrabold text-slate-700 uppercase tracking-widest flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-blue-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Pipeline d'état
            </h3>
            <span v-if="stateUpdating" class="text-xs text-blue-500 font-bold animate-pulse">Synchronisation...</span>
          </div>

          <div class="flex flex-col md:flex-row items-stretch gap-3 h-auto md:h-28">
            <div
              v-for="col in columns"
              :key="col.etat"
              class="flex-1 rounded-xl border-2 transition-all relative overflow-hidden flex flex-col justify-center items-center p-3"
              :class="[
                dragTarget === col.etat ? 'border-blue-400 bg-blue-50 scale-[1.02] shadow-inner' : 'border-slate-100 bg-slate-50',
                ticket.etat === col.etat ? 'border-blue-200 bg-blue-50/50' : ''
              ]"
              @dragover.prevent="onDragOver(col.etat)"
              @drop.prevent="onDrop(col.etat)"
            >
              <span class="text-[11px] font-extrabold text-slate-500 mb-3 uppercase tracking-wider text-center">{{ col.label }}</span>
              
              <div
                v-if="ticket.etat === col.etat"
                class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-bold px-5 py-2.5 rounded-full shadow-lg shadow-blue-600/30 transition-transform flex items-center gap-2"
                :class="canDragTicket ? 'cursor-grab active:cursor-grabbing hover:scale-105' : 'opacity-70 cursor-not-allowed'"
                :draggable="canDragTicket"
                @dragstart="onDragStart"
                @dragend="onDragEnd"
              >
                <span v-if="canDragTicket">✋ Glissez-moi</span>
                <span v-else>🔒 Actuel</span>
              </div>
            </div>
          </div>
          <p v-if="!canDragTicket" class="text-xs text-center text-slate-400 mt-4 font-medium italic">
            {{ isManager ? 'En tant que manager, vous êtes en lecture seule sur le flux Kanban.' : 'Vous n\'avez pas les droits de modifier cet état ou le ticket ne vous est pas assigné.' }}
          </p>
        </div>

        <!-- Ticket layout: Details (Left) + Sidebar (Right) -->
        <div class="flex flex-col lg:flex-row gap-6">
          
          <!-- Left Content -->
          <div class="flex-1 space-y-6">
            
            <!-- Description Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-8 border border-slate-100">
              <div class="prose prose-slate max-w-none text-sm leading-relaxed" v-html="formatDescription(ticket.description || 'Aucune description fournie.')"></div>
              
              <!-- Attachments -->
              <div v-if="ticket.attachments?.length" class="mt-8 pt-6 border-t border-slate-100">
                <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                  📎 Pièces jointes ({{ ticket.attachments.length }})
                </h4>
                <div class="flex flex-wrap gap-3">
                  <a v-for="att in ticket.attachments" :key="att.id" :href="'http://localhost:8000/storage/' + att.file_path" target="_blank" class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 hover:border-blue-400 hover:bg-blue-50 hover:shadow-md rounded-xl text-sm font-medium text-blue-700 transition-all group">
                    <span class="text-xl group-hover:scale-110 transition-transform">📄</span>
                    <span class="truncate max-w-[200px]">{{ att.file_name }}</span>
                  </a>
                </div>
              </div>
            </div>

            <!-- Comments Section -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 overflow-hidden border border-slate-100 flex flex-col">
              <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest flex items-center gap-2">💬 Commentaires ({{ ticket.comments?.length || 0 }})</h2>
              </div>

              <div class="p-8 overflow-y-auto space-y-6 max-h-[500px]" ref="chatBox">
                <div v-if="!ticket.comments?.length" class="text-center text-sm text-slate-400 py-10 font-medium italic">Aucun commentaire pour le moment.</div>

                <div
                  v-for="comment in ticket.comments"
                  :key="comment.id"
                  class="flex flex-col max-w-[85%]"
                  :class="comment.user_id === currentUser.id ? 'ml-auto items-end' : 'mr-auto items-start'"
                >
                  <div class="flex items-center gap-2 mb-1.5" :class="comment.user_id === currentUser.id ? 'flex-row-reverse' : ''">
                    <span class="text-xs font-bold text-slate-700">{{ comment.user?.prenom }} {{ comment.user?.nom }}</span>
                    <span class="text-[10px] font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ formatTime(comment.created_at) }}</span>
                    <span v-if="comment.user_id === currentUser.id" class="flex gap-1 ml-1 opacity-0 group-hover:opacity-100 transition">
                      <button @click="startEdit(comment)" class="text-slate-400 hover:text-blue-500 text-xs transition">✏️</button>
                      <button @click="ask('Supprimer ce commentaire ?', () => deleteComment(comment), true)" class="text-slate-400 hover:text-red-500 text-xs transition">🗑️</button>
                    </span>
                  </div>

                  <div
                    v-if="editingCommentId !== comment.id"
                    class="px-5 py-3.5 rounded-2xl text-sm shadow-sm group relative"
                    :class="comment.user_id === currentUser.id ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-br-none shadow-blue-600/20' : 'bg-slate-50 border border-slate-200 text-slate-800 rounded-bl-none'"
                  >
                    {{ comment.contenu }}
                  </div>

                  <div v-else class="flex flex-col gap-2 w-full mt-1">
                    <textarea v-model="editContent" rows="2" class="w-full px-4 py-3 border border-blue-200 rounded-xl text-sm resize-none outline-none focus:ring-4 focus:ring-blue-500/20 text-slate-800 bg-blue-50" @keydown.esc="cancelEdit"></textarea>
                    <div class="flex gap-2 justify-end">
                      <button @click="cancelEdit" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition">Annuler</button>
                      <button @click="saveEdit(comment)" :disabled="!editContent.trim() || savingEdit" class="px-4 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg shadow-md hover:bg-blue-700 transition">{{ savingEdit ? '...' : 'Enregistrer' }}</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="p-5 border-t border-slate-100 bg-slate-50">
                <div class="flex items-end gap-3">
                  <textarea v-model="newComment" rows="2" placeholder="Ajouter un commentaire..." class="flex-1 px-4 py-3 border-slate-200 rounded-xl text-sm resize-none outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 shadow-sm" @keydown.enter.prevent="submitComment"></textarea>
                  <button @click="submitComment" :disabled="!newComment.trim() || submittingComment" class="px-6 py-3 bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">Envoyer</button>
                </div>
              </div>
            </div>

          </div>

          <!-- Right Sidebar (Actions & Info) -->
          <div class="w-full lg:w-80 space-y-6 flex-shrink-0">
            
            <!-- Info Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-6 border border-slate-100 space-y-4">
              <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest border-b border-slate-100 pb-3">Informations</h3>
              
              <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                  <span class="text-slate-500 font-medium">Projet</span>
                  <span class="font-bold text-slate-800 truncate max-w-[140px]">{{ ticket.project?.nom }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-500 font-medium">Testeur</span>
                  <span class="font-bold text-slate-800">{{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}</span>
                </div>
                
                <div class="pt-3 border-t border-slate-100 flex flex-col gap-1">
                  <span class="text-slate-500 font-medium">Assignation</span>
                  <span v-if="ticket.assignment_status === 'approved' && ticket.developpeur" class="font-bold text-blue-700 bg-blue-50 py-1.5 px-3 rounded-lg border border-blue-100 flex items-center gap-2 mt-1">
                    👨‍💻 {{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}
                  </span>
                  <span v-else-if="ticket.assignment_status === 'pending' && ticket.proposed_developpeur" class="font-bold text-amber-700 bg-amber-50 py-1.5 px-3 rounded-lg border border-amber-100 flex items-center gap-2 mt-1 text-xs">
                    ⏳ Prop: {{ ticket.proposed_developpeur.prenom }}
                  </span>
                  <span v-else class="italic text-slate-400 mt-1">Non assigné</span>
                </div>
              </div>
            </div>

            <!-- Time Tracking Card -->
            <div v-if="ticket.temps_estime" class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
              <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
              <h3 class="text-xs font-extrabold text-slate-300 uppercase tracking-widest mb-4">Suivi du temps</h3>
              
              <div class="flex justify-between items-end mb-2">
                <span class="text-3xl font-black">{{ ticket.temps_passe || 0 }}<span class="text-lg text-slate-400 font-bold">h</span></span>
                <span class="text-sm font-bold text-slate-400 mb-1">/ {{ ticket.temps_estime }}h</span>
              </div>
              
              <div class="w-full bg-slate-700/50 rounded-full h-3 backdrop-blur-sm border border-slate-600/50">
                <div class="h-3 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(59,130,246,0.5)]" :class="ticket.temps_passe > ticket.temps_estime ? 'bg-gradient-to-r from-red-500 to-orange-500' : 'bg-gradient-to-r from-blue-400 to-indigo-500'" :style="{ width: Math.min(100, ((ticket.temps_passe || 0) / ticket.temps_estime) * 100) + '%' }"></div>
              </div>

              <!-- Log Time Input (Dev only) -->
              <div v-if="currentUser?.role === 'developpeur' && ticket.assignment_status === 'approved' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME'" class="mt-5 pt-5 border-t border-slate-700/50">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Ajouter des heures</label>
                <div class="flex gap-2">
                  <input v-model="timeToAdd" type="number" step="0.5" min="0.5" class="w-full px-3 py-2 text-sm bg-slate-800/50 border border-slate-600 rounded-lg outline-none focus:border-blue-400 text-white placeholder-slate-500 transition-colors" placeholder="Ex: 1.5">
                  <button @click="logTime" :disabled="!timeToAdd || stateUpdating" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg transition-colors shadow-md disabled:opacity-50 disabled:cursor-not-allowed">OK</button>
                </div>
              </div>
            </div>

            <!-- Actions Panel -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-6 border border-slate-100 space-y-4">
              <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest border-b border-slate-100 pb-3">Actions requises</h3>

              <!-- Valider/refuser assignation -->
              <div v-if="isManager && ticket.assignment_status === 'pending' && ticket.etat === 'OUVERT'" class="space-y-3">
                <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl">
                  <p class="text-[10px] font-bold text-amber-700 uppercase mb-1">Développeur proposé</p>
                  <p class="text-sm font-bold text-amber-900">{{ ticket.proposed_developpeur?.prenom }} {{ ticket.proposed_developpeur?.nom }}</p>
                </div>
                <button @click="ask('Valider cette assignation et notifier le développeur ?', acceptTicket)" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl shadow-md transition">✅ Valider</button>
                <button @click="ask('Refuser cette assignation ?', rejectTicket, true)" class="w-full py-2.5 bg-white border-2 border-slate-200 hover:border-red-500 hover:text-red-600 text-slate-600 text-sm font-bold rounded-xl transition">❌ Refuser</button>
              </div>

              <!-- Assignation manuelle -->
              <div v-if="isManager && ticket.assignment_status !== 'approved' && ticket.etat === 'OUVERT'" class="space-y-3 mt-2">
                <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigner manuellement</h4>
                <div v-if="workloads.length === 0" class="text-xs text-slate-400 italic text-center py-2">Aucun développeur disponible</div>
                <div v-else class="space-y-2 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
                  <div v-for="dev in workloads" :key="dev.id" class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-100 transition group">
                    <div>
                      <div class="text-xs font-bold text-slate-800">{{ dev.prenom }} {{ dev.nom }}</div>
                      <div class="text-[10px] font-medium text-slate-500 mt-0.5"><span class="w-2 h-2 inline-block rounded-full bg-blue-500 mr-1"></span>{{ dev.active_tickets_count }} actifs</div>
                    </div>
                    <button @click="ask(`Assigner ce ticket à ${dev.prenom} ${dev.nom} ?`, () => reassignTicket(dev.id))" class="px-3 py-1.5 bg-white border border-slate-300 hover:border-blue-500 hover:text-blue-600 text-slate-600 text-[10px] font-bold uppercase rounded-lg shadow-sm transition opacity-0 group-hover:opacity-100">Go</button>
                  </div>
                </div>
              </div>

              <!-- Nothing to do -->
              <div v-if="!isManager && !canDragTicket && !(currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME') && !(currentUser?.role === 'testeur' && ticket.testeur_id === currentUser.id)" class="text-center py-6">
                <span class="text-4xl block mb-2">☕</span>
                <p class="text-xs font-medium text-slate-400">Aucune action requise de votre part sur ce ticket.</p>
              </div>

              <div v-if="stateUpdating" class="text-xs text-blue-500 font-bold text-center py-2 animate-pulse">Synchronisation...</div>
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
const isManager   = computed(() => ['chef_de_projet', 'admin'].includes(currentUser?.role));

const ticket        = ref(null);
const loading       = ref(true);
const stateUpdating = ref(false);
const chatBox       = ref(null);
const workloads     = ref([]);
const timeToAdd     = ref(null);

const newComment        = ref('');
const submittingComment = ref(false);
const editingCommentId  = ref(null);
const editContent       = ref('');
const savingEdit        = ref(false);

// Drag & Drop Timeline State
const columns = [
  { etat: 'OUVERT',      label: 'À traiter'   },
  { etat: 'EN_COURS',    label: 'En cours'    },
  { etat: 'A_TESTER',    label: 'À tester'    },
  { etat: 'RECLAMATION', label: 'Réclamation' },
  { etat: 'VALIDE',      label: 'Validé'      },
];
const dragTarget = ref(null);

// Confirm dialog state
const confirmDialog = ref({ show: false, message: '', danger: false, onConfirm: () => {} });
const ask = (message, onConfirm, danger = false) => {
  confirmDialog.value = { show: true, message, onConfirm, danger };
};

const fetchTicket = async () => {
  try {
    const res = await api.get(`/tickets/${route.params.id}`);
    ticket.value = res.data;
    if (isManager.value && ticket.value.assignment_status !== 'approved') {
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

const logTime = async () => {
  if (!timeToAdd.value || timeToAdd.value <= 0) return;
  stateUpdating.value = true;
  try {
    await api.post(`/tickets/${ticket.value.id}/log-time`, { temps_ajoute: timeToAdd.value });
    await fetchTicket();
    timeToAdd.value = null;
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur lors de l\'ajout de temps');
  } finally {
    stateUpdating.value = false;
  }
};

// Timeline Drag & Drop Handlers
const canDragTicket = computed(() => {
  if (!ticket.value) return false;
  const role = currentUser?.role;
  if (isManager.value) return false; // Admin/Chef = read only on states
  if (role === 'developpeur') {
    return ticket.value.developpeur_id === currentUser?.id && ticket.value.assignment_status === 'approved';
  }
  if (role === 'testeur') {
    return ticket.value.testeur_id === currentUser?.id && ticket.value.etat === 'A_TESTER';
  }
  return false;
});

const canTransition = (toEtat) => {
  const role = currentUser?.role;
  if (isManager.value) return false;
  if (role === 'developpeur') {
    return ['OUVERT', 'EN_COURS', 'A_TESTER'].includes(toEtat);
  }
  if (role === 'testeur') {
    return ['RECLAMATION', 'VALIDE'].includes(toEtat);
  }
  return false;
};

const onDragStart = () => {};
const onDragEnd = () => { dragTarget.value = null; };
const onDragOver = (etat) => {
  if (!isManager.value) dragTarget.value = etat;
};

const onDrop = async (etat) => {
  dragTarget.value = null;
  if (!ticket.value || ticket.value.etat === etat) return;

  if (!canTransition(etat)) {
    alert(`Vous n'êtes pas autorisé à glisser le ticket vers l'état "${etat}".`);
    return;
  }

  const oldEtat = ticket.value.etat;
  ticket.value.etat = etat; // optimistic update
  stateUpdating.value = true;
  try {
    await api.put(`/tickets/${ticket.value.id}/status`, { etat });
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur lors du déplacement.');
    ticket.value.etat = oldEtat; // rollback
  } finally {
    stateUpdating.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value.trim()) return;
  submittingComment.value = true;
  try {
    const res = await api.post('/comments', { ticket_id: ticket.value.id, contenu: newComment.value.trim() });
    if (!ticket.value.comments) ticket.value.comments = [];
    ticket.value.comments.push(res.data);
    newComment.value = '';
    nextTick(() => { if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight; });
  } catch {
    alert('Erreur lors de l\'envoi');
  } finally {
    submittingComment.value = false;
  }
};

const startEdit = (comment) => {
  editingCommentId.value = comment.id;
  editContent.value = comment.contenu;
};

const cancelEdit = () => {
  editingCommentId.value = null;
  editContent.value = '';
};

const saveEdit = async (comment) => {
  if (!editContent.value.trim() || editContent.value === comment.contenu) {
    cancelEdit();
    return;
  }
  savingEdit.value = true;
  try {
    const res = await api.put(`/comments/${comment.id}`, { contenu: editContent.value });
    comment.contenu = res.data.contenu;
    cancelEdit();
  } catch {
    alert('Erreur de modification');
  } finally {
    savingEdit.value = false;
  }
};

const deleteComment = async (comment) => {
  try {
    await api.delete(`/comments/${comment.id}`);
    ticket.value.comments = ticket.value.comments.filter(c => c.id !== comment.id);
  } catch {
    alert('Erreur de suppression');
  }
};

const formatDescription = (text) => {
  if (!text) return '';
  return text.replace(/\n/g, '<br>')
             .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
             .replace(/_(.*?)_/g, '<em>$1</em>')
             .replace(/- (.*)/g, '<li>$1</li>');
};

const prioriteClass = (prio) => {
  const map = { CRITIQUE: 'border-red-200 text-red-700 bg-red-50', HAUTE: 'border-orange-200 text-orange-700 bg-orange-50', MOYENNE: 'border-blue-200 text-blue-700 bg-blue-50', BASSE: 'border-emerald-200 text-emerald-700 bg-emerald-50' };
  return map[prio] || 'border-gray-200 text-gray-700 bg-gray-50';
};

const formatTime = (iso) => {
  const d = new Date(iso);
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(fetchTicket);
</script>

<style scoped>
.layout { display: flex; min-height: 100vh; background: #f8fafc; }
.main { flex: 1; padding: 2.5rem; overflow-y: auto; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>