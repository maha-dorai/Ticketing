<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">

      <!-- Header -->
      <div class="page-header">
        <div>
          <button @click="goBack" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Retour aux projets
          </button>
          <h1 class="page-title">📂 {{ projectName || 'Chargement...' }}</h1>
          <p class="page-sub">{{ tickets.length }} ticket{{ tickets.length !== 1 ? 's' : '' }} dans ce projet</p>
        </div>
        <button v-if="currentUser?.role === 'testeur'" @click="showCreateModal = true" class="btn-new">
          + Créer un ticket
        </button>
      </div>

      <div class="page-content">

        <!-- Filtres état -->
        <div class="toolbar">
          <div class="filters">
            <button @click="etatFilter = ''" :class="['fb', etatFilter === '' ? 'fb-active' : '']">Tous</button>
            <button @click="etatFilter = 'OUVERT'" :class="['fb', etatFilter === 'OUVERT' ? 'fb-active fb-open' : '']">🟢 Ouverts</button>
            <button @click="etatFilter = 'EN_COURS'" :class="['fb', etatFilter === 'EN_COURS' ? 'fb-active fb-prog' : '']">🔵 En cours</button>
            <button @click="etatFilter = 'RESOLU'" :class="['fb', etatFilter === 'RESOLU' ? 'fb-active fb-done' : '']">✅ Résolus</button>
            <button @click="etatFilter = 'FERME'" :class="['fb', etatFilter === 'FERME' ? 'fb-active fb-closed' : '']">⬜ Fermés</button>
          </div>
        </div>

        <!-- Chargement -->
        <div v-if="loading" class="loading-state">
          <svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/></svg>
          Chargement des tickets...
        </div>

        <!-- Aucun ticket -->
        <div v-else-if="!filteredTickets.length" class="empty">
          <div class="ei">🎫</div>
          <h3 class="et">Aucun ticket trouvé</h3>
          <p class="es">{{ etatFilter ? 'Aucun ticket avec ce statut.' : currentUser?.role === \'testeur\' ? \'Créez le premier ticket !\' : \'Aucun ticket pour ce projet.\' }}</p>
        </div>

        <!-- Grille de tickets -->
        <div v-else class="ticket-grid">
          <div v-for="ticket in filteredTickets" :key="ticket.id"
            @click="$router.push({ name: 'TicketDetails', params: { projectId: projectId, id: ticket.id } })"
            class="ticket-card">
            <div class="tc-top">
              <div class="tc-head">
                <span class="prio-badge" :class="prioClass(ticket.priorite)">{{ ticket.priorite }}</span>
                <span class="etat-badge" :class="etatClass(ticket.etat)">{{ etatLabel(ticket.etat) }}</span>
              </div>
              <h3 class="tc-title">{{ ticket.titre }}</h3>
              <p class="tc-desc">{{ ticket.description || 'Aucune description.' }}</p>
            </div>
            <div class="tc-footer">
              <div class="tc-dev">
                <span v-if="ticket.developpeur" class="dev-info">
                  <div class="dev-av">{{ (ticket.developpeur.prenom[0] || '') + (ticket.developpeur.nom[0] || '') }}</div>
                  <span class="dev-name">{{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}</span>
                </span>
                <span v-else class="unassigned">Non assigné</span>
              </div>
              <span class="tc-date">{{ formatDate(ticket.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Création Ticket -->
    <div v-if="showCreateModal" class="overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Nouveau ticket — {{ projectName }}</h3>
          <button @click="closeModal" class="close-btn">✕</button>
        </div>
        <div class="mform">
          <div class="field">
            <label class="label">Titre *</label>
            <input v-model="form.titre" required type="text" class="input" placeholder="Titre du ticket" />
          </div>
          <div class="field">
            <label class="label">Description</label>
            <textarea v-model="form.description" rows="3" class="input ta" placeholder="Description détaillée du problème..."></textarea>
          </div>
          <div class="row2">
            <div class="field">
              <label class="label">Priorité</label>
              <select v-model="form.priorite" class="input sel">
                <option value="BASSE">🟢 Basse</option>
                <option value="MOYENNE">🔵 Moyenne</option>
                <option value="HAUTE">🟠 Haute</option>
                <option value="CRITIQUE">🔴 Critique</option>
              </select>
            </div>
            <div class="field">
              <label class="label">Développeur (optionnel)</label>
              <select v-model="form.developpeur_id" class="input sel">
                <option value="">Non assigné</option>
                <option v-for="dev in projectDevs" :key="dev.id" :value="dev.id">{{ dev.prenom }} {{ dev.nom }}</option>
              </select>
            </div>
          </div>
          <div v-if="formError" class="alert alert-err">✕ {{ formError }}</div>
          <div class="modal-footer">
            <button @click="closeModal" class="btn-cancel">Annuler</button>
            <button @click="submitTicket" :disabled="submitting" class="btn-primary">
              <svg v-if="submitting" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
              <span v-else>Créer le ticket</span>
            </button>
          </div>
        </div>
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

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const projectId = route.params.projectId;
const currentUser = authStore.currentUser;
const isAdmin = authStore.isAdmin();

const tickets = ref([]);
const projectName = ref('');
const projectDevs = ref([]);
const loading = ref(false);
const etatFilter = ref('');

const showCreateModal = ref(false);
const submitting = ref(false);
const formError = ref('');
const form = ref({ titre: '', description: '', priorite: 'BASSE', developpeur_id: '' });

// Back button: admin goes back to /admin/projects, user to /projects
const goBack = () => {
  if (isAdmin) router.push({ name: 'ProjectManagement' });
  else router.push({ name: 'Projects' });
};

const filteredTickets = computed(() =>
  etatFilter.value ? tickets.value.filter(t => t.etat === etatFilter.value) : tickets.value
);

const fetchProjectInfo = async () => {
  try {
    const res = await api.get('/projects');
    const allProjects = res.data.data || res.data;
    const current = allProjects.find(p => p.id == projectId);
    if (current) {
      projectName.value = current.nom;
      projectDevs.value = (current.users || []).filter(u => u.role === 'developpeur');
    }
  } catch (e) { console.error('Erreur projet', e); }
};

const fetchTickets = async () => {
  loading.value = true;
  try { const res = await api.get(`/projects/${projectId}/tickets`); tickets.value = res.data; }
  catch (e) { console.error('Erreur tickets', e); }
  finally { loading.value = false; }
};

const submitTicket = async () => {
  if (!form.value.titre) { formError.value = 'Le titre est requis'; return; }
  submitting.value = true; formError.value = '';
  try {
    const payload = { ...form.value };
    if (!payload.developpeur_id) delete payload.developpeur_id;
    await api.post(`/projects/${projectId}/tickets`, payload);
    await fetchTickets();
    closeModal();
  } catch (e) { formError.value = e.response?.data?.message || 'Erreur lors de la création.'; }
  finally { submitting.value = false; }
};

const closeModal = () => {
  showCreateModal.value = false; formError.value = '';
  form.value = { titre: '', description: '', priorite: 'BASSE', developpeur_id: '' };
};

onMounted(() => { fetchProjectInfo(); fetchTickets(); });

const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '';
const etatLabel = e => ({ OUVERT: 'Ouvert', EN_COURS: 'En cours', RESOLU: 'Résolu', FERME: 'Fermé' }[e] || e);
const etatClass = e => ({ OUVERT: 'e-open', EN_COURS: 'e-prog', RESOLU: 'e-done', FERME: 'e-closed' }[e] || '');
const prioClass = p => ({ BASSE: 'p-low', MOYENNE: 'p-med', HAUTE: 'p-high', CRITIQUE: 'p-crit' }[p] || '');
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;background:#f8fafc;}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;padding:2rem 2.5rem 1.5rem;border-bottom:1px solid #e2e8f0;background:white;gap:1rem;flex-wrap:wrap;}
.back-btn{display:inline-flex;align-items:center;gap:.375rem;color:#3b82f6;font-size:.8125rem;font-weight:600;background:none;border:none;cursor:pointer;padding:0;font-family:inherit;margin-bottom:.5rem;transition:color .15s;}
.back-btn:hover{color:#1d4ed8;}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;}
.page-sub{font-size:.875rem;color:#64748b;margin:.25rem 0 0;}
.btn-new{padding:.625rem 1.25rem;background:#1e293b;color:white;border:none;border-radius:9px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;flex-shrink:0;align-self:center;}
.btn-new:hover{background:#0f172a;}
.page-content{padding:1.75rem 2.5rem;display:flex;flex-direction:column;gap:1.25rem;}
.toolbar{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.filters{display:flex;gap:.375rem;flex-wrap:wrap;}
.fb{padding:.4375rem .875rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;font-weight:500;color:#64748b;background:white;cursor:pointer;font-family:inherit;transition:all .15s;}
.fb:hover{border-color:#cbd5e1;color:#1e293b;}
.fb-active{background:#1e293b;color:white;border-color:#1e293b;}
.fb-open.fb-active{background:#16a34a;border-color:#16a34a;}
.fb-prog.fb-active{background:#1d4ed8;border-color:#1d4ed8;}
.fb-done.fb-active{background:#0891b2;border-color:#0891b2;}
.fb-closed.fb-active{background:#64748b;border-color:#64748b;}
.loading-state{display:flex;align-items:center;gap:.5rem;color:#94a3b8;font-size:.875rem;padding:3rem 0;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
.empty{text-align:center;padding:5rem 2rem;}
.ei{font-size:3.5rem;margin-bottom:1rem;}
.et{font-size:1.125rem;font-weight:700;color:#1e293b;margin:0 0 .5rem;}
.es{font-size:.875rem;color:#94a3b8;margin:0;}
/* Ticket grid */
.ticket-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;}
.ticket-card{background:white;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;cursor:pointer;display:flex;flex-direction:column;gap:1rem;transition:all .2s;}
.ticket-card:hover{border-color:#3b82f6;box-shadow:0 4px 20px rgba(59,130,246,.1);transform:translateY(-1px);}
.tc-top{display:flex;flex-direction:column;gap:.625rem;flex:1;}
.tc-head{display:flex;align-items:center;gap:.5rem;}
.prio-badge{font-size:.625rem;font-weight:800;padding:3px 8px;border-radius:5px;text-transform:uppercase;letter-spacing:.05em;}
.p-low{background:#f0fdf4;color:#16a34a;}.p-med{background:#eff6ff;color:#1d4ed8;}.p-high{background:#fff7ed;color:#ea580c;}.p-crit{background:#fef2f2;color:#dc2626;}
.etat-badge{font-size:.625rem;font-weight:800;padding:3px 8px;border-radius:5px;text-transform:uppercase;letter-spacing:.05em;margin-left:auto;}
.e-open{background:#dcfce7;color:#166534;}.e-prog{background:#dbeafe;color:#1e40af;}.e-done{background:#e0f2fe;color:#0369a1;}.e-closed{background:#f1f5f9;color:#64748b;}
.tc-title{font-size:.9375rem;font-weight:700;color:#1e293b;margin:0;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.tc-desc{font-size:.8125rem;color:#64748b;margin:0;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.tc-footer{display:flex;align-items:center;justify-content:space-between;border-top:1px solid #f1f5f9;padding-top:.875rem;}
.dev-info{display:flex;align-items:center;gap:.5rem;}
.dev-av{width:22px;height:22px;border-radius:6px;background:#dbeafe;color:#1d4ed8;font-size:.5625rem;font-weight:800;display:flex;align-items:center;justify-content:center;text-transform:uppercase;flex-shrink:0;}
.dev-name{font-size:.75rem;font-weight:600;color:#475569;}
.unassigned{font-size:.75rem;color:#cbd5e1;font-style:italic;}
.tc-date{font-size:.75rem;color:#94a3b8;}
/* Modal */
.overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;}
.modal{background:white;border-radius:16px;width:100%;max-width:480px;box-shadow:0 24px 48px rgba(0,0,0,.25);overflow:hidden;}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;}
.modal-title{font-size:1rem;font-weight:800;color:#0f172a;margin:0;}
.close-btn{background:none;border:none;font-size:1rem;color:#94a3b8;cursor:pointer;padding:4px;border-radius:6px;transition:color .15s;}
.close-btn:hover{color:#1e293b;}
.mform{padding:1.5rem;display:flex;flex-direction:column;gap:1rem;}
.field{display:flex;flex-direction:column;gap:.35rem;}
.label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;}
.input{width:100%;padding:.625rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;font-size:.9rem;font-family:inherit;outline:none;transition:border-color .2s;}
.input::placeholder{color:#cbd5e1;}
.input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);background:white;}
.ta{resize:vertical;min-height:80px;}
.sel{cursor:pointer;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;}
.alert-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.modal-footer{display:flex;gap:.75rem;justify-content:flex-end;padding-top:.5rem;border-top:1px solid #f1f5f9;margin-top:.5rem;}
.btn-cancel{padding:.5625rem 1rem;background:white;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;}
.btn-primary{padding:.5625rem 1rem;background:#1e293b;color:white;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:.5rem;transition:background .15s;}
.btn-primary:hover:not(:disabled){background:#0f172a;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
</style>