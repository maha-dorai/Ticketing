<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />
      <div class="page-header">
        <div>
          <h1 class="page-title">Mes Projets</h1>
          <p class="page-sub">Projets auxquels vous êtes affecté</p>
        </div>
      </div>

      <div class="page-content">
        <!-- Barre d'outils avec recherche et filtres -->
        <div class="toolbar">
          <div class="search-wrap">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input v-model="search" @input="onSearch" type="text" placeholder="Rechercher un projet..." class="search-input" />
          </div>
        </div>

        <div v-if="loading" class="loading">
          <svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/>
            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/>
          </svg>
          Chargement de vos projets...
        </div>

        <div v-else-if="!projects.length" class="empty">
          <div class="empty-icon">📂</div>
          <h3 class="empty-title">Aucun projet trouvé</h3>
          <p class="empty-sub">Vous n'avez aucun projet correspondant à vos filtres.</p>
        </div>

        <!-- KANBAN BOARD -->
        <div v-else class="kanban-board">
          <div 
            v-for="col in columns" 
            :key="col.id" 
            class="kanban-column"
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
                @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })"
                style="cursor:pointer;"
              >
                <div class="k-card-header">
                  <h4 class="k-title" title="Voir les tickets">
                    {{ p.nom }}
                  </h4>
                </div>
                
                <p class="k-desc">{{ p.description || 'Aucune description' }}</p>
                
                <div class="k-meta">
                  <div class="mavs" v-if="p.users?.length">
                    <div v-for="(m,i) in p.users.slice(0,3)" :key="m.id" class="mav" :style="{zIndex:10-i}" :title="m.prenom+' '+m.nom">{{ (m.prenom[0]||'')+(m.nom[0]||'') }}</div>
                    <div v-if="p.users.length>3" class="mav mmore">+{{p.users.length-3}}</div>
                  </div>
                  <span v-else class="mu">Aucun membre</span>
                  
                  <span class="k-tickets-badge" title="Nombre de tickets">
                    🎫 {{ p.tickets_count || 0 }}
                  </span>
                </div>

                <!-- Archive Summary -->
                <div v-if="p.statut === 'archive'" class="archive-summary">
                  <div class="arc-row"><strong>Début:</strong> {{ fmt(p.date_debut) }}</div>
                  <div class="arc-row"><strong>Fin:</strong> {{ fmt(p.date_fin) }}</div>
                  <div class="arc-row"><strong>Créateur:</strong> {{ p.creator?.prenom }} {{ p.creator?.nom }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination">
          <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="page-btn">← Précédent</button>
          <span class="page-info">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="page-btn">Suivant →</button>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const projects = ref([]);
const loading = ref(false);
const search = ref('');
const filter = ref('');
const pagination = ref({ current_page: 1, last_page: 1 });
let searchTimer = null;

const fetchProjects = async (page = 1) => {
  loading.value = true;
  try {
    const r = await api.get('/projects', { 
      params: { 
        search: search.value || undefined, 
        statut: filter.value || undefined,
        page 
      } 
    });
    projects.value = r.data.data || r.data;
    if (r.data.current_page) pagination.value = r.data;
  } catch { /* silencieux */ }
  finally { loading.value = false; }
};

onMounted(() => fetchProjects());

const setFilter = (val) => {
  filter.value = val;
  fetchProjects(1);
};

const columns = [
  { id: 'ouvert', title: '🟢 Ouverts' },
  { id: 'en_cours', title: '🔵 En cours' },
  { id: 'archive', title: '📦 Fermés' }
];

const getProjectsByStatus = (status) => {
  return projects.value.filter(p => p.statut === status);
};

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchProjects(1), 350);
};

const loadPage = (p) => {
  if (p >= 1 && p <= pagination.value.last_page) fetchProjects(p);
};

const fmt = d => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const statusLabel = s => ({ ouvert: 'Ouvert', en_cours: 'En cours', archive: 'Archivé' }[s] || s);
const statusIcon  = s => ({ ouvert: '🟢', en_cours: '🔵', archive: '📦' }[s] || '⚪');
const statusClass = s => ({ ouvert: 'st-open', en_cours: 'st-inprogress', archive: 'st-archive' }[s] || '');
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
.layout { display: flex; min-height: 100vh; background: #f8fafc; }
.main { flex: 1; overflow-y: auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; padding: 2rem 2.5rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: white; gap: 1rem; flex-wrap: wrap; }
.page-title { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -.02em; }
.page-sub { font-size: .875rem; color: #64748b; margin: .25rem 0 0; }

.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.search-wrap { position: relative; }
.search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.search-input { padding: .625rem .875rem .625rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 9px; font-size: .875rem; color: #1e293b; background: white; outline: none; width: 280px; font-family: inherit; transition: border-color .2s, box-shadow .2s; }
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
.search-input::placeholder { color: #cbd5e1; }

.filters { display: flex; gap: .375rem; }
.fb { padding: .4375rem .875rem; border: 1px solid #e2e8f0; border-radius: 7px; font-size: .8125rem; font-weight: 500; color: #64748b; background: white; cursor: pointer; font-family: inherit; transition: all .15s; }
.fb:hover { border-color: #cbd5e1; color: #1e293b; }
.fb-active { background: #1e293b; color: white; border-color: #1e293b; }

.page-content { padding: 2rem 2.5rem; display: flex; flex-direction: column; gap: 1.5rem; }
.loading { display: flex; align-items: center; justify-content: center; gap: .5rem; color: #94a3b8; font-size: .875rem; padding: 3rem 0; }
.spin { animation: spin .8s linear infinite; } @keyframes spin { to { transform: rotate(360deg); } }
.empty { text-align: center; padding: 5rem 2rem; }
.empty-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.empty-title { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0 0 .5rem; }
.empty-sub { font-size: .875rem; color: #94a3b8; margin: 0; }

.projects-grid { display: none; } /* obsolete */

/* KANBAN CSS */
.kanban-board{display:grid;grid-template-columns:repeat(3, 1fr);gap:1.5rem;flex:1;}
.kanban-column{display:flex;flex-direction:column;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #dfe1e6;}
.column-header{padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;background:white;border-bottom:1px solid #dfe1e6;}
.column-header h3{margin:0;font-size:1rem;font-weight:800;color:#172b4d;}
.col-count{background:#f1f5f9;color:#64748b;font-size:.75rem;font-weight:800;padding:2px 8px;border-radius:12px;}

.ch-ouvert{border-top:4px solid #10b981;}
.ch-en_cours{border-top:4px solid #3b82f6;}
.ch-archive{border-top:4px solid #94a3b8;}

.column-body{flex:1;padding:1rem;display:flex;flex-direction:column;gap:1rem;}
.empty-col{text-align:center;padding:2rem 0;color:#8f9caa;font-size:.875rem;font-weight:600;}

.kanban-card{background:white;border-radius:10px;padding:1.25rem;box-shadow:0 1px 3px rgba(9,30,66,0.15);transition:box-shadow .2s, transform .1s;}
.kanban-card:hover{box-shadow:0 4px 8px rgba(9,30,66,0.15);}

.k-card-header{display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;}
.k-title{margin:0;font-size:1rem;font-weight:700;color:#1e293b;line-height:1.3;}
.k-title:hover{color:#2563eb;text-decoration:underline;}

.k-desc{font-size:.8125rem;color:#64748b;margin:.5rem 0 1rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}

.k-meta{display:flex;justify-content:space-between;align-items:center;}
.mavs{display:flex;}
.mav{width:24px;height:24px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:.5rem;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid white;margin-left:-6px;flex-shrink:0;}
.mav:first-child{margin-left:0;}
.mmore{background:#f1f5f9;color:#64748b;}
.mu{font-size:.75rem;color:#cbd5e1;}

.k-tickets-badge{font-size:.75rem;font-weight:700;background:#f8fafc;color:#475569;padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;}

.archive-summary{margin-top:1rem;padding-top:1rem;border-top:1px dashed #e2e8f0;font-size:.75rem;color:#475569;}
.arc-row{margin-bottom:.25rem;}
.arc-row strong{color:#1e293b;}

.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.page-btn { padding: .5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: .875rem; font-weight: 600; color: #1e293b; cursor: pointer; transition: all .15s; }
.page-btn:hover:not(:disabled) { border-color: #cbd5e1; background: #f8fafc; }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-info { font-size: .875rem; font-weight: 500; color: #64748b; }
</style>