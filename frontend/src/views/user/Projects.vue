<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
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
          <div class="filters">
            <button @click="setFilter('')" :class="['fb', filter === '' ? 'fb-active' : '']">Tous</button>
            <button @click="setFilter('ouvert')" :class="['fb', filter === 'ouvert' ? 'fb-active' : '']">🟢 Ouverts</button>
            <button @click="setFilter('en_cours')" :class="['fb', filter === 'en_cours' ? 'fb-active' : '']">🔵 En cours</button>
            <button @click="setFilter('archive')" :class="['fb', filter === 'archive' ? 'fb-active' : '']">📦 Fermés (Archivés)</button>
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

        <div v-else class="projects-grid">
          <div
            v-for="p in projects"
            :key="p.id"
            class="project-card"
            @click="$router.push({ name: 'ProjectDetail', params: { id: p.id } })"
            style="cursor:pointer;"
          >
            <!-- Top -->
            <div class="pc-top">
              <div class="pc-icon">{{ statusIcon(p.statut) }}</div>
              <span class="status-chip" :class="statusClass(p.statut)">{{ statusLabel(p.statut) }}</span>
            </div>

            <!-- Title + desc -->
            <h3 class="pc-name">{{ p.nom }}</h3>
            <p class="pc-desc">{{ p.description || 'Aucune description.' }}</p>

            <!-- Dates -->
            <div class="pc-dates">
              <div class="date-item">
                <span class="date-label">Début</span>
                <span class="date-val">{{ fmt(p.date_debut) }}</span>
              </div>
              <div class="date-sep"></div>
              <div class="date-item">
                <span class="date-label">Fin</span>
                <span class="date-val">{{ fmt(p.date_fin) }}</span>
              </div>
            </div>

            <!-- Members -->
            <div class="pc-members" v-if="p.users?.length">
              <div class="member-avatars">
                <div
                  v-for="(m, i) in p.users.slice(0, 4)"
                  :key="m.id"
                  class="m-av"
                  :style="{ zIndex: p.users.length - i }"
                  :title="m.prenom + ' ' + m.nom"
                >
                  {{ (m.prenom[0] || '') + (m.nom[0] || '') }}
                </div>
                <div v-if="p.users.length > 4" class="m-av m-more">+{{ p.users.length - 4 }}</div>
              </div>
              <span class="member-count">{{ p.users.length }} membre{{ p.users.length > 1 ? 's' : '' }}</span>
            </div>

            <!-- Tickets count hint -->
            <div class="pc-footer">
              <span class="tickets-hint">🎫 Voir les tickets →</span>
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

.projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
.project-card { background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; display: flex; flex-direction: column; gap: .875rem; transition: box-shadow .2s, border-color .2s; }
.project-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); border-color: #3b82f6; }
.pc-top { display: flex; align-items: center; justify-content: space-between; }
.pc-icon { font-size: 1.25rem; }
.status-chip { font-size: .6875rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
.st-open { background: #dcfce7; color: #16a34a; }
.st-inprogress { background: #dbeafe; color: #1d4ed8; }
.st-archive { background: #f1f5f9; color: #64748b; }
.pc-name { font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.3; }
.pc-desc { font-size: .8125rem; color: #64748b; margin: 0; line-height: 1.5; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pc-dates { display: flex; align-items: center; gap: .5rem; background: #f8fafc; border-radius: 8px; padding: .625rem .875rem; }
.date-item { flex: 1; }
.date-label { display: block; font-size: .625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
.date-val { font-size: .8125rem; font-weight: 600; color: #475569; }
.date-sep { width: 1px; height: 24px; background: #e2e8f0; }
.pc-members { display: flex; align-items: center; gap: .625rem; }
.member-avatars { display: flex; }
.m-av { width: 26px; height: 26px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-size: .5625rem; font-weight: 800; display: flex; align-items: center; justify-content: center; text-transform: uppercase; border: 2px solid white; margin-left: -6px; flex-shrink: 0; }
.m-av:first-child { margin-left: 0; }
.m-more { background: #f1f5f9; color: #64748b; }
.member-count { font-size: .75rem; color: #94a3b8; }
.pc-footer { border-top: 1px solid #f1f5f9; padding-top: .5rem; }
.tickets-hint { font-size: .75rem; color: #3b82f6; font-weight: 600; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; }
.page-btn { padding: .5rem 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; font-size: .875rem; font-weight: 600; color: #475569; cursor: pointer; font-family: inherit; transition: all .15s; }
.page-btn:hover:not(:disabled) { border-color: #3b82f6; color: #3b82f6; }
.page-btn:disabled { opacity: .4; cursor: not-allowed; }
.page-info { font-size: .875rem; color: #64748b; }
</style>