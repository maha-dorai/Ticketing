<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />

      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <button @click="$router.push({ name: 'Projects' })" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Mes Projets
          </button>
          <div v-if="project" class="header-info">
            <h1 class="page-title">{{ project.nom }}</h1>
            <div class="header-meta">
              <span class="status-chip" :class="statusClass(project.statut)">{{ statusLabel(project.statut) }}</span>
              <span class="meta-sep">·</span>
              <span class="meta-text">{{ fmt(project.date_debut) }} → {{ fmt(project.date_fin) }}</span>
            </div>
          </div>
        </div>
        <button v-if="isTesteur && activeTab === 'tickets'" @click="showCreateModal = true" class="btn-create">
          + Nouveau ticket
        </button>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button @click="activeTab = 'info'" :class="['tab', activeTab === 'info' && 'tab-active']">📋 Informations</button>
        <button @click="activeTab = 'tickets'" :class="['tab', activeTab === 'tickets' && 'tab-active']">
          🎟️ Tickets
          <span v-if="tickets.length" class="tab-count">{{ tickets.length }}</span>
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-wrap">
        <svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/></svg>
        Chargement...
      </div>

      <div v-else class="page-content">

        <!-- TAB INFO -->
        <template v-if="activeTab === 'info' && project">
          <div class="info-grid">
            <div class="info-card">
              <div class="info-label">Description</div>
              <div class="info-val">{{ project.description || 'Aucune description.' }}</div>
            </div>
            <div class="info-card">
              <div class="info-label">Statut</div>
              <span class="status-chip" :class="statusClass(project.statut)">{{ statusLabel(project.statut) }}</span>
            </div>
            <div class="info-card">
              <div class="info-label">Date de début</div>
              <div class="info-val">{{ fmt(project.date_debut) }}</div>
            </div>
            <div class="info-card">
              <div class="info-label">Date de fin</div>
              <div class="info-val">{{ fmt(project.date_fin) }}</div>
            </div>
          </div>

          <!-- Members Filters -->
          <div class="members-filters" v-if="project.users?.length">
            <div class="filter-pills">
              <span class="filter-label-inline">Filtrer par rôle :</span>
              <button @click="toggleRole('developpeur')" :class="['pill', filterRole.includes('developpeur') ? 'pill-dev-active' : '']">👨‍💻 Développeurs</button>
              <button @click="toggleRole('testeur')" :class="['pill', filterRole.includes('testeur') ? 'pill-testeur-active' : '']">🕵️ Testeurs</button>
            </div>
            <div class="filter-slider" v-if="filterRole.length === 0 || filterRole.includes('developpeur')">
              <label class="filter-label-inline">Charge Max. (Devs) : <span class="val-badge">{{ filterMaxTickets >= 10 ? 'Tous' : filterMaxTickets }}</span></label>
              <input type="range" min="0" max="10" v-model="filterMaxTickets" class="slider" />
            </div>
          </div>

          <!-- Members -->
          <div class="members-section" v-if="project.users?.length">
            <div class="section-title">Membres du projet ({{ filteredMembers.length }})</div>
            <div v-if="filteredMembers.length === 0" class="text-sm text-gray-500 py-4">Aucun membre ne correspond à ces filtres.</div>
            <div v-else class="members-grid">
              <div v-for="m in filteredMembers" :key="m.id" class="member-card">
                <div class="m-avatar" :class="roleAvatarClass(m.role)">{{ ini(m) }}</div>
                <div class="m-info">
                  <div class="m-name">{{ m.prenom }} {{ m.nom }}</div>
                  <div class="m-roles-wrap">
                    <span class="role-badge" :class="roleBadgeClass(m.role)">{{ roleLabel(m.role) }}</span>
                    <span v-if="m.role === 'developpeur'" class="charge-badge charge-normal">Tickets actifs : {{ m.active_tickets_count }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- TAB TICKETS -->
        <template v-if="activeTab === 'tickets'">

          <!-- Header Tickets Tab (Kanban Button) -->
          <div class="flex justify-between items-center mb-6 bg-blue-50 border border-blue-100 p-4 rounded-xl">
            <div>
              <h3 class="text-sm font-bold text-blue-900">Tableau Kanban</h3>
              <p class="text-xs text-blue-700 mt-1">Gérez vos tickets visuellement avec le glisser-déposer.</p>
            </div>
            <button @click="$router.push({ name: 'Tickets', params: { projectId: projectId } })" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-md transition-all flex items-center gap-2">
              <span class="text-lg">📊</span> Ouvrir le Tableau
            </button>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="filter-group">
              <label class="filter-label">Statut</label>
              <select v-model="filterEtat" class="filter-select">
                <option value="">Tous</option>
                <option value="OUVERT">Ouvert</option>
                <option value="EN_COURS">En cours</option>
                <option value="RESOLU">Résolu</option>
                <option value="FERME">Fermé</option>
              </select>
            </div>
            <div class="filter-group">
              <label class="filter-label">Priorité</label>
              <select v-model="filterPrio" class="filter-select">
                <option value="">Toutes</option>
                <option value="CRITIQUE">🔴 Critique</option>
                <option value="HAUTE">🟠 Haute</option>
                <option value="MOYENNE">🔵 Moyenne</option>
                <option value="BASSE">⚪ Basse</option>
              </select>
            </div>
          </div>

          <!-- Empty -->
          <div v-if="filteredTickets.length === 0" class="empty-tickets">
            <div class="empty-icon">🎫</div>
            <div class="empty-title">Aucun ticket{{ filterEtat || filterPrio ? ' pour ces filtres' : '' }}</div>
            <div v-if="isTesteur" class="empty-sub">Créez votre premier ticket avec le bouton "Nouveau ticket"</div>
          </div>

          <!-- Tickets list -->
          <div v-else class="tickets-list">
            <div v-for="t in filteredTickets" :key="t.id"
              class="ticket-row"
              @click="$router.push({ name: 'TicketDetails', params: { projectId: projectId, id: t.id } })">

              <!-- Priority indicator -->
              <div class="prio-bar" :class="prioBarClass(t.priorite)"></div>

              <div class="ticket-main">
                <div class="ticket-top">
                  <span class="ticket-id">#{{ t.id }}</span>
                  <h3 class="ticket-title">{{ t.titre }}</h3>
                  <span class="etat-badge" :class="etatClass(t.etat)">{{ etatLabel(t.etat) }}</span>
                </div>
                <p v-if="t.description" class="ticket-desc">{{ t.description }}</p>
                <div class="ticket-meta">
                  <span class="prio-badge" :class="prioBadgeClass(t.priorite)">
                    {{ prioIcon(t.priorite) }} {{ t.priorite }}
                  </span>
                  <span class="meta-dot">·</span>
                  <span class="meta-info">
                    🧑‍💻 Créé par {{ t.testeur?.prenom }} {{ t.testeur?.nom }}
                  </span>
                  <span v-if="t.assignment_status === 'approved' && t.developpeur" class="meta-dot">·</span>
                  <span v-if="t.assignment_status === 'approved' && t.developpeur" class="meta-info">
                    👨‍💻 {{ t.developpeur.prenom }} {{ t.developpeur.nom }}
                  </span>
                  <span v-else-if="t.assignment_status === 'pending' && t.proposed_developpeur" class="meta-dot">·</span>
                  <span v-else-if="t.assignment_status === 'pending' && t.proposed_developpeur" class="meta-info">
                    ⏳ Proposé : {{ t.proposed_developpeur.prenom }} {{ t.proposed_developpeur.nom }}
                  </span>
                  <span class="meta-dot">·</span>
                  <span class="meta-info">{{ formatDate(t.created_at) }}</span>
                </div>
              </div>

              <svg class="ticket-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </div>
          </div>
        </template>

      </div>
    </main>

    <!-- Modal Création Ticket -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">

        <!-- Confirmation auto-assign (post-creation) -->
        <template v-if="assignResult">
          <div class="modal-header">
            <h3 class="modal-title">Ticket créé ! ✅</h3>
            <button @click="closeModal" class="modal-close">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="assignResult.success" class="assign-success-box">
              <div v-if="assignResult.is_retour">
                <div class="assign-icon">🔁</div>
                <div class="assign-info">
                  <p class="assign-title">Assignation automatique (Retour)</p>
                  <p class="assign-hint">Assigné d'office à {{ assignResult.dev_prenom }} {{ assignResult.dev_nom }}.</p>
                </div>
              </div>
              <div v-else>
                <div class="assign-icon">⏳</div>
                <div class="assign-info">
                  <p class="assign-title">Assignation automatique en cours de validation</p>
                  <p class="assign-dev">👨‍💻 Proposé à : <strong>{{ assignResult.dev_prenom }} {{ assignResult.dev_nom }}</strong></p>
                  <p class="assign-hint">L'administrateur va valider ou refuser cette assignation. Vous serez notifié(e) une fois confirmée.</p>
                </div>
              </div>
            </div>
            <div v-else class="assign-warn-box">
              <div class="assign-icon">⚠️</div>
              <div class="assign-info">
                <p class="assign-title">Aucun développeur disponible</p>
                <p class="assign-hint">{{ assignResult.message }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeModal" class="btn-submit">Fermer</button>
          </div>
        </template>

        <!-- Formulaire de création -->
        <template v-else>
          <div class="modal-header">
            <h3 class="modal-title">Nouveau ticket</h3>
            <button @click="closeModal" class="modal-close">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitTicket" class="space-y-4">
              <div>
                <label class="form-label">Type de ticket</label>
                <div style="display:flex;gap:1rem;margin-bottom:0.5rem;">
                  <label style="display:flex;align-items:center;gap:0.25rem;font-size:0.875rem;cursor:pointer;">
                    <input type="radio" v-model="form.type" value="NOUVEAU" /> Nouveau
                  </label>
                  <label style="display:flex;align-items:center;gap:0.25rem;font-size:0.875rem;cursor:pointer;">
                    <input type="radio" v-model="form.type" value="RETOUR" /> Retour (Bug sur ticket existant)
                  </label>
                </div>
              </div>

              <div v-if="form.type === 'RETOUR'" style="background:#fffbeb;padding:0.75rem;border-radius:8px;border:1px solid #fef3c7;">
                <label class="form-label text-amber-700">Ticket Parent (Sera assigné d'office à son développeur) *</label>
                <select v-model="form.parent_ticket_id" class="form-input" style="border-color:#fcd34d;">
                  <option :value="null" disabled>-- Sélectionner le ticket concerné --</option>
                  <option v-for="t in validParentTickets" :key="t.id" :value="t.id">
                    #{{ t.id }} - {{ t.titre }} ({{ t.etat }})
                  </option>
                </select>
              </div>

              <div>
                <label class="form-label">Titre *</label>
                <input v-model="form.titre" type="text" class="form-input" placeholder="Titre du ticket" />
              </div>
              
              <div>
                <label class="form-label">Estimation du temps (heures) *</label>
                <input v-model="form.temps_estime" type="number" step="0.5" min="0.5" class="form-input" placeholder="Ex: 2.5" />
              </div>
              
              <div>
                <label class="form-label">Étapes pour reproduire</label>
                <textarea v-model="form.etapes" rows="2" class="form-input" style="resize:none" placeholder="1. Cliquer sur X..."></textarea>
              </div>

              <div>
                <label class="form-label">Résultat attendu vs obtenu</label>
                <textarea v-model="form.resultat" rows="2" class="form-input" style="resize:none" placeholder="Résultat attendu: ... Obtenu: ..."></textarea>
              </div>

              <div>
                <label class="form-label">Notes supplémentaires</label>
                <textarea v-model="form.notes" rows="2" class="form-input" style="resize:none" placeholder="Contexte, logs..."></textarea>
              </div>

              <div>
                <label class="form-label">Pièces jointes</label>
                <input type="file" multiple @change="handleFileUpload" class="form-input py-1" />
                <div v-if="attachments.length" class="text-xs text-blue-600 mt-1">{{ attachments.length }} fichier(s) sélectionné(s)</div>
              </div>

              <div>
                <label class="form-label">Priorité</label>
                <select v-model="form.priorite" class="form-input">
                  <option value="BASSE">Basse</option>
                  <option value="MOYENNE">🔵 Moyenne</option>
                  <option value="HAUTE">🟠 Haute</option>
                  <option value="CRITIQUE">🔴 Critique</option>
                </select>
              </div>
            </form>
            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>
          <div class="modal-footer">
            <button @click="closeModal" class="btn-cancel">Annuler</button>
            <button @click="submitTicket" :disabled="submitting" class="btn-submit">
              {{ submitting ? 'Création...' : 'Créer le ticket' }}
            </button>
          </div>
        </template>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const projectId = route.params.id;

const project  = ref(null);
const tickets  = ref([]);
const loading  = ref(false);
const activeTab = ref('info');

const filterEtat = ref('');
const filterPrio = ref('');

// Membres Filters
const filterRole = ref([]);
const filterMaxTickets = ref(10); // 10 = Tous

const toggleRole = (role) => {
  if (filterRole.value.includes(role)) {
    filterRole.value = filterRole.value.filter(r => r !== role);
  } else {
    filterRole.value.push(role);
  }
};

const showCreateModal = ref(false);
const submitting = ref(false);
const formError  = ref('');
const assignResult = ref(null);
const form       = ref({ titre: '', etapes: '', resultat: '', notes: '', priorite: 'BASSE', temps_estime: null, type: 'NOUVEAU', parent_ticket_id: null });
const attachments = ref([]);

const validParentTickets = computed(() => {
  return tickets.value.filter(t => t.developpeur_id && ['VALIDE', 'A_TESTER', 'RECLAMATION'].includes(t.etat));
});

const handleFileUpload = (event) => {
  attachments.value = Array.from(event.target.files);
};

const isTesteur = computed(() => authStore.currentUser?.role === 'testeur');

const projectDevs = computed(() =>
  (project.value?.users || []).filter(u => u.role === 'developpeur')
);

const filteredMembers = computed(() => {
  if (!project.value?.users) return [];
  return project.value.users.filter(m => {
    if (filterRole.value.length > 0 && !filterRole.value.includes(m.role)) return false;
    if (m.role === 'developpeur' && filterMaxTickets.value < 10 && m.active_tickets_count > filterMaxTickets.value) return false;
    return true;
  });
});

const filteredTickets = computed(() => {
  return tickets.value.filter(t => {
    if (filterEtat.value && t.etat !== filterEtat.value) return false;
    if (filterPrio.value && t.priorite !== filterPrio.value) return false;
    return true;
  });
});

const fetchProject = async () => {
  loading.value = true;
  try {
    const r = await api.get(`/projects/${projectId}`);
    project.value = r.data;
  } catch { router.push({ name: 'Projects' }); }
  finally { loading.value = false; }
};

const fetchTickets = async () => {
  try {
    const r = await api.get(`/projects/${projectId}/tickets`);
    tickets.value = r.data;
  } catch (e) { console.error(e); }
};

onMounted(async () => {
  await fetchProject();
  await fetchTickets();
});

const submitTicket = async () => {
  if (!form.value.titre) { formError.value = 'Le titre est requis.'; return; }
  if (!form.value.temps_estime || form.value.temps_estime <= 0) { formError.value = 'L\'estimation du temps est requise.'; return; }
  submitting.value = true;
  formError.value = '';
  try {
    const formData = new FormData();
    formData.append('titre', form.value.titre);
    formData.append('priorite', form.value.priorite);
    formData.append('temps_estime', form.value.temps_estime);
    formData.append('type', form.value.type);
    if (form.value.type === 'RETOUR' && form.value.parent_ticket_id) {
      formData.append('parent_ticket_id', form.value.parent_ticket_id);
    }
    if (form.value.etapes) formData.append('etapes', form.value.etapes);
    if (form.value.resultat) formData.append('resultat', form.value.resultat);
    if (form.value.notes) formData.append('notes', form.value.notes);
    
    attachments.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file);
    });

    const res = await api.post(`/projects/${route.params.id}/tickets`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    await fetchTickets();
    activeTab.value = 'tickets';
    assignResult.value = res.data.auto_assign;
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la création.';
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  formError.value = '';
  form.value = { titre: '', etapes: '', resultat: '', notes: '', priorite: 'BASSE', temps_estime: null, type: 'NOUVEAU', parent_ticket_id: null };
  attachments.value = [];
  assignResult.value = null;
};

// Helpers
const fmt = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '';
const ini = u => (u.prenom?.[0] || '') + (u.nom?.[0] || '');

const statusLabel = s => ({ ouvert: 'Ouvert', en_cours: 'En cours', archive: 'Archivé' }[s] || s);
const statusClass = s => ({ ouvert: 'st-open', en_cours: 'st-inprogress', archive: 'st-archive' }[s] || '');

const roleLabel = r => ({ testeur: 'Testeur', developpeur: 'Développeur', admin: 'Admin' }[r] || r);
const roleAvatarClass = r => ({ testeur: 'av-testeur', developpeur: 'av-dev', admin: 'av-admin' }[r] || '');
const roleBadgeClass = r => ({ testeur: 'rb-testeur', developpeur: 'rb-dev', admin: 'rb-admin' }[r] || '');

const etatLabel = e => ({ OUVERT: 'Ouvert', EN_COURS: 'En cours', RESOLU: 'Résolu', FERME: 'Fermé' }[e] || e);
const etatClass = e => ({ OUVERT: 'etat-open', EN_COURS: 'etat-inprogress', RESOLU: 'etat-resolved', FERME: 'etat-closed' }[e] || '');

const prioIcon = p => ({ BASSE: '⚪', MOYENNE: '🔵', HAUTE: '🟠', CRITIQUE: '🔴' }[p] || '');
const prioBarClass = p => ({ BASSE: 'prio-basse', MOYENNE: 'prio-moyenne', HAUTE: 'prio-haute', CRITIQUE: 'prio-critique' }[p] || '');
const prioBadgeClass = p => ({ BASSE: 'pb-basse', MOYENNE: 'pb-moyenne', HAUTE: 'pb-haute', CRITIQUE: 'pb-critique' }[p] || '');
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}

.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;display:flex;flex-direction:column;}

/* Header */
.page-header{display:flex;align-items:center;justify-content:space-between;padding:1.5rem 2.5rem;border-bottom:1px solid #e2e8f0;background:white;gap:1rem;flex-wrap:wrap;}
.header-left{display:flex;align-items:center;gap:1.25rem;}
.back-btn{display:flex;align-items:center;gap:6px;font-size:.8125rem;font-weight:600;color:#64748b;background:none;border:1px solid #e2e8f0;padding:.4rem .75rem;border-radius:8px;cursor:pointer;transition:all .15s;white-space:nowrap;}
.back-btn:hover{border-color:#94a3b8;color:#334155;}
.page-title{font-size:1.375rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;}
.header-meta{display:flex;align-items:center;gap:.5rem;margin-top:4px;}
.meta-sep{color:#cbd5e1;}
.meta-text{font-size:.8125rem;color:#64748b;}
.btn-create{padding:.5625rem 1.25rem;background:#2563eb;color:white;border:none;border-radius:9px;font-size:.875rem;font-weight:700;cursor:pointer;transition:background .15s;font-family:inherit;}
.btn-create:hover{background:#1d4ed8;}

/* Tabs */
.tabs{display:flex;gap:4px;padding:.75rem 2.5rem 0;background:white;border-bottom:1px solid #e2e8f0;}
.tab{padding:.625rem 1.25rem;font-size:.875rem;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:.5rem;font-family:inherit;}
.tab:hover{color:#334155;}
.tab-active{color:#2563eb;border-bottom-color:#2563eb;}
.tab-count{background:#dbeafe;color:#1d4ed8;font-size:.6875rem;font-weight:800;padding:1px 7px;border-radius:99px;}

/* Content */
.loading-wrap{display:flex;align-items:center;justify-content:center;gap:.75rem;padding:4rem;color:#94a3b8;font-size:.875rem;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;flex:1;}

/* Info tab */
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;}
.info-card{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;}
.info-label{font-size:.6875rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem;}
.info-val{font-size:.9375rem;font-weight:600;color:#1e293b;}

/* Members Filters */
.members-filters{display:flex;align-items:center;gap:2rem;background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.5rem;margin-bottom:1rem;flex-wrap:wrap;}
.filter-label-inline{font-size:.75rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;}
.filter-pills{display:flex;align-items:center;gap:.75rem;}
.pill{padding:.4rem 1rem;border-radius:99px;border:1px solid #e2e8f0;background:white;font-size:.8125rem;font-weight:700;color:#64748b;cursor:pointer;transition:all .2s;}
.pill:hover{border-color:#cbd5e1;background:#f8fafc;}
.pill-dev-active{background:#dbeafe;border-color:#bfdbfe;color:#1d4ed8;}
.pill-testeur-active{background:#dcfce7;border-color:#bbf7d0;color:#16a34a;}
.filter-slider{display:flex;align-items:center;gap:1rem;}
.slider{accent-color:#3b82f6;cursor:pointer;}
.val-badge{background:#e2e8f0;color:#334155;padding:2px 8px;border-radius:6px;font-size:.75rem;}

/* Members */
.members-section{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;}
.section-title{font-size:.875rem;font-weight:700;color:#0f172a;margin-bottom:1rem;}
.members-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));gap:1rem;}
.member-card{display:flex;align-items:flex-start;gap:.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.875rem 1rem;}
.m-avatar{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.875rem;font-weight:800;text-transform:uppercase;flex-shrink:0;}
.av-testeur{background:#dcfce7;color:#16a34a;}
.av-dev{background:#dbeafe;color:#1d4ed8;}
.av-admin{background:#f3e8ff;color:#7c3aed;}
.m-info{display:flex;flex-direction:column;gap:4px;}
.m-name{font-size:.875rem;font-weight:700;color:#1e293b;}
.m-roles-wrap{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.role-badge{font-size:.625rem;font-weight:800;padding:3px 8px;border-radius:99px;text-transform:uppercase;}
.rb-testeur{background:#dcfce7;color:#16a34a;}
.rb-dev{background:#dbeafe;color:#1d4ed8;}
.rb-admin{background:#f3e8ff;color:#7c3aed;}
.charge-badge{font-size:.625rem;font-weight:800;padding:3px 8px;border-radius:99px;}
.charge-normal{background:#f1f5f9;color:#64748b;}
.charge-high{background:#fee2e2;color:#dc2626;}

/* Status chips */
.status-chip{font-size:.6875rem;font-weight:700;padding:3px 10px;border-radius:99px;}
.st-open{background:#dcfce7;color:#16a34a;}
.st-inprogress{background:#dbeafe;color:#1d4ed8;}
.st-archive{background:#f1f5f9;color:#64748b;}

/* Filters */
.filters-bar{display:flex;gap:1rem;flex-wrap:wrap;}
.filter-group{display:flex;flex-direction:column;gap:4px;}
.filter-label{font-size:.6875rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;}
.filter-select{padding:.4375rem .875rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;color:#1e293b;background:white;outline:none;font-family:inherit;cursor:pointer;}
.filter-select:focus{border-color:#3b82f6;}

/* Tickets list */
.empty-tickets{text-align:center;padding:4rem 2rem;background:white;border:1px solid #e2e8f0;border-radius:14px;}
.empty-icon{font-size:3rem;margin-bottom:.75rem;}
.empty-title{font-size:1rem;font-weight:700;color:#334155;margin-bottom:.25rem;}
.empty-sub{font-size:.875rem;color:#94a3b8;}

.tickets-list{display:flex;flex-direction:column;gap:.5rem;}
.ticket-row{display:flex;align-items:center;gap:1rem;background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;cursor:pointer;transition:all .15s;overflow:hidden;position:relative;}
.ticket-row:hover{box-shadow:0 4px 16px rgba(0,0,0,.06);border-color:#cbd5e1;}
.prio-bar{width:4px;height:100%;position:absolute;left:0;top:0;bottom:0;border-radius:12px 0 0 12px;}
.prio-basse{background:#cbd5e1;}
.prio-moyenne{background:#3b82f6;}
.prio-haute{background:#f97316;}
.prio-critique{background:#ef4444;}
.ticket-main{flex:1;padding-left:.5rem;}
.ticket-top{display:flex;align-items:center;gap:.75rem;margin-bottom:.375rem;flex-wrap:wrap;}
.ticket-id{font-size:.75rem;font-weight:700;color:#94a3b8;}
.ticket-title{font-size:.9375rem;font-weight:700;color:#0f172a;flex:1;}
.ticket-desc{font-size:.8125rem;color:#64748b;margin:0 0 .5rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;}
.ticket-meta{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;}
.meta-dot{color:#cbd5e1;font-size:.75rem;}
.meta-info{font-size:.75rem;color:#64748b;}
.ticket-arrow{color:#cbd5e1;flex-shrink:0;}

/* Etat badges */
.etat-badge{font-size:.6875rem;font-weight:700;padding:3px 10px;border-radius:99px;white-space:nowrap;}
.etat-open{background:#dcfce7;color:#16a34a;}
.etat-inprogress{background:#fef3c7;color:#d97706;}
.etat-resolved{background:#dbeafe;color:#1d4ed8;}
.etat-closed{background:#f1f5f9;color:#64748b;}

/* Priority badges */
.prio-badge{font-size:.6875rem;font-weight:700;padding:2px 8px;border-radius:6px;}
.pb-basse{background:#f1f5f9;color:#64748b;}
.pb-moyenne{background:#dbeafe;color:#1d4ed8;}
.pb-haute{background:#ffedd5;color:#ea580c;}
.pb-critique{background:#fee2e2;color:#dc2626;}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;}
.modal{background:white;border-radius:16px;width:100%;max-width:520px;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,.15);}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;background:#f8fafc;}
.modal-title{font-size:1rem;font-weight:800;color:#0f172a;margin:0;}
.modal-close{background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;line-height:1;padding:0;}
.modal-close:hover{color:#475569;}
.modal-body{padding:1.5rem;display:flex;flex-direction:column;gap:1rem;}
.modal-footer{padding:1rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:.75rem;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.form-label{font-size:.8125rem;font-weight:700;color:#374151;}
.form-input{padding:.5625rem .875rem;border:1px solid #e2e8f0;border-radius:9px;font-size:.875rem;color:#1e293b;outline:none;font-family:inherit;transition:border-color .2s,box-shadow .2s;width:100%;}
.form-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
.form-error{font-size:.8125rem;color:#dc2626;background:#fef2f2;padding:.625rem .875rem;border-radius:8px;}
.btn-cancel{padding:.5625rem 1.25rem;background:#f1f5f9;color:#475569;border:none;border-radius:9px;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-cancel:hover{background:#e2e8f0;}
.btn-submit{padding:.5625rem 1.25rem;background:#2563eb;color:white;border:none;border-radius:9px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-submit:hover:not(:disabled){background:#1d4ed8;}
.btn-submit:disabled{opacity:.5;cursor:not-allowed;}
</style>