<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />

      <div class="page-header">
        <div>
          <button @click="goBack" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Retour
          </button>
          <h1 class="page-title">{{ projectName || 'Chargement…' }}</h1>
          <p class="page-sub">{{ tickets.length }} ticket{{ tickets.length !== 1 ? 's' : '' }}</p>
        </div>
        <div class="flex items-center gap-3">
          <button @click="$router.push({ name: 'ProjectDetail', params: { id: projectId } })" class="btn-info">
            Infos du projet
          </button>
          <button v-if="currentUser?.role === 'testeur'" @click="showCreateModal = true" class="btn-new">
            + Nouveau ticket
          </button>
        </div>
      </div>

      <div v-if="globalMsg" class="toast" :class="globalOk ? 'toast-ok' : 'toast-err'">
        {{ globalMsg }}
      </div>

      <div v-if="loading" class="loading-state">
        <svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/>
          <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/>
        </svg>
        Chargement…
      </div>

      <div v-else class="kanban-scroll">
        <div class="kanban-board">
          <div
            v-for="col in columns"
            :key="col.etat"
            class="kanban-col"
            :class="'col-' + col.key"
            @dragover.prevent="onDragOver(col.etat)"
            @drop.prevent="onDrop(col.etat)"
            :data-dragover="dragTarget === col.etat"
          >
            <div class="col-header">
              <div class="col-label">
                <span class="col-dot" :class="'dot-' + col.key"></span>
                <span class="col-name">{{ col.label }}</span>
              </div>
              <span class="col-count">{{ ticketsByEtat(col.etat).length }}</span>
            </div>

            <div class="col-cards">
              <div
                v-for="ticket in ticketsByEtat(col.etat)"
                :key="ticket.id"
                class="ticket-card"
                :class="['prio-' + ticket.priorite.toLowerCase(), { 'is-dragging': dragging?.id === ticket.id }]"
                :draggable="canDrag(ticket)"
                @dragstart="onDragStart(ticket)"
                @dragend="onDragEnd"
                @click="goToTicket(ticket)"
              >
                <div class="prio-strip" :class="'strip-' + ticket.priorite.toLowerCase()"></div>
                <div class="card-body">
                  <div class="card-top">
                    <span class="prio-badge" :class="'pb-' + ticket.priorite.toLowerCase()">{{ ticket.priorite }}</span>
                    <div class="flex items-center gap-1">
                      <span v-if="ticket.categorie_ia" class="ia-badge-flat">IA: {{ categorieLabel(ticket.categorie_ia) }}</span>
                      <span class="card-id">#{{ ticket.id }}</span>
                    </div>
                  </div>
                  <h3 class="card-title">{{ ticket.titre }}</h3>
                  <div class="card-footer">
                    <div class="dev-info" v-if="ticket.assignment_status === 'approved' && ticket.developpeur">
                      <div class="dev-av">{{ initials(ticket.developpeur) }}</div>
                      <span class="dev-name">{{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}</span>
                    </div>
                    <div class="dev-info" v-else-if="ticket.assignment_status === 'pending' && ticket.proposed_developpeur">
                      <span class="dev-name">⏳ {{ ticket.proposed_developpeur.prenom }}</span>
                    </div>
                    <span v-else class="unassigned">Non assigné</span>
                    <span class="card-date">{{ formatDate(ticket.created_at) }}</span>
                  </div>
                </div>
              </div>

              <div v-if="dragTarget === col.etat && dragging" class="drop-ghost">Déposer ici</div>
              <div v-if="!ticketsByEtat(col.etat).length && dragTarget !== col.etat" class="col-empty">Aucun ticket</div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <div v-if="showCreateModal" class="overlay" @click.self="closeModal">
      <div class="modal">

        <template v-if="assignResult">
          <div class="modal-header">
            <h3 class="modal-title">Ticket créé ✅</h3>
            <button @click="closeModal" class="close-btn">✕</button>
          </div>
          <div class="modal-body">

            <div class="confirm-ai-block">
              <div class="confirm-ai-title">🤖 Analyse automatique</div>
              <div class="confirm-ai-row">
                <div class="confirm-ai-item">
                  <span class="confirm-ai-label">Priorité</span>
                  <span class="confirm-prio-badge" :class="'cpb-' + (ticketResult?.priorite || 'basse').toLowerCase()">
                    {{ ticketResult?.priorite || '—' }}
                  </span>
                </div>
                <div class="confirm-ai-item">
                  <span class="confirm-ai-label">Catégorie</span>
                  <span class="confirm-cat-badge">
                    {{ ticketResult?.categorie_ia ? categorieLabel(ticketResult.categorie_ia) : '—' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="confirm-assign-block">
              <div v-if="assignResult.success" class="confirm-assign-row">
                <span class="confirm-assign-icon">{{ assignResult.is_retour ? '🔁' : '⏳' }}</span>
                <div>
                  <p class="confirm-assign-title">
                    {{ assignResult.is_retour ? 'Assigné d\'office (Retour)' : 'Assignation proposée' }}
                  </p>
                  <p class="confirm-assign-sub">
                    {{ assignResult.dev_prenom }} {{ assignResult.dev_nom }} —
                    {{ assignResult.is_retour ? 'développeur du ticket parent.' : 'en attente de validation admin.' }}
                  </p>
                </div>
              </div>
              <div v-else class="confirm-assign-row">
                <span class="confirm-assign-icon">⚠️</span>
                <div>
                  <p class="confirm-assign-title" style="color:#dc2626;">Aucun développeur disponible</p>
                  <p class="confirm-assign-sub">{{ assignResult.message }}</p>
                </div>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button @click="closeModal" class="btn-cancel">Fermer</button>
          </div>
        </template>

        <template v-else>
          <div class="modal-header">
            <h3 class="modal-title">Nouveau ticket</h3>
            <button @click="closeModal" class="close-btn">✕</button>
          </div>

          <div class="modal-body">

            <div class="field">
              <label class="label">Type</label>
              <div class="radio-group">
                <label class="radio-option" :class="{ active: form.type === 'NOUVEAU' }">
                  <input type="radio" v-model="form.type" value="NOUVEAU" hidden />
                  🆕 Nouveau
                </label>
                <label class="radio-option" :class="{ active: form.type === 'RETOUR' }">
                  <input type="radio" v-model="form.type" value="RETOUR" hidden />
                  🔁 Retour
                </label>
              </div>
            </div>

            <div v-if="form.type === 'RETOUR'" class="field">
              <label class="label">Ticket concerné *</label>
              <select v-model="form.parent_ticket_id" class="input">
                <option :value="null" disabled>-- Sélectionner --</option>
                <option v-for="t in validParentTickets" :key="t.id" :value="t.id">
                  #{{ t.id }} — {{ t.titre }}
                </option>
              </select>
            </div>

            <div class="field">
              <label class="label">Titre *</label>
              <input v-model="form.titre" type="text" class="input" placeholder="Décrivez le problème en une phrase" />
            </div>

            <div class="field">
              <label class="label">Description</label>
              <textarea v-model="form.description" rows="3" class="input ta" placeholder="Étapes pour reproduire, résultat attendu vs obtenu, contexte…"></textarea>
            </div>

            <div class="field">
              <label class="label">Estimation (h) *</label>
              <input v-model="form.temps_estime" type="number" step="0.5" min="0.5" class="input" placeholder="Ex: 2.5" />
            </div>

            <div class="field">
              <label class="label">Pièces jointes</label>
              <label class="file-upload">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                {{ attachments.length ? attachments.length + ' fichier(s) sélectionné(s)' : 'Choisir des fichiers' }}
                <input type="file" multiple @change="handleFileUpload" hidden />
              </label>
            </div>

            <div v-if="formError" class="alert-err">{{ formError }}</div>
          </div>

          <div class="modal-footer">
            <button @click="closeModal" class="btn-cancel">Annuler</button>
            <button @click="submitTicket" :disabled="submitting" class="btn-primary">
              <svg v-if="submitting" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="14" height="14">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/>
              </svg>
              <span>{{ submitting ? 'Création en cours…' : 'Créer' }}</span>
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';
import AppHeader from '../../components/AppHeader.vue';

const authStore   = useAuthStore();
const router      = useRouter();
const route       = useRoute();

const projectId   = route.params.projectId;
const currentUser = authStore.currentUser;
const isManager   = authStore.isManager();

const tickets     = ref([]);
const projectName = ref('');
const loading     = ref(false);

const dragging    = ref(null);
const dragTarget  = ref(null);

const showCreateModal = ref(false);
const submitting      = ref(false);
const formError       = ref('');
const assignResult    = ref(null);
const ticketResult    = ref(null);
const attachments     = ref([]);

const form = ref({
  titre: '',
  description: '',
  temps_estime: null,
  type: 'NOUVEAU',
  parent_ticket_id: null,
});

const globalMsg = ref('');
const globalOk  = ref(true);
const msg = (m, ok = true) => {
  globalMsg.value = m; globalOk.value = ok;
  setTimeout(() => globalMsg.value = '', 4000);
};

const validParentTickets = computed(() =>
  tickets.value.filter(t => t.developpeur_id && ['VALIDE', 'A_TESTER', 'RECLAMATION'].includes(t.etat))
);

const handleFileUpload = (e) => { attachments.value = Array.from(e.target.files); };

const columns = [
  { etat: 'OUVERT',      key: 'open', label: 'À traiter'   },
  { etat: 'EN_COURS',    key: 'prog', label: 'En cours'    },
  { etat: 'A_TESTER',    key: 'test', label: 'À tester'    },
  { etat: 'RECLAMATION', key: 'recl', label: 'Réclamation' },
  { etat: 'VALIDE',      key: 'done', label: 'Validé'      },
];

const ticketsByEtat = (etat) =>
  tickets.value
    .filter(t => t.etat === etat)
    .sort((a, b) => {
      const o = { CRITIQUE: 0, HAUTE: 1, MOYENNE: 2, BASSE: 3 };
      return (o[a.priorite] ?? 9) - (o[b.priorite] ?? 9);
    });

const onDragStart = (t) => { dragging.value = t; };
const onDragEnd   = () => { dragging.value = null; dragTarget.value = null; };
const onDragOver  = (etat) => { if (!isManager) dragTarget.value = etat; };

const onDrop = async (etat) => {
  if (isManager) return;
  dragTarget.value = null;
  if (!dragging.value || dragging.value.etat === etat) return;
  const ticket = dragging.value;
  dragging.value = null;
  if (!canTransition(ticket, etat)) { msg('Transition non autorisée.', false); return; }
  ticket.etat = etat;
  try {
    await api.put(`/tickets/${ticket.id}/status`, { etat });
    msg('Statut mis à jour', true);
  } catch (e) {
    msg(e.response?.data?.message || 'Erreur lors du déplacement.', false);
    await fetchTickets();
  }
};

const canDrag = (ticket) => {
  const role = currentUser?.role;
  if (isManager) return false;
  if (role === 'developpeur') return ticket.developpeur_id === currentUser?.id && ticket.assignment_status === 'approved';
  if (role === 'testeur') return ticket.testeur_id === currentUser?.id && ticket.etat === 'A_TESTER';
  return false;
};

const canTransition = (ticket, toEtat) => {
  const role = currentUser?.role;
  if (isManager) return false;
  if (role === 'developpeur') return ticket.developpeur_id === currentUser?.id && ticket.assignment_status === 'approved' && ['OUVERT', 'EN_COURS', 'A_TESTER'].includes(toEtat);
  if (role === 'testeur') return ticket.testeur_id === currentUser?.id && ticket.etat === 'A_TESTER' && ['RECLAMATION', 'VALIDE'].includes(toEtat);
  return false;
};

const goBack = () => {
  if (isManager) router.push({ name: 'ProjectManagement' });
  else router.push({ name: 'Projects' });
};
const goToTicket = (ticket) => {
  if (dragging.value) return;
  router.push({ name: 'TicketDetails', params: { projectId, id: ticket.id } });
};

const fetchProjectInfo = async () => {
  try {
    const res = await api.get(`/projects/${projectId}`);
    projectName.value = res.data.nom;
  } catch {
    try {
      const res = await api.get('/projects');
      const all = res.data.data || res.data;
      const cur = all.find(p => p.id == projectId);
      if (cur) projectName.value = cur.nom;
    } catch {}
  }
};

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/projects/${projectId}/tickets`);
    tickets.value = res.data;
  } catch (e) { console.error(e); }
  finally { loading.value = false; }
};

const submitTicket = async () => {
  if (!form.value.titre) { formError.value = 'Le titre est requis.'; return; }
  if (!form.value.temps_estime || form.value.temps_estime <= 0) { formError.value = 'Une estimation de temps valide est requise.'; return; }
  submitting.value = true; formError.value = '';
  try {
    const formData = new FormData();
    formData.append('titre', form.value.titre);

    const desc = form.value.description?.trim();
    if (desc) {
      formData.append('description', desc);
    }

    formData.append('temps_estime', form.value.temps_estime);
    formData.append('type', form.value.type);
    if (form.value.type === 'RETOUR' && form.value.parent_ticket_id) {
      formData.append('parent_ticket_id', form.value.parent_ticket_id);
    }
    attachments.value.forEach((file, i) => formData.append(`attachments[${i}]`, file));

    const res = await api.post(`/projects/${projectId}/tickets`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await fetchTickets();
    ticketResult.value  = res.data.ticket;
    assignResult.value  = res.data.auto_assign;
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la création.';
  } finally { submitting.value = false; }
};

const closeModal = () => {
  showCreateModal.value = false;
  form.value = { titre: '', description: '', temps_estime: null, type: 'NOUVEAU', parent_ticket_id: null };
  attachments.value = [];
  formError.value = '';
  assignResult.value = null;
  ticketResult.value = null;
};

onMounted(() => { fetchProjectInfo(); fetchTickets(); });

const initials   = (u) => ((u?.prenom?.[0] || '') + (u?.nom?.[0] || '')).toUpperCase();
const categorieLabel = (cat) => {
  const map = { BUG: 'Bug', PERFORMANCE: 'Perf', SECURITE: 'Sécu', UI_UX: 'UI/UX', BASE_DE_DONNEES: 'BDD', API: 'API', CONFIGURATION: 'Config', AUTRE: 'Autre', NON_CLASSE: '?' };
  return map[cat] || cat;
};
const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '';
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*, *::before, *::after { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

.layout { display: flex; min-height: 100vh; background: #f0f4f8; }
.main   { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

.page-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 1.75rem 2rem 1.25rem; background: white;
  border-bottom: 1px solid #e2e8f0; gap: 1rem; flex-shrink: 0;
}
.back-btn {
  display: inline-flex; align-items: center; gap: .375rem;
  color: #3b82f6; font-size: .8rem; font-weight: 600;
  background: none; border: none; cursor: pointer; padding: 0; margin-bottom: .375rem;
}
.back-btn:hover { color: #1d4ed8; }
.page-title { font-size: 1.375rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-sub   { font-size: .8125rem; color: #94a3b8; margin: .2rem 0 0; }
.btn-info {
  padding: .5rem 1rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: .8125rem;
  font-weight: 600; cursor: pointer; transition: background .15s;
}
.btn-info:hover { background: #e2e8f0; }
.btn-new {
  padding: .5rem 1.125rem; background: #1e293b; color: white;
  border: none; border-radius: 8px; font-size: .8125rem; font-weight: 700;
  cursor: pointer; transition: background .15s;
}
.btn-new:hover { background: #0f172a; }

.toast {
  position: fixed; top: 5.5rem; left: 50%; transform: translateX(-50%);
  padding: .6rem 1.5rem; border-radius: 999px; font-size: .8125rem;
  font-weight: 600; z-index: 999; box-shadow: 0 4px 16px rgba(0,0,0,.1);
}
.toast-ok  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.toast-err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

.loading-state { display: flex; align-items: center; gap: .5rem; color: #94a3b8; font-size: .875rem; padding: 4rem 2rem; }
.spin { animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.kanban-scroll { flex: 1; overflow-x: auto; overflow-y: hidden; padding: 1.5rem 1.75rem 1.75rem; }
.kanban-board  { display: flex; gap: 1rem; align-items: flex-start; min-height: calc(100vh - 130px); min-width: max-content; }
.kanban-col {
  width: 260px; flex-shrink: 0; background: #fff; border-radius: 14px;
  border: 1px solid #e2e8f0; display: flex; flex-direction: column;
  max-height: calc(100vh - 150px); transition: border-color .2s, box-shadow .2s;
}
.kanban-col[data-dragover="true"] { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.col-open { border-top: 3px solid #22c55e; }
.col-prog { border-top: 3px solid #3b82f6; }
.col-test { border-top: 3px solid #f59e0b; }
.col-recl { border-top: 3px solid #ef4444; }
.col-done { border-top: 3px solid #8b5cf6; }
.col-header { display: flex; align-items: center; justify-content: space-between; padding: .875rem 1rem .75rem; flex-shrink: 0; }
.col-label  { display: flex; align-items: center; gap: .5rem; }
.col-dot    { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot-open  { background: #22c55e; }
.dot-prog  { background: #3b82f6; }
.dot-test  { background: #f59e0b; }
.dot-recl  { background: #ef4444; }
.dot-done  { background: #8b5cf6; }
.col-name  { font-size: .8125rem; font-weight: 700; color: #1e293b; }
.col-count { font-size: .6875rem; font-weight: 700; color: #94a3b8; background: #f1f5f9; border-radius: 20px; padding: 2px 8px; }
.col-cards { flex: 1; overflow-y: auto; padding: .25rem .625rem .75rem; display: flex; flex-direction: column; gap: .5rem; }
.col-cards::-webkit-scrollbar { width: 4px; }
.col-cards::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
.col-empty { text-align: center; font-size: .75rem; color: #cbd5e1; padding: 1.5rem .5rem; font-style: italic; }

.ticket-card {
  background: white; border: 1px solid #e2e8f0; border-radius: 10px;
  cursor: pointer; display: flex; overflow: hidden;
  transition: all .18s; box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.ticket-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,.08); transform: translateY(-1px); }
.ticket-card.is-dragging { opacity: .4; transform: scale(.97); }
.prio-strip { width: 4px; flex-shrink: 0; }
.strip-basse    { background: #22c55e; }
.strip-moyenne  { background: #3b82f6; }
.strip-haute    { background: #f59e0b; }
.strip-critique { background: #ef4444; }
.card-body   { padding: .75rem .875rem; flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .5rem; }
.card-top    { display: flex; align-items: center; justify-content: space-between; }
.prio-badge  { font-size: .5625rem; font-weight: 800; padding: 2px 7px; border-radius: 4px; text-transform: uppercase; letter-spacing: .05em; }
.pb-basse    { background: #f0fdf4; color: #16a34a; }
.pb-moyenne  { background: #eff6ff; color: #1d4ed8; }
.pb-haute    { background: #fff7ed; color: #ea580c; }
.pb-critique { background: #fef2f2; color: #dc2626; }
.card-id     { font-size: .625rem; color: #cbd5e1; font-weight: 600; }

.ia-badge-flat {
  font-size: 0.625rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  padding: 0.15rem 0.4rem;
  background-color: #e0e7ff;
  color: #4338ca;
  border-radius: 4px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
}

.card-title  { font-size: .8125rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.card-footer { display: flex; align-items: center; justify-content: space-between; padding-top: .375rem; border-top: 1px solid #f8fafc; }
.dev-info    { display: flex; align-items: center; gap: .35rem; }
.dev-av      { width: 18px; height: 18px; border-radius: 5px; background: #dbeafe; color: #1d4ed8; font-size: .5rem; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.dev-name    { font-size: .6875rem; font-weight: 600; color: #475569; }
.unassigned  { font-size: .6875rem; color: #e2e8f0; font-style: italic; }
.card-date   { font-size: .625rem; color: #cbd5e1; }
.drop-ghost  { border: 2px dashed #3b82f6; border-radius: 10px; padding: 1rem; text-align: center; font-size: .75rem; font-weight: 600; color: #3b82f6; background: rgba(59,130,246,.04); min-height: 60px; display: flex; align-items: center; justify-content: center; }

.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; z-index: 200; padding: 1rem; }
.modal   { background: white; border-radius: 16px; width: 100%; max-width: 460px; box-shadow: 0 24px 48px rgba(0,0,0,.2); overflow: hidden; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; background: white; z-index: 1; }
.modal-title  { font-size: .9375rem; font-weight: 800; color: #0f172a; margin: 0; }
.close-btn    { background: none; border: none; font-size: 1rem; color: #94a3b8; cursor: pointer; border-radius: 6px; padding: 4px; }
.close-btn:hover { color: #1e293b; }
.modal-body   { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: .875rem; }
.modal-footer { display: flex; gap: .625rem; justify-content: flex-end; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; position: sticky; bottom: 0; background: white; }

.field { display: flex; flex-direction: column; gap: .3rem; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.label { font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
.input { width: 100%; padding: .625rem .875rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #1e293b; font-size: .875rem; font-family: inherit; outline: none; transition: border-color .2s; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); background: white; }
.ta { resize: vertical; min-height: 80px; }

.radio-group  { display: flex; gap: .5rem; }
.radio-option {
  flex: 1; text-align: center; padding: .5rem; border-radius: 8px;
  border: 1.5px solid #e2e8f0; font-size: .8125rem; font-weight: 600;
  color: #64748b; cursor: pointer; transition: all .15s;
}
.radio-option.active { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }

.file-upload {
  display: flex; align-items: center; gap: .5rem;
  padding: .6rem .875rem; background: #f8fafc; border: 1.5px dashed #cbd5e1;
  border-radius: 8px; font-size: .8125rem; color: #64748b;
  cursor: pointer; transition: border-color .2s;
}
.file-upload:hover { border-color: #3b82f6; color: #3b82f6; }

.confirm-ai-block {
  background: #f5f3ff; border: 1.5px solid #ddd6fe; border-radius: 12px; padding: 1rem 1.125rem;
}
.confirm-ai-title { font-size: .75rem; font-weight: 800; color: #6d28d9; margin-bottom: .75rem; }
.confirm-ai-row   { display: flex; gap: .75rem; margin-bottom: .625rem; }
.confirm-ai-item  { flex: 1; display: flex; flex-direction: column; gap: .3rem; }
.confirm-ai-label { font-size: .65rem; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: .05em; }
.confirm-prio-badge {
  display: inline-block; font-size: .75rem; font-weight: 800; padding: .3rem .75rem;
  border-radius: 6px; text-align: center;
}
.cpb-basse    { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.cpb-moyenne  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.cpb-haute    { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
.cpb-critique { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.confirm-cat-badge {
  display: inline-block; font-size: .75rem; font-weight: 700; padding: .3rem .75rem;
  border-radius: 6px; background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; text-align: center;
}
.confirm-solution      { margin-top: .5rem; padding-top: .625rem; border-top: 1px solid #ddd6fe; }
.confirm-solution-text { margin: .3rem 0 0; font-size: .8125rem; color: #4c1d95; line-height: 1.5; }

.confirm-assign-block {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.125rem;
}
.confirm-assign-row   { display: flex; align-items: flex-start; gap: .875rem; }
.confirm-assign-icon  { font-size: 1.5rem; flex-shrink: 0; margin-top: .1rem; }
.confirm-assign-title { font-size: .875rem; font-weight: 700; color: #1e293b; margin: 0 0 .2rem; }
.confirm-assign-sub   { font-size: .8125rem; color: #64748b; margin: 0; }

.alert-err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; padding: .625rem .875rem; font-size: .8125rem; }

.btn-cancel { padding: .5rem 1rem; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .8125rem; font-weight: 600; cursor: pointer; font-family: inherit; }
.btn-cancel:hover { background: #f8fafc; }
.btn-primary { padding: .5rem 1.25rem; background: #1e293b; color: white; border: none; border-radius: 8px; font-size: .8125rem; font-weight: 700; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: .375rem; }
.btn-primary:hover:not(:disabled) { background: #0f172a; }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; }
</style>