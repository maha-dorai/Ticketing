<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />

      <!-- Page Hero -->
      <div class="page-hero">
        <div class="hero-content">
          <div class="hero-text">
            <h1 class="hero-title">Tableau des projets</h1>
          </div>
          <div class="hero-actions">
            <button @click="openCreate" class="btn-new">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
              Nouveau projet
            </button>
          </div>
        </div>

        <!-- Search bar -->
        <div class="search-bar">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
          </svg>
          <input v-model="search" @input="onSearch" type="text" placeholder="Rechercher un projet..." class="search-input" />
          <kbd v-if="!search" class="search-kbd">⌘K</kbd>
        </div>
      </div>

      <div class="page-body">

        <!-- Alert -->
        <div v-if="globalMsg" class="alert" :class="globalOk ? 'alert-ok' : 'alert-err'">
          {{ globalOk ? '✓' : '✕' }} {{ globalMsg }}
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="loader-ring"></div>
          <p>Chargement des projets…</p>
        </div>

        <!-- Empty -->
        <div v-else-if="!projects.length" class="empty-state">
          <div class="empty-visual">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="1.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
            </svg>
          </div>
          <h3>Aucun projet trouvé</h3>
          <p>Aucun projet ne correspond à votre recherche.</p>
        </div>

        <!-- KANBAN BOARD -->
        <div v-else class="kanban-board">
          <div
            v-for="col in columns"
            :key="col.id"
            class="kanban-col"
            @dragover.prevent
            @dragenter.prevent
            @drop="onDrop($event, col.id)"
          >
            <!-- Column header -->
            <div class="col-head" :class="`col-head--${col.id}`">
              <div class="col-head-left">
                <span class="col-indicator" :class="`ind--${col.id}`"></span>
                <span class="col-title">{{ col.title }}</span>
              </div>
              <span class="col-badge">{{ getProjectsByStatus(col.id).length }}</span>
            </div>

            <!-- Cards -->
            <div class="col-body">
              <div v-if="getProjectsByStatus(col.id).length === 0" class="col-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span>Aucun projet</span>
              </div>

              <div
                v-for="(p, idx) in getProjectsByStatus(col.id)"
                :key="p.id"
                class="project-card"
                :class="[`accent--${col.id}`, { 'dragging': dragProject?.id === p.id }]"
                :style="{ animationDelay: `${idx * 60}ms` }"
                draggable="true"
                @dragstart="onDragStart($event, p)"
                @dragend="onDragEnd"
              >
                <div class="card-body">
                  <!-- Header row -->
                  <div class="card-header">
                    <div class="card-icon" :class="`icon--${col.id}`">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                      </svg>
                    </div>
                    <div class="card-admin-actions">
                      <button @click.stop="openEdit(p)" class="btn-icon" title="Modifier">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                      </button>
                      <button @click.stop="openAssign(p)" class="btn-icon" title="Affecter membres">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                      </button>
                    </div>
                  </div>

                  <!-- Title -->
                  <h4 class="card-title" @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })">{{ p.nom }}</h4>

                  <!-- Description -->
                  <p class="card-desc">{{ p.description || 'Aucune description fournie.' }}</p>

                  <!-- Archive dates -->
                  <div v-if="p.statut === 'archive'" class="card-dates">
                    <div class="date-row">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/></svg>
                      <span>{{ fmt(p.date_debut) }} → {{ fmt(p.date_fin) }}</span>
                    </div>
                    <div v-if="p.creator" class="date-row" style="margin-top:4px">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                      <span>{{ p.creator?.prenom }} {{ p.creator?.nom }}</span>
                    </div>
                  </div>

                  <!-- Footer -->
                  <div class="card-footer">
                    <!-- Avatars -->
                    <div class="avatars" v-if="p.users?.length">
                      <div
                        v-for="(m, i) in p.users.slice(0, 4)"
                        :key="m.id"
                        class="avatar"
                        :style="{ zIndex: 10 - i }"
                        :title="`${m.prenom} ${m.nom}`"
                      >{{ (m.prenom[0] || '') + (m.nom[0] || '') }}</div>
                      <div v-if="p.users.length > 4" class="avatar avatar-more">+{{ p.users.length - 4 }}</div>
                    </div>
                    <span v-else class="no-members">Sans membres</span>

                    <!-- Tickets count -->
                    <div class="ticket-count">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg>
                      <span>{{ p.tickets_count || 0 }} ticket{{ (p.tickets_count || 0) !== 1 ? 's' : '' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Drag handle hint -->
                <div class="card-drag-hint" title="Glisser pour changer de statut">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 100-4 2 2 0 000 4zM8 14a2 2 0 100-4 2 2 0 000 4zM8 22a2 2 0 100-4 2 2 0 000 4zM16 6a2 2 0 100-4 2 2 0 000 4zM16 14a2 2 0 100-4 2 2 0 000 4zM16 22a2 2 0 100-4 2 2 0 000 4z"/></svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination">
          <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="page-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Précédent
          </button>
          <div class="page-dots">
            <span
              v-for="n in pagination.last_page"
              :key="n"
              class="page-dot"
              :class="{ active: n === pagination.current_page }"
              @click="loadPage(n)"
            ></span>
          </div>
          <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="page-btn">
            Suivant
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
          </button>
        </div>
      </div>
    </main>

    <!-- ═══ MODAL CREATE / EDIT ═══ -->
    <div v-if="showModal" class="overlay" @click.self="showModal=false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">{{ editing ? 'Modifier le projet' : 'Nouveau projet' }}</h3>
          <button @click="showModal=false" class="close-btn">✕</button>
        </div>
        <form @submit.prevent="saveProject" class="mform">
          <div class="field">
            <label class="label">Nom du projet *</label>
            <input v-model="form.nom" required placeholder="Ex : Refonte du site web" class="input" />
          </div>
          <div class="field">
            <label class="label">Description</label>
            <textarea v-model="form.description" placeholder="Décrivez brièvement l'objectif du projet..." class="input ta" rows="3"></textarea>
          </div>
          <div class="row2">
            <div class="field">
              <label class="label">Date de début</label>
              <input v-model="form.date_debut" type="date" class="input" />
            </div>
            <div class="field">
              <label class="label">Date de fin</label>
              <input v-model="form.date_fin" type="date" class="input" />
            </div>
          </div>
          <div v-if="editing" class="field">
            <label class="label">Statut</label>
            <select v-model="form.statut" class="input sel">
              <option value="ouvert">🟢 Ouvert</option>
              <option value="en_cours">🔵 En cours</option>
              <option value="archive">📦 Fermé (Archivé)</option>
            </select>
            <span v-if="form.statut === 'archive'" class="hint-text">Un projet ne peut être fermé que si tous ses tickets sont VALIDÉS.</span>
          </div>
          <div v-if="formError" class="alert alert-err">✕ {{ formError }}</div>
          <div class="modal-footer">
            <button type="button" @click="showModal=false" class="btn-cancel">Annuler</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              <svg v-if="saving" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
              <span v-else>{{ editing ? 'Enregistrer' : 'Créer le projet' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ═══ MODAL ASSIGN ═══ -->
    <div v-if="showAssign" class="overlay" @click.self="showAssign=false">
      <div class="modal modal-wide">
        <div class="modal-header">
          <h3 class="modal-title">Affecter des membres — {{ currentProject?.nom }}</h3>
          <button @click="showAssign=false" class="close-btn">✕</button>
        </div>
        <div class="assign-body">
          <p class="assign-hint">Sélectionnez les membres actifs à affecter à ce projet.</p>
          <div v-if="assignError" class="alert alert-err" style="margin-bottom:1rem;">✕ {{ assignError }}</div>
          <div class="member-grid">
            <label v-for="u in activeMembers" :key="u.id" class="member-check" :class="{selected: selectedIds.includes(u.id)}">
              <input type="checkbox" :value="u.id" v-model="selectedIds" class="hidden-cb"/>
              <div class="mc-av">{{ (u.prenom[0]||'')+(u.nom[0]||'') }}</div>
              <div class="mc-info">
                <p class="mc-name">{{ u.prenom }} {{ u.nom }}</p>
                <p class="mc-role">{{ u.role }}</p>
              </div>
              <span class="check-mark">{{ selectedIds.includes(u.id) ? '✓' : '' }}</span>
            </label>
          </div>
          <div class="modal-footer">
            <button @click="showAssign=false" class="btn-cancel">Annuler</button>
            <button @click="saveAssign" :disabled="assigning" class="btn-primary">
              <span>Confirmer l'affectation ({{ selectedIds.length }})</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';
import AppHeader from '../../components/AppHeader.vue';

const projects = ref([]);
const allUsers = ref([]);
const loading = ref(false);
const saving = ref(false);
const assigning = ref(false);
const globalMsg = ref('');
const globalOk = ref(true);
const search = ref('');
const pagination = ref({ current_page: 1, last_page: 1 });

const showModal = ref(false);
const showAssign = ref(false);
const editing = ref(false);
const currentProject = ref(null);
const formError = ref('');
const assignError = ref('');
const selectedIds = ref([]);

const dragProject = ref(null);

const form = ref({ nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert' });

const columns = [
  { id: 'ouvert',   title: 'Ouverts'  },
  { id: 'en_cours', title: 'En cours' },
  { id: 'archive',  title: 'Fermés'   },
];

let searchTimer = null;

const filteredProjects = computed(() => projects.value);

const getProjectsByStatus = (status) => {
  return filteredProjects.value.filter(p => p.statut === status);
};

const activeMembers = computed(() => {
  const assignedIds = (currentProject.value?.users || []).map(u => u.id);
  return allUsers.value.filter(u =>
    u.statut === 'actif' &&
    !['chef_de_projet', 'admin'].includes(u.role) &&
    !assignedIds.includes(u.id)
  );
});

const fetchProjects = async (page = 1) => {
  loading.value = true;
  try {
    const r = await api.get('/projects', { params: { search: search.value || undefined, page } });
    projects.value = r.data.data || r.data;
    if (r.data.current_page) pagination.value = r.data;
  } catch { msg('Erreur chargement des projets.', false); }
  finally { loading.value = false; }
};

const fetchUsers = async () => {
  try { const r = await api.get('/users'); allUsers.value = r.data; } catch {}
};

onMounted(() => { fetchProjects(); fetchUsers(); });

const onSearch = () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => fetchProjects(1), 350); };
const loadPage = (p) => { if (p >= 1 && p <= pagination.value.last_page) fetchProjects(p); };

const msg = (m, ok = true) => {
  globalMsg.value = m; globalOk.value = ok;
  setTimeout(() => globalMsg.value = '', 4000);
};

const fmt = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const openCreate = () => {
  editing.value = false;
  form.value = { nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert' };
  formError.value = '';
  showModal.value = true;
};

const openEdit = (p) => {
  editing.value = true;
  currentProject.value = p;
  form.value = { nom: p.nom, description: p.description || '', date_debut: p.date_debut ? p.date_debut.split('T')[0] : '', date_fin: p.date_fin ? p.date_fin.split('T')[0] : '', statut: p.statut };
  formError.value = '';
  showModal.value = true;
};

const saveProject = async () => {
  saving.value = true; formError.value = '';
  try {
    if (editing.value) {
      await api.put(`/projects/${currentProject.value.id}`, form.value);
      msg('Projet mis à jour ✓');
    } else {
      await api.post('/projects', form.value);
      msg('Projet créé ✓');
    }
    showModal.value = false;
    await fetchProjects();
  } catch (e) {
    const errs = e.response?.data?.errors;
    formError.value = errs ? String(Object.values(errs).flat()[0]) : e.response?.data?.message || 'Erreur.';
  } finally { saving.value = false; }
};

const openAssign = (p) => {
  currentProject.value = p;
  selectedIds.value = (p.users || []).map(u => u.id);
  assignError.value = '';
  showAssign.value = true;
};

const saveAssign = async () => {
  assigning.value = true;
  assignError.value = '';
  try {
    await api.post(`/projects/${currentProject.value.id}/assign`, { user_ids: selectedIds.value });
    msg('Membres affectés ✓');
    showAssign.value = false;
    await fetchProjects();
  } catch (e) {
    assignError.value = e.response?.data?.message || 'Erreur.';
  } finally { assigning.value = false; }
};

// --- DRAG AND DROP ---
const onDragStart = (e, project) => {
  dragProject.value = project;
  e.dataTransfer.effectAllowed = 'move';
  // Fallback for Firefox
  e.dataTransfer.setData('text/plain', project.id);
};

const onDragEnd = () => {
  dragProject.value = null;
};

const onDrop = async (e, newStatus) => {
  const p = dragProject.value;
  if (!p) return;
  if (p.statut === newStatus) return;

  // Empêcher de repasser à Ouvert si déjà En cours ou Archivé
  if (newStatus === 'ouvert' && (p.statut === 'en_cours' || p.statut === 'archive')) {
    msg("Un projet commencé ne peut pas redevenir 'Ouvert'.", false);
    dragProject.value = null;
    return;
  }

  // Save previous status for optimism (optional, doing pessimistic here to show errors properly)
  try {
    await api.put(`/projects/${p.id}`, {
      nom: p.nom,
      description: p.description,
      date_debut: p.date_debut ? p.date_debut.split('T')[0] : '',
      date_fin: p.date_fin ? p.date_fin.split('T')[0] : '',
      statut: newStatus
    });
    msg('Projet déplacé avec succès ✓');
    await fetchProjects(); // Refresh everything to get updated tickets counts/history
  } catch (err) {
    msg(err.response?.data?.message || 'Erreur lors du déplacement.', false);
  }
  
  dragProject.value = null;
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap');

*, *::before, *::after { box-sizing: border-box; }

/* ── Layout ─────────────────────────────────────────────────────── */
.layout { display: flex; min-height: 100vh; background: #f0f4f9; font-family: 'Plus Jakarta Sans', sans-serif; }
.main   { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }

/* ── Page Hero ──────────────────────────────────────────────────── */
.page-hero {
  background: #fff;
  border-bottom: 1px solid #e4eaf3;
  padding: 2rem 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  flex-shrink: 0;
}
.hero-content {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.hero-title {
  margin: 0;
  font-size: 1.875rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -.03em;
  line-height: 1.1;
}
.hero-sub {
  margin: .375rem 0 0;
  font-size: .875rem;
  color: #64748b;
  font-weight: 500;
}
.hero-actions {
  display: flex;
  align-items: center;
  gap: .875rem;
  flex-wrap: wrap;
}

/* Stats pills */
.hero-stats {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e4eaf3;
  border-radius: 12px;
  padding: .625rem 1rem;
  gap: .75rem;
}
.stat-pill  { display: flex; align-items: center; gap: .4rem; }
.stat-dot   { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.dot-green  { background: #10b981; }
.dot-blue   { background: #3b82f6; }
.dot-gray   { background: #94a3b8; }
.stat-val   { font-size: 1rem; font-weight: 800; color: #0f172a; line-height: 1; }
.stat-label { font-size: .75rem; font-weight: 500; color: #64748b; }
.stat-divider { width: 1px; height: 20px; background: #e4eaf3; }

/* New project button */
.btn-new {
  display: flex; align-items: center; gap: .5rem;
  padding: .625rem 1.25rem;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white; border: none; border-radius: 10px;
  font-size: .875rem; font-weight: 700; font-family: inherit;
  cursor: pointer; transition: transform .2s, box-shadow .2s;
  white-space: nowrap;
}
.btn-new:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,.28); }

/* Search bar */
.search-bar {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute; left: 14px;
  color: #94a3b8; pointer-events: none;
}
.search-input {
  width: 100%;
  padding: .75rem 3rem .75rem 2.5rem;
  border: 1px solid #e4eaf3;
  border-radius: 10px;
  font-size: .875rem;
  font-family: inherit;
  color: #1e293b;
  background: #f8fafc;
  outline: none;
  transition: border-color .18s, box-shadow .18s, background .18s;
}
.search-input:focus {
  background: #fff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.search-input::placeholder { color: #c0cfe0; }
.search-kbd {
  position: absolute; right: 12px;
  font-size: .6875rem; color: #b0bec5;
  background: #f1f5f9; border: 1px solid #e4eaf3;
  border-radius: 5px; padding: 2px 6px;
  font-family: inherit; pointer-events: none;
}

/* ── Page Body ──────────────────────────────────────────────────── */
.page-body { flex: 1; padding: 2rem 2.5rem; display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Alert ──────────────────────────────────────────────────────── */
.alert { padding: .75rem 1rem; border-radius: 8px; font-size: .875rem; font-weight: 600; }
.alert-ok  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.alert-err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Loading / Empty ────────────────────────────────────────────── */
.loading-state {
  display: flex; flex-direction: row;
  align-items: center; justify-content: center;
  gap: .625rem; padding: 5rem 0;
  color: #94a3b8; font-size: .875rem;
}
.loader-ring {
  width: 36px; height: 36px;
  border: 3px solid #e4eaf3;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  display: flex; flex-direction: column;
  align-items: center; padding: 5rem 2rem;
  text-align: center;
}
.empty-visual {
  width: 72px; height: 72px;
  background: #f1f5f9; border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.25rem;
}
.empty-state h3 { margin: 0 0 .5rem; font-size: 1.125rem; font-weight: 700; color: #1e293b; }
.empty-state p  { margin: 0; font-size: .875rem; color: #94a3b8; }

/* ── Kanban Board ───────────────────────────────────────────────── */
.kanban-board {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  align-items: start;
}

/* Column */
.kanban-col {
  background: #fff;
  border: 1px solid #e4eaf3;
  border-radius: 14px;
  overflow: hidden;
}

/* Column header */
.col-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.125rem;
  border-bottom: 1px solid #e4eaf3;
  background: #fafbfd;
}
.col-head--ouvert   { border-top: 3px solid #10b981; }
.col-head--en_cours { border-top: 3px solid #3b82f6; }
.col-head--archive  { border-top: 3px solid #cbd5e1; }

.col-head-left { display: flex; align-items: center; gap: .5rem; }
.col-indicator { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ind--ouvert   { background: #10b981; }
.ind--en_cours { background: #3b82f6; }
.ind--archive  { background: #cbd5e1; }

.col-title { font-size: .875rem; font-weight: 700; color: #1e293b; }
.col-badge {
  min-width: 22px; height: 22px;
  background: #f1f5f9; color: #475569;
  font-size: .6875rem; font-weight: 800;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 6px;
}

/* Column body */
.col-body {
  padding: .875rem;
  display: flex;
  flex-direction: column;
  gap: .75rem;
  min-height: 80px;
}
.col-empty {
  display: flex; align-items: center; justify-content: center;
  gap: .5rem; color: #c0cfe0;
  font-size: .8125rem; font-weight: 500;
  padding: 1.5rem 0;
}

/* ── Project Card ───────────────────────────────────────────────── */
.project-card {
  position: relative;
  background: #fff;
  border: 1px solid #e8eef6;
  border-radius: 10px;
  cursor: grab;
  overflow: hidden;
  transition: border-color .18s, box-shadow .18s, transform .18s;
  animation: cardIn .35s ease both;
}
@keyframes cardIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.project-card:hover {
  border-color: #bfcfe8;
  box-shadow: 0 4px 16px rgba(15, 23, 42, .08);
  transform: translateY(-2px);
}
.project-card:active { cursor: grabbing; }
.dragging { opacity: 0.45; transform: scale(0.97); }

/* Left accent line */
.project-card.accent--ouvert   { border-left: 4px solid #10b981; }
.project-card.accent--en_cours { border-left: 4px solid #3b82f6; }
.project-card.accent--archive  { border-left: 4px solid #cbd5e1; }

.card-body { padding: 1rem; }

/* Card header row */
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .6rem; }
.card-icon {
  width: 26px; height: 26px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.icon--ouvert   { background: #ecfdf5; color: #059669; }
.icon--en_cours { background: #eff6ff; color: #2563eb; }
.icon--archive  { background: #f1f5f9; color: #64748b; }

/* Admin action buttons */
.card-admin-actions { display: flex; gap: .25rem; }
.btn-icon {
  display: flex; align-items: center; justify-content: center;
  width: 26px; height: 26px;
  background: #f8fafc; border: 1px solid #e4eaf3;
  border-radius: 6px; cursor: pointer; color: #64748b;
  transition: background .15s, color .15s, border-color .15s;
}
.btn-icon:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }

/* Title */
.card-title {
  margin: 0 0 .4rem;
  font-size: .9375rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
  cursor: pointer;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  transition: color .15s;
}
.card-title:hover { color: #2563eb; text-decoration: underline; }

/* Description */
.card-desc {
  margin: 0 0 .875rem;
  font-size: .8125rem;
  color: #64748b;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Dates */
.card-dates {
  margin-bottom: .75rem;
  padding: .5rem .625rem;
  background: #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 6px;
}
.date-row {
  display: flex; align-items: center; gap: 5px;
  font-size: .7rem; color: #64748b; font-weight: 500;
}

/* Footer */
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: .75rem;
  border-top: 1px solid #f1f5f9;
}

/* Avatars */
.avatars { display: flex; }
.avatar {
  width: 24px; height: 24px;
  border-radius: 50%;
  background: #dbeafe; color: #1d4ed8;
  font-size: .55rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #fff;
  margin-left: -5px;
  flex-shrink: 0;
  text-transform: uppercase;
  transition: transform .15s;
}
.avatar:first-child { margin-left: 0; }
.avatar:hover { transform: scale(1.15); z-index: 5; }
.avatar-more { background: #f1f5f9; color: #64748b; font-size: .55rem; }
.no-members { font-size: .7rem; color: #c0cfe0; font-weight: 500; }

/* Ticket count */
.ticket-count {
  display: flex; align-items: center; gap: 4px;
  font-size: .7rem; font-weight: 700; color: #94a3b8;
  background: #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 6px;
  padding: 3px 8px;
}

/* Drag hint */
.card-drag-hint {
  position: absolute;
  top: .75rem; right: .75rem;
  color: #d1dae8;
  opacity: 0;
  transition: opacity .18s;
}
.project-card:hover .card-drag-hint { opacity: 1; }

/* ── Pagination ─────────────────────────────────────────────────── */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e4eaf3;
}
.page-btn {
  display: flex; align-items: center; gap: 5px;
  padding: .5rem 1rem;
  background: #fff;
  border: 1px solid #e4eaf3;
  border-radius: 8px;
  font-size: .8125rem; font-weight: 600; color: #475569;
  font-family: inherit;
  cursor: pointer;
  transition: all .15s;
}
.page-btn:hover:not(:disabled) { border-color: #bfcfe8; color: #1e293b; background: #f8fafc; }
.page-btn:disabled { opacity: .45; cursor: not-allowed; }

.page-dots { display: flex; align-items: center; gap: 5px; }
.page-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #e4eaf3; cursor: pointer;
  transition: all .15s;
}
.page-dot:hover { background: #bfcfe8; }
.page-dot.active { background: #3b82f6; transform: scale(1.2); }

/* ── Modal ──────────────────────────────────────────────────────── */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.6); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal { background: white; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 24px 48px rgba(0,0,0,.25); overflow: hidden; }
.modal-wide { max-width: 560px; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.modal-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; }
.close-btn { background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer; padding: 0; }
.close-btn:hover { color: #1e293b; }
.mform { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.label { font-size: .75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.input { padding: .625rem .875rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #1e293b; font-size: .9rem; font-family: inherit; outline: none; transition: border-color .2s; }
.input:focus { border-color: #3b82f6; background: white; }
.ta { resize: vertical; min-height: 80px; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.hint-text { font-size: .75rem; color: #eab308; font-weight: 600; margin-top: 4px; }
.modal-footer { display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1rem; }
.btn-cancel { padding: .5rem 1rem; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; font-family: inherit; cursor: pointer; }
.btn-primary { padding: .5rem 1rem; background: #1e293b; color: white; border: none; border-radius: 8px; font-weight: 700; font-family: inherit; cursor: pointer; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }

/* Assign modal */
.assign-body { padding: 1.5rem; }
.assign-hint { font-size: .875rem; color: #64748b; margin: 0 0 1rem; }
.member-grid { max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; }
.member-check { display: flex; align-items: center; gap: .75rem; padding: .75rem; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: background .15s; }
.member-check:hover { background: #f8fafc; }
.member-check.selected { background: #eff6ff; border-color: #bfdbfe; }
.hidden-cb { display: none; }
.mc-av { width: 32px; height: 32px; background: #dbeafe; color: #1d4ed8; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .75rem; flex-shrink: 0; }
.mc-info { flex: 1; }
.mc-name { margin: 0; font-size: .875rem; font-weight: 700; color: #1e293b; }
.mc-role { margin: 0; font-size: .75rem; color: #64748b; text-transform: capitalize; }
.check-mark { color: #2563eb; font-weight: 800; }

/* Spinner for save button */
.spin { animation: spin .8s linear infinite; }
</style>