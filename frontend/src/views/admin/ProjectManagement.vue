<template>
  <AppLayout>

      <PageHeader variant="hero">
        <template #title>Tableau des projets</template>
        <template #actions>
          <BaseButton variant="primary" @click="openCreate">
            <Plus :size="15" aria-hidden="true" />
            Nouveau projet
          </BaseButton>
        </template>
        <template #toolbar>
          <div class="ds-search">
            <Search class="ds-search-icon" :size="16" aria-hidden="true" />
            <input v-model="search" @input="onSearch" type="text" placeholder="Rechercher un projet..." class="ds-search-input" />
            <kbd v-if="!search" class="ph-search__kbd">⌘K</kbd>
          </div>
        </template>
      </PageHeader>

      <div class="page-body">

        <!-- Alert -->
        <BaseAlert
          v-if="globalMsg"
          :variant="globalOk ? 'success' : 'error'"
          :icon="globalOk ? CheckCircle2 : XCircle"
          class="ds-page-feedback"
        >
          {{ globalMsg }}
        </BaseAlert>

        <!-- Loading -->
        <div v-if="loading" class="ds-loading-state">
          <Loader2 class="spin" :size="20" aria-hidden="true" />
          Chargement des projets…
        </div>

        <!-- Empty -->
        <div v-else-if="!projects.length" class="ds-empty-state">
          <div class="ds-empty-visual">
            <Folder :size="48" :stroke-width="1.2" aria-hidden="true" />
          </div>
          <h3>Aucun projet trouvé</h3>
          <p>Aucun projet ne correspond à votre recherche.</p>
        </div>

        <!-- KANBAN BOARD -->
        <div v-else class="ds-kanban-board">
          <div
            v-for="col in columns"
            :key="col.id"
            class="ds-kanban-column ds-kanban-column--white"
            @dragover.prevent
            @dragenter.prevent
            @drop="onDrop($event, col.id)"
          >
            <!-- Column header -->
            <div class="ds-kanban-column-header" :class="`ds-kanban-column--${col.id}`">
              <h3 class="ds-kanban-column-title">
                <span class="ds-status-dot" :class="`ds-status-dot--${col.id}`" aria-hidden="true" />
                {{ col.title }}
              </h3>
              <span class="ds-kanban-column-count">{{ getProjectsByStatus(col.id).length }}</span>
            </div>

            <!-- Cards -->
            <div class="ds-kanban-column-body">
              <div v-if="getProjectsByStatus(col.id).length === 0" class="ds-kanban-column-empty">
                <Plus :size="24" :stroke-width="1.2" aria-hidden="true" />
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
                      <Folder :size="14" :stroke-width="2" aria-hidden="true" />
                    </div>
                    <div class="card-admin-actions">
                      <BaseButton @click.stop="openEdit(p)" variant="ghost" size="sm" aria-label="Modifier le projet">
                        <Pencil :size="13" aria-hidden="true" />
                      </BaseButton>
                      <BaseButton @click.stop="openAssign(p)" variant="ghost" size="sm" aria-label="Affecter des membres">
                        <Users :size="13" aria-hidden="true" />
                      </BaseButton>
                    </div>
                  </div>

                  <!-- Title -->
                  <h4 class="card-title" @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })">{{ p.nom }}</h4>

                  <!-- Description -->
                  <p class="card-desc">{{ p.description || 'Aucune description fournie.' }}</p>

                  <!-- Deadline badge (ouvert / en_cours only) -->
                  <div v-if="p.statut !== 'archive' && p.date_fin" class="deadline-row">
                    <span class="deadline-badge" :class="deadlineBadge(p)?.color">
                      <Clock :size="10" :stroke-width="2.5" aria-hidden="true" />
                      {{ deadlineBadge(p)?.label }}
                    </span>
                  </div>

                  <!-- Clôture date (archive only) -->
                  <div v-if="p.statut === 'archive'" class="card-dates">
                    <div class="date-row">
                      <Calendar :size="11" :stroke-width="2" aria-hidden="true" />
                      <span>Ouvert le {{ fmt(p.date_debut) }}</span>
                    </div>
                    <div class="date-row" style="margin-top:4px">
                      <Check :size="11" :stroke-width="2" aria-hidden="true" />
                      <span>Clôturé le {{ fmt(p.date_cloture || p.date_fin) }}</span>
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
                      <Ticket :size="11" :stroke-width="2" aria-hidden="true" />
                      <span>{{ p.tickets_count || 0 }} ticket{{ (p.tickets_count || 0) !== 1 ? 's' : '' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Drag handle hint -->
                <div class="card-drag-hint" title="Glisser pour changer de statut">
                  <GripVertical :size="12" fill="currentColor" aria-hidden="true" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="ds-pagination">
          <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="ds-page-btn" aria-label="Page précédente">
            <ChevronLeft :size="14" :stroke-width="2.5" aria-hidden="true" />
            Précédent
          </button>
          <div class="ds-page-dots">
            <span
              v-for="n in pagination.last_page"
              :key="n"
              class="ds-page-dot"
              :class="{ active: n === pagination.current_page }"
              @click="loadPage(n)"
            ></span>
          </div>
          <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="ds-page-btn" aria-label="Page suivante">
            Suivant
            <ChevronRight :size="14" :stroke-width="2.5" aria-hidden="true" />
          </button>
        </div>
      </div>

    <!-- ═══ MODAL CREATE / EDIT ═══ -->
    <BaseModal v-model="showModal" :title="editing ? 'Modifier le projet' : 'Nouveau projet'" size="lg">
      <form @submit.prevent="saveProject" class="mform">
        <div class="field">
          <label class="label">Nom du projet <span class="required-tag">*</span></label>
          <BaseInput v-model="form.nom" required placeholder="Ex : Refonte du site web" />
        </div>
        <BaseInput v-model="form.description" label="Description" placeholder="Décrivez brièvement l'objectif du projet..." textarea rows="3" />
        <div class="row2">
          <div class="field">
            <label class="label">Date de début</label>
            <BaseInput v-model="form.date_debut" type="date" />
            <span class="field-hint">Aujourd'hui par défaut</span>
          </div>
          <div class="field">
            <label class="label">Deadline <span class="optional-tag">optionnel</span></label>
            <BaseInput v-model="form.date_fin" type="date" :min="form.date_debut" />
            <span class="field-hint">Affichée comme badge sur la carte</span>
          </div>
        </div>
        <div v-if="editing" class="field">
          <label class="label">Statut</label>
          <select v-model="form.statut" class="ds-input">
            <option value="ouvert">Ouvert</option>
            <option value="en_cours">En cours</option>
            <option value="archive">Fermé (Archivé)</option>
          </select>
          <span v-if="form.statut === 'archive'" class="hint-text">Un projet ne peut être fermé que si tous ses tickets sont VALIDÉS.</span>
        </div>
        <BaseAlert v-if="formError" variant="error" :icon="XCircle" class="ds-page-feedback">{{ formError }}</BaseAlert>

        <!-- Member selection (create only) -->
        <div v-if="!editing" class="field">
          <label class="label">Membres <span class="required-tag">*</span></label>
          <div class="member-grid-inline">
            <label
              v-for="u in allUsers.filter(u => u.statut === 'actif' && ['developpeur', 'testeur'].includes(u.role))"
              :key="u.id"
              class="member-check"
              :class="{ selected: form.user_ids.includes(u.id) }"
            >
              <input type="checkbox" :value="u.id" v-model="form.user_ids" class="hidden-cb"/>
              <div class="mc-av">{{ (u.prenom[0]||'')+(u.nom[0]||'') }}</div>
              <div class="mc-info">
                <p class="mc-name">{{ u.prenom }} {{ u.nom }}</p>
                <p class="mc-role">{{ u.role }}</p>
              </div>
              <Check v-if="form.user_ids.includes(u.id)" class="check-mark-icon" :size="14" aria-hidden="true" />
            </label>
          </div>
          <span v-if="form.user_ids.length" class="field-hint">{{ form.user_ids.length }} membre(s) sélectionné(s)</span>
        </div>
      </form>
      <template #footer>
        <BaseButton variant="secondary" @click="showModal=false">Annuler</BaseButton>
        <BaseButton variant="slate" :disabled="saving" :loading="saving" @click="saveProject">
          <span>{{ editing ? 'Enregistrer' : 'Créer le projet' }}</span>
        </BaseButton>
      </template>
    </BaseModal>

    <!-- ═══ MODAL ASSIGN ═══ -->
    <BaseModal v-model="showAssign" :title="`Affecter des membres — ${currentProject?.nom}`" size="lg">
      <div class="assign-body">
        <p class="assign-hint">Sélectionnez les membres actifs à affecter à ce projet.</p>
        <BaseAlert v-if="assignError" variant="error" :icon="XCircle" class="ds-page-feedback">{{ assignError }}</BaseAlert>
        <div class="member-grid">
          <label v-for="u in activeMembers" :key="u.id" class="member-check" :class="{selected: selectedIds.includes(u.id)}">
            <input type="checkbox" :value="u.id" v-model="selectedIds" class="hidden-cb"/>
            <div class="mc-av">{{ (u.prenom[0]||'')+(u.nom[0]||'') }}</div>
            <div class="mc-info">
              <p class="mc-name">{{ u.prenom }} {{ u.nom }}</p>
              <p class="mc-role">{{ u.role }}</p>
            </div>
            <Check v-if="selectedIds.includes(u.id)" class="check-mark-icon" :size="14" aria-hidden="true" />
          </label>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="showAssign=false">Annuler</BaseButton>
        <BaseButton variant="slate" :disabled="assigning" :loading="assigning" @click="saveAssign">
          Confirmer l'affectation ({{ selectedIds.length }})
        </BaseButton>
      </template>
    </BaseModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import {
  Calendar,
  Check,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Clock,
  Folder,
  GripVertical,
  Loader2,
  Pencil,
  Plus,
  Search,
  Ticket,
  Users,
  XCircle,
} from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import BaseAlert from '../../components/ui/BaseAlert.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseModal from '../../components/ui/BaseModal.vue';

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

const form = ref({ nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert', user_ids: [] });

// Returns today's date as YYYY-MM-DD
const todayStr = () => new Date().toISOString().split('T')[0];

// Deadline badge logic for a project
const deadlineBadge = (p) => {
  if (!p.date_fin || p.statut === 'archive') return null;
  const today = new Date(); today.setHours(0,0,0,0);
  const fin   = new Date(p.date_fin);
  const diff  = Math.ceil((fin - today) / 86400000);
  if (diff < 0)  return { label: `En retard de ${Math.abs(diff)}j`, color: 'badge-red' };
  if (diff <= 14) return { label: `${diff}j restants`, color: 'badge-yellow' };
  return { label: `${diff}j restants`, color: 'badge-green' };
};

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
  form.value = { nom: '', description: '', date_debut: todayStr(), date_fin: '', statut: 'ouvert', user_ids: [] };
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
  formError.value = '';

  // Validation frontend
  if (!form.value.nom.trim()) {
    formError.value = 'Le nom du projet est obligatoire.';
    return;
  }
  if (!editing.value && (!form.value.user_ids || form.value.user_ids.length === 0)) {
    formError.value = 'Veuillez sélectionner au moins un membre.';
    return;
  }

  saving.value = true;
  try {
    if (editing.value) {
      await api.put(`/projects/${currentProject.value.id}`, form.value);
      msg('Projet mis à jour');
    } else {
      await api.post('/projects', form.value);
      msg('Projet créé');
    }
    showModal.value = false;
    await fetchProjects();
  } catch (e) {
    const data = e.response?.data;
    const errs = data?.errors;
    const msg_raw = data?.message || 'Erreur.';

    // Messages personnalisés
    if (msg_raw.includes('user_ids') || msg_raw.includes('membre')) {
      formError.value = 'Veuillez sélectionner au moins un membre.';
    } else if (msg_raw.includes('nom') || msg_raw.includes('unique')) {
      formError.value = 'Un projet avec ce nom existe déjà.';
    } else if (errs) {
      formError.value = String(Object.values(errs).flat()[0]);
    } else {
      formError.value = msg_raw;
    }
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
    msg('Membres affectés');
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
    msg('Projet déplacé avec succès');
    await fetchProjects(); // Refresh everything to get updated tickets counts/history
  } catch (err) {
    msg(err.response?.data?.message || 'Erreur lors du déplacement.', false);
  }
  
  dragProject.value = null;
};
</script>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────────── */
/* ── Page Body ──────────────────────────────────────────────────── */
.page-body { flex: 1; padding: 2rem 2.5rem; display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Form Styles ─────────────────────────────────────────────────── */
.member-grid-inline {
  max-height: 200px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: .4rem;
  border: 1px solid #e4eaf3;
  border-radius: 8px;
  padding: .5rem;
  background: #f8fafc;
}
.required-tag { color: #ef4444; font-size: .75rem; margin-left: 2px; }

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
.field :deep(.ds-input) { width: 100%; }
.ta { resize: vertical; min-height: 80px; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.hint-text { font-size: .75rem; color: #eab308; font-weight: 600; margin-top: 4px; }
.modal-footer { display: flex; gap: .75rem; justify-content: flex-end; margin-top: 1rem; }

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

/* Deadline badge */
.deadline-row { margin-bottom: .75rem; }
.deadline-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .7rem; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
}
.badge-green  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.badge-yellow { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.badge-red    { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Field hints */
.field-hint   { font-size: .7rem; color: #94a3b8; margin-top: 3px; }
.optional-tag {
  font-size: .6rem; font-weight: 600; color: #94a3b8;
  background: #f1f5f9; border-radius: 4px;
  padding: 1px 5px; margin-left: 4px; text-transform: uppercase;
}
</style>