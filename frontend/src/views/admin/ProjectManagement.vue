<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion des projets</h1>
          <p class="page-sub">Créez, modifiez et archivez les projets de la plateforme</p>
        </div>
        <button @click="openCreate" class="btn-new">+ Nouveau projet</button>
      </div>

      <div class="page-content">
        <div v-if="globalMsg" class="alert" :class="globalOk ? 'alert-ok' : 'alert-err'">{{ globalOk ? '✓' : '✕' }} {{ globalMsg }}</div>

        <!-- Search + Filter -->
        <div class="toolbar">
          <div class="search-wrap">
            <svg class="si" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input v-model="search" @input="onSearch" placeholder="Rechercher un projet..." class="search-input" />
          </div>
          <div class="filters">
            <button @click="filter=''" :class="['fb', filter===''?'fb-active':'']">Tous</button>
            <button @click="filter='ouvert'" :class="['fb', filter==='ouvert'?'fb-active':'']">🟢 Ouverts</button>
            <button @click="filter='en_cours'" :class="['fb', filter==='en_cours'?'fb-active':'']">🔵 En cours</button>
            <button @click="filter='archive'" :class="['fb', filter==='archive'?'fb-active':'']">📦 Archivés</button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading"><svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/></svg> Chargement...</div>

        <!-- Empty -->
        <div v-else-if="!filteredProjects.length" class="empty">
          <div class="ei">📁</div>
          <h3 class="et">Aucun projet</h3>
          <p class="es">{{ filter ? 'Aucun projet avec ce statut.' : 'Créez votre premier projet.' }}</p>
        </div>

        <!-- Projects Table -->
        <div v-else class="card">
          <table class="tbl">
            <thead><tr><th>Projet</th><th>Statut</th><th>Membres</th><th>Dates</th><th class="tc">Actions</th></tr></thead>
            <tbody>
              <tr v-for="p in filteredProjects" :key="p.id">
                <td>
                  <p class="pn">{{ p.nom }}</p>
                  <p class="pd">{{ p.description || '—' }}</p>
                </td>
                <td>
                  <span class="st-chip" :class="stClass(p.statut)">{{ stLabel(p.statut) }}</span>
                </td>
                <td>
                  <div class="mavs" v-if="p.users?.length">
                    <div v-for="(m,i) in p.users.slice(0,3)" :key="m.id" class="mav" :style="{zIndex:10-i}" :title="m.prenom+' '+m.nom">{{ (m.prenom[0]||'')+(m.nom[0]||'') }}</div>
                    <div v-if="p.users.length>3" class="mav mmore">+{{p.users.length-3}}</div>
                  </div>
                  <span v-else class="mu">Aucun</span>
                </td>
                <td>
                  <p class="dt">{{ fmt(p.date_debut) }} → {{ fmt(p.date_fin) }}</p>
                </td>
                <td class="tc">
                  <div class="ab">
                    <button @click="openEdit(p)" class="btn-edit">✏</button>
                    <button @click="openAssign(p)" class="btn-assign">👥</button>
                    <button v-if="p.statut !== 'archive'" @click="archiveProject(p.id)" class="btn-archive">📦</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
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
              <option value="archive">📦 Archivé</option>
            </select>
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
const filter = ref('');
const pagination = ref({ current_page: 1, last_page: 1 });

const showModal = ref(false);
const showAssign = ref(false);
const editing = ref(false);
const currentProject = ref(null);
const formError = ref('');
const selectedIds = ref([]);

const form = ref({ nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert' });

let searchTimer = null;

const filteredProjects = computed(() =>
  filter.value ? projects.value.filter(p => p.statut === filter.value) : projects.value
);
const activeMembers = computed(() =>
  allUsers.value.filter(u => u.statut === 'actif' && !['admin','super_admin'].includes(u.role))
);

const fetchProjects = async (page = 1) => {
  loading.value = true;
  try {
    const r = await api.get('/projects', { params: { search: search.value || undefined, page } });
    projects.value = r.data.data || r.data;
    if (r.data.current_page) pagination.value = r.data;
  } catch { msg('Erreur chargement.', false); }
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
const stLabel = s => ({ ouvert: 'Ouvert', en_cours: 'En cours', archive: 'Archivé' }[s] || s);
const stClass = s => ({ ouvert: 'st-open', en_cours: 'st-prog', archive: 'st-arch' }[s] || '');

const openCreate = () => {
  editing.value = false;
  form.value = { nom: '', description: '', date_debut: '', date_fin: '', statut: 'ouvert' };
  formError.value = '';
  showModal.value = true;
};
const openEdit = (p) => {
  editing.value = true;
  currentProject.value = p;
  form.value = { nom: p.nom, description: p.description || '', date_debut: p.date_debut || '', date_fin: p.date_fin || '', statut: p.statut };
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

const archiveProject = async (id) => {
  if (!confirm('Archiver ce projet ?')) return;
  try { await api.delete(`/projects/${id}`); msg('Projet archivé.'); await fetchProjects(); }
  catch { msg('Erreur.', false); }
};

const openAssign = (p) => {
  currentProject.value = p;
  selectedIds.value = (p.users || []).map(u => u.id);
  showAssign.value = true;
};
const saveAssign = async () => {
  assigning.value = true;
  try {
    await api.post(`/projects/${currentProject.value.id}/assign`, { user_ids: selectedIds.value });
    msg('Membres affectés ✓');
    showAssign.value = false;
    await fetchProjects();
  } catch (e) { msg(e.response?.data?.message || 'Erreur.', false); }
  finally { assigning.value = false; }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;}
.page-header{display:flex;align-items:center;justify-content:space-between;padding:2rem 2.5rem 1.5rem;border-bottom:1px solid #e2e8f0;background:white;gap:1rem;flex-wrap:wrap;}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;}
.page-sub{font-size:.875rem;color:#64748b;margin:.25rem 0 0;}
.btn-new{padding:.625rem 1.25rem;background:#1e293b;color:white;border:none;border-radius:9px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;flex-shrink:0;}
.btn-new:hover{background:#0f172a;}
.page-content{padding:1.75rem 2.5rem;display:flex;flex-direction:column;gap:1.25rem;}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;}
.alert-ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.alert-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.toolbar{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.search-wrap{position:relative;}
.si{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;}
.search-input{padding:.5625rem .875rem .5625rem 2.125rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;color:#1e293b;background:white;outline:none;width:220px;font-family:inherit;transition:border-color .2s;}
.search-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
.search-input::placeholder{color:#cbd5e1;}
.filters{display:flex;gap:.375rem;}
.fb{padding:.4375rem .875rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;font-weight:500;color:#64748b;background:white;cursor:pointer;font-family:inherit;transition:all .15s;}
.fb:hover{border-color:#cbd5e1;color:#1e293b;}
.fb-active{background:#1e293b;color:white;border-color:#1e293b;}
.loading{display:flex;align-items:center;gap:.5rem;color:#94a3b8;font-size:.875rem;padding:3rem 0;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
.empty{text-align:center;padding:5rem 2rem;}
.ei{font-size:3.5rem;margin-bottom:1rem;}
.et{font-size:1.125rem;font-weight:700;color:#1e293b;margin:0 0 .5rem;}
.es{font-size:.875rem;color:#94a3b8;margin:0;}
.card{background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.tbl{width:100%;border-collapse:collapse;font-size:.875rem;}
.tbl thead{background:#f8fafc;}
.tbl th{padding:.75rem 1.25rem;text-align:left;font-size:.6875rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #e2e8f0;}
.tbl td{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.tbl tbody tr:last-child td{border-bottom:none;}
.tbl tbody tr:hover td{background:#fafafa;}
.tc{text-align:center;}
.pn{font-size:.9rem;font-weight:700;color:#1e293b;margin:0;}
.pd{font-size:.75rem;color:#94a3b8;margin:.125rem 0 0;max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
.st-chip{font-size:.6875rem;font-weight:700;padding:4px 10px;border-radius:20px;}
.st-open{background:#dcfce7;color:#16a34a;}
.st-prog{background:#dbeafe;color:#1d4ed8;}
.st-arch{background:#f1f5f9;color:#64748b;}
.mavs{display:flex;}
.mav{width:26px;height:26px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:.5625rem;font-weight:800;display:flex;align-items:center;justify-content:center;text-transform:uppercase;border:2px solid white;margin-left:-6px;flex-shrink:0;}
.mav:first-child{margin-left:0;}
.mmore{background:#f1f5f9;color:#64748b;}
.mu{font-size:.8125rem;color:#cbd5e1;}
.dt{font-size:.75rem;color:#64748b;margin:0;}
.ab{display:flex;gap:.375rem;justify-content:center;}
.btn-edit,.btn-assign,.btn-archive{width:30px;height:30px;border-radius:7px;border:1px solid #e2e8f0;background:white;cursor:pointer;font-size:.875rem;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.btn-edit:hover{background:#f0f9ff;border-color:#bae6fd;}
.btn-assign:hover{background:#f0fdf4;border-color:#bbf7d0;}
.btn-archive:hover{background:#fff7ed;border-color:#fed7aa;}
.pagination{display:flex;align-items:center;justify-content:center;gap:1rem;}
.page-btn{padding:.5rem 1rem;background:white;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;font-weight:600;color:#475569;cursor:pointer;font-family:inherit;transition:all .15s;}
.page-btn:hover:not(:disabled){border-color:#3b82f6;color:#3b82f6;}
.page-btn:disabled{opacity:.4;cursor:not-allowed;}
.page-info{font-size:.875rem;color:#64748b;}
/* Modal */
.overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;}
.modal{background:white;border-radius:16px;width:100%;max-width:480px;box-shadow:0 24px 48px rgba(0,0,0,.25);overflow:hidden;}
.modal-wide{max-width:560px;}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;}
.modal-title{font-size:1rem;font-weight:800;color:#0f172a;margin:0;}
.close-btn{background:none;border:none;font-size:1rem;color:#94a3b8;cursor:pointer;padding:4px;border-radius:6px;line-height:1;transition:color .15s;}
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
.modal-footer{display:flex;gap:.75rem;justify-content:flex-end;padding-top:.5rem;border-top:1px solid #f1f5f9;margin-top:.5rem;}
.btn-cancel{padding:.5625rem 1rem;background:white;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;}
.btn-primary{padding:.5625rem 1rem;background:#1e293b;color:white;border:none;border-radius:8px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:.5rem;transition:background .15s;}
.btn-primary:hover:not(:disabled){background:#0f172a;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
/* Assign modal */
.assign-body{padding:1.5rem;}
.assign-hint{font-size:.875rem;color:#64748b;margin:0 0 1.25rem;}
.member-grid{display:flex;flex-direction:column;gap:.5rem;max-height:320px;overflow-y:auto;padding-right:.25rem;margin-bottom:1.25rem;}
.member-check{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:10px;border:1px solid #f1f5f9;cursor:pointer;transition:all .15s;user-select:none;}
.member-check:hover{background:#f8fafc;border-color:#e2e8f0;}
.selected{background:#eff6ff;border-color:#bfdbfe;}
.hidden-cb{display:none;}
.mc-av{width:32px;height:32px;border-radius:8px;background:#dbeafe;color:#1d4ed8;font-size:.6875rem;font-weight:800;display:flex;align-items:center;justify-content:center;text-transform:uppercase;flex-shrink:0;}
.mc-info{flex:1;}
.mc-name{font-size:.875rem;font-weight:600;color:#1e293b;margin:0;}
.mc-role{font-size:.75rem;color:#94a3b8;margin:0;text-transform:capitalize;}
.check-mark{font-size:.875rem;font-weight:700;color:#1d4ed8;width:20px;text-align:center;}
</style>