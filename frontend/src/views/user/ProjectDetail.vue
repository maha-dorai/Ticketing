<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Tableau des projets</h1>
          <p class="page-sub">Gérez le cycle de vie de vos projets via glisser-déposer.</p>
        </div>
        <button @click="openCreate" class="btn-new">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Nouveau projet
        </button>
      </div>

      <div class="page-content">
        <div v-if="globalMsg" class="alert" :class="globalOk ? 'alert-ok' : 'alert-err'">
          {{ globalOk ? '✓' : '✕' }} {{ globalMsg }}
        </div>

        <!-- Search Toolbar -->
        <div class="toolbar">
          <div class="search-wrap">
            <svg class="si" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input v-model="search" @input="onSearch" placeholder="Rechercher par nom..." class="search-input" />
          </div>
        </div>

        <div v-if="loading" class="loading">
          <svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/></svg> 
          Chargement des projets...
        </div>

        <!-- KANBAN BOARD -->
        <div v-else class="kanban-board">
          <div 
            v-for="col in columns" 
            :key="col.id" 
            class="kanban-column"
            @dragover.prevent
            @dragenter.prevent
            @drop="onDrop($event, col.id)"
          >
            <div class="column-header" :class="'ch-' + col.id">
              <h3>{{ col.title }}</h3>
              <span class="col-count">{{ getProjectsByStatus(col.id).length }}</span>
            </div>

            <div class="column-body">
              <div v-if="getProjectsByStatus(col.id).length === 0" class="empty-col">
                <p>Aucun projet</p>
              </div>

              <div
                v-for="p in getProjectsByStatus(col.id)"
                :key="p.id"
                class="kanban-card"
                :class="{ 'dragging': dragProject?.id === p.id }"
                draggable="true"
                @dragstart="onDragStart($event, p)"
                @dragend="onDragEnd"
              >
                <div class="card-strip" :class="'strip-' + col.id"></div>
                <div class="card-inner">
                  <div class="card-top">
                    <span class="status-badge" :class="'sb-' + col.id">{{ col.badge }}</span>
                    <div class="card-actions">
                      <button @click.stop="openEdit(p)" class="btn-icon" title="Modifier">✏</button>
                      <button @click.stop="openAssign(p)" class="btn-icon" title="Affecter membres">👥</button>
                    </div>
                  </div>

                  <h4 class="k-title" @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })" title="Voir les tickets">
                    {{ p.nom }}
                  </h4>

                  <p class="k-desc">{{ p.description || 'Aucune description fournie.' }}</p>

                  <div class="card-dates" v-if="p.date_debut || p.date_fin">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span v-if="p.date_debut">{{ fmt(p.date_debut) }}</span>
                    <span v-if="p.date_debut && p.date_fin"> → </span>
                    <span v-if="p.date_fin">{{ fmt(p.date_fin) }}</span>
                  </div>

                  <div class="card-footer">
                    <div class="mavs" v-if="p.users?.length">
                      <div v-for="(m,i) in p.users.slice(0,4)" :key="m.id" class="mav" :style="{zIndex:10-i}" :title="m.prenom+' '+m.nom">{{ (m.prenom[0]||'')+(m.nom[0]||'') }}</div>
                      <div v-if="p.users.length>4" class="mav mmore">+{{p.users.length-4}}</div>
                    </div>
                    <span v-else class="mu">Aucun membre</span>
                    <span class="k-tickets-badge">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                      {{ p.tickets_count || 0 }} ticket{{ (p.tickets_count||0) !== 1 ? 's' : '' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination (if more than 50 projects) -->
        <div v-if="pagination.last_page > 1" class="pagination">
          <button @click="loadPage(pagination.current_page-1)" :disabled="pagination.current_page===1" class="page-btn">← Précédent</button>
          <span class="page-info">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button @click="loadPage(pagination.current_page+1)" :disabled="pagination.current_page===pagination.last_page" class="page-btn">Suivant →</button>
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
          <!-- Sélection membres (création uniquement) -->
          <div v-if="!editing" class="field">
            <label class="label">Membres du projet * <span class="label-count">({{ form.user_ids.length }} sélectionné(s))</span></label>
            <div class="members-search-wrap">
              <svg class="si" xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
              <input v-model="memberSearch" placeholder="Rechercher un membre..." class="members-search-input" />
            </div>
            <div class="member-grid-inline">
              <div v-if="filteredMembersForCreate.length === 0" class="no-members">Aucun membre actif disponible</div>
              <label
                v-for="u in filteredMembersForCreate" :key="u.id"
                class="member-check" :class="{ selected: form.user_ids.includes(u.id) }"
              >
                <input type="checkbox" :value="u.id" v-model="form.user_ids" class="hidden-cb" />
                <div class="mc-av">{{ (u.prenom[0]||'')+(u.nom[0]||'') }}</div>
                <div class="mc-info">
                  <p class="mc-name">{{ u.prenom }} {{ u.nom }}</p>
                  <p class="mc-role">{{ u.role }}</p>
                </div>
                <span class="check-mark">{{ form.user_ids.includes(u.id) ? '✓' : '' }}</span>
              </label>
            </div>
            <span v-if="formMembersError" class="hint-text">⚠ {{ formMembersError }}</span>
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
const formMembersError = ref('');
const assignError = ref('');
const selectedIds = ref([]);
const memberSearch = ref('');

const dragProject = ref(null);

const form = ref({ nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert', user_ids: [] });

const columns = [
  { id: 'ouvert',   title: '🟢 Ouverts',  badge: 'Ouvert'   },
  { id: 'en_cours', title: '🔵 En cours', badge: 'En cours' },
  { id: 'archive',  title: '📦 Fermés',   badge: 'Fermé'    },
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

const filteredMembersForCreate = computed(() => {
  const q = memberSearch.value.toLowerCase().trim();
  return allUsers.value.filter(u => {
    if (u.statut !== 'actif' || ['chef_de_projet', 'admin'].includes(u.role)) return false;
    if (!q) return true;
    return (u.prenom + ' ' + u.nom + ' ' + u.role).toLowerCase().includes(q);
  });
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
  form.value = { nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert', user_ids: [] };
  formError.value = '';
  formMembersError.value = '';
  memberSearch.value = '';
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
  formMembersError.value = '';
  if (!editing.value && form.value.user_ids.length === 0) {
    formMembersError.value = 'Vous devez sélectionner au moins un membre.';
    return;
  }
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

  // Bloquer Ouvert → En cours : ce passage se fait automatiquement lors du premier ticket
  if (p.statut === 'ouvert' && newStatus === 'en_cours') {
    msg("🔒 Ce passage se fait automatiquement quand le premier ticket est créé.", false);
    dragProject.value = null;
    return;
  }

  try {
    await api.put(`/projects/${p.id}`, {
      nom: p.nom,
      description: p.description,
      date_debut: p.date_debut ? p.date_debut.split('T')[0] : '',
      date_fin: p.date_fin ? p.date_fin.split('T')[0] : '',
      statut: newStatus
    });
    msg('Projet déplacé avec succès ✓');
    await fetchProjects();
  } catch (err) {
    msg(err.response?.data?.message || 'Erreur lors du déplacement.', false);
  }
  
  dragProject.value = null;
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.layout{display:flex;height:100vh;background:#f4f7f9;overflow:hidden;}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.page-header{display:flex;align-items:center;justify-content:space-between;padding:1.5rem 2rem 1.25rem;border-bottom:1px solid #e2e8f0;background:white;gap:1rem;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.02);}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;}
.page-sub{font-size:.875rem;color:#64748b;margin:.25rem 0 0;}
.btn-new{display:flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg, #2563eb, #4f46e5);color:white;border:none;border-radius:10px;font-size:.875rem;font-weight:700;cursor:pointer;transition:transform .2s, box-shadow .2s;}
.btn-new:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.25);}

.page-content{flex:1;padding:1.5rem 2rem;display:flex;flex-direction:column;gap:1.25rem;overflow:hidden;}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:600;}
.alert-ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.alert-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}

.toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-shrink:0;}
.search-wrap{position:relative;}
.si{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;}
.search-input{padding:.5rem .875rem .5rem 2.125rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;color:#1e293b;background:white;outline:none;width:280px;transition:border-color .2s;box-shadow:0 1px 2px rgba(0,0,0,0.02);}
.search-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}

.loading{display:flex;align-items:center;gap:.5rem;color:#64748b;font-weight:600;padding:3rem 0;justify-content:center;}
.spin{animation:spin 1s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}

.kanban-board{display:grid;grid-template-columns:repeat(3, 1fr);gap:1.5rem;flex:1;overflow:hidden;}
.kanban-column{display:flex;flex-direction:column;background:#f1f5f9;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;}
.column-header{padding:.875rem 1rem .75rem;display:flex;align-items:center;justify-content:space-between;background:white;border-bottom:1px solid #e2e8f0;}
.column-header h3{margin:0;font-size:.8125rem;font-weight:700;color:#1e293b;}
.col-count{background:#f1f5f9;color:#94a3b8;font-size:.6875rem;font-weight:700;padding:2px 8px;border-radius:20px;}

.ch-ouvert{border-top:3px solid #22c55e;}
.ch-en_cours{border-top:3px solid #3b82f6;}
.ch-archive{border-top:3px solid #94a3b8;}

.column-body{flex:1;padding:.375rem .625rem .75rem;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;}
.column-body::-webkit-scrollbar{width:4px;}
.column-body::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.empty-col{text-align:center;padding:1.5rem .5rem;color:#cbd5e1;font-size:.75rem;font-style:italic;}

.kanban-card{background:white;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.04);cursor:grab;display:flex;overflow:hidden;transition:all .18s;}
.kanban-card:hover{border-color:#cbd5e1;box-shadow:0 4px 12px rgba(0,0,0,.08);transform:translateY(-1px);}
.kanban-card:active{cursor:grabbing;}
.dragging{opacity:.4;transform:scale(.97);}

.card-strip{width:4px;flex-shrink:0;}
.strip-ouvert{background:#22c55e;}
.strip-en_cours{background:#3b82f6;}
.strip-archive{background:#94a3b8;}

.card-inner{padding:.75rem .875rem;flex:1;min-width:0;display:flex;flex-direction:column;gap:.4rem;}
.card-top{display:flex;align-items:center;justify-content:space-between;}
.status-badge{font-size:.5625rem;font-weight:800;padding:2px 7px;border-radius:4px;text-transform:uppercase;letter-spacing:.05em;}
.sb-ouvert{background:#f0fdf4;color:#16a34a;}
.sb-en_cours{background:#eff6ff;color:#1d4ed8;}
.sb-archive{background:#f8fafc;color:#64748b;}

.card-actions{display:flex;gap:.25rem;}
.btn-icon{background:none;border:none;font-size:.875rem;cursor:pointer;opacity:.35;transition:opacity .15s;padding:2px;}
.btn-icon:hover{opacity:1;}

.k-title{margin:0;font-size:.875rem;font-weight:700;color:#1e293b;cursor:pointer;line-height:1.35;}
.k-title:hover{color:#2563eb;}
.k-desc{font-size:.75rem;color:#94a3b8;margin:0;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.card-dates{display:flex;align-items:center;gap:.3rem;font-size:.6875rem;color:#94a3b8;font-weight:500;}
.card-footer{display:flex;align-items:center;justify-content:space-between;padding-top:.4rem;border-top:1px solid #f8fafc;margin-top:.1rem;}
.mavs{display:flex;}
.mav{width:20px;height:20px;border-radius:5px;background:#dbeafe;color:#1d4ed8;font-size:.5rem;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid white;margin-left:-5px;flex-shrink:0;}
.mav:first-child{margin-left:0;}
.mmore{background:#f1f5f9;color:#64748b;}
.mu{font-size:.6875rem;color:#e2e8f0;font-style:italic;}
.k-tickets-badge{display:inline-flex;align-items:center;gap:.3rem;font-size:.6875rem;font-weight:700;color:#475569;background:#f8fafc;padding:3px 7px;border-radius:6px;border:1px solid #e2e8f0;}

/* Modal CSS */
.overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:100;}
.modal{background:white;border-radius:16px;width:100%;max-width:480px;box-shadow:0 24px 48px rgba(0,0,0,.25);overflow:hidden;}
.modal-wide{max-width:560px;}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;}
.modal-title{font-size:1.1rem;font-weight:800;color:#0f172a;margin:0;}
.close-btn{background:none;border:none;font-size:1.2rem;color:#94a3b8;cursor:pointer;padding:0;}
.close-btn:hover{color:#1e293b;}
.mform{padding:1.5rem;display:flex;flex-direction:column;gap:1rem;}
.field{display:flex;flex-direction:column;gap:.35rem;}
.label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;}
.input{padding:.625rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;font-size:.9rem;outline:none;transition:border-color .2s;}
.input:focus{border-color:#3b82f6;background:white;}
.ta{resize:vertical;min-height:80px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
.hint-text{font-size:0.75rem;color:#eab308;font-weight:600;margin-top:4px;}
.modal-footer{display:flex;gap:.75rem;justify-content:flex-end;margin-top:1rem;}
.btn-cancel{padding:.5rem 1rem;background:white;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;font-weight:600;cursor:pointer;}
.btn-primary{padding:.5rem 1rem;background:#1e293b;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;}
.btn-primary:disabled{opacity:0.6;cursor:not-allowed;}

/* Member selection in create form */
.label-count{font-size:.7rem;color:#3b82f6;font-weight:600;text-transform:none;letter-spacing:0;margin-left:.25rem;}
.members-search-wrap{position:relative;margin-bottom:.5rem;}
.members-search-input{width:100%;padding:.5rem .875rem .5rem 2rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;color:#1e293b;background:#f8fafc;outline:none;transition:border-color .2s;}
.members-search-input:focus{border-color:#3b82f6;background:white;}
.member-grid-inline{max-height:200px;overflow-y:auto;display:flex;flex-direction:column;gap:.4rem;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem;background:#fafafa;}
.no-members{text-align:center;padding:1rem;color:#94a3b8;font-size:.85rem;}
.modal{background:white;border-radius:16px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 48px rgba(0,0,0,.25);}

.assign-body{padding:1.5rem;}
.assign-hint{font-size:.875rem;color:#64748b;margin:0 0 1rem;}
.member-grid{max-height:300px;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem;}
.member-check{display:flex;align-items:center;gap:.75rem;padding:.75rem;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;}
.member-check:hover{background:#f8fafc;}
.member-check.selected{background:#eff6ff;border-color:#bfdbfe;}
.hidden-cb{display:none;}
.mc-av{width:32px;height:32px;background:#dbeafe;color:#1d4ed8;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.75rem;}
.mc-info{flex:1;}
.mc-name{margin:0;font-size:.875rem;font-weight:700;color:#1e293b;}
.mc-role{margin:0;font-size:.75rem;color:#64748b;text-transform:capitalize;}
.check-mark{color:#2563eb;font-weight:800;}
</style>