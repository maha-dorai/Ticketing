<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />

      <!-- Page Header -->
      <div class="page-hero">
        <div class="hero-content">
          <div class="hero-text">
            <h1 class="hero-title">Mes Projets</h1>
            <p class="hero-sub">{{ projects.length }} projet{{ projects.length !== 1 ? 's' : '' }} assigné{{ projects.length !== 1 ? 's' : '' }}</p>
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
          <p>Vous n'avez aucun projet correspondant à votre recherche.</p>
        </div>

        <!-- Kanban Board -->
        <div v-else class="kanban-board">
          <div v-for="col in columns" :key="col.id" class="kanban-col">
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
                :class="`accent--${col.id}`"
                :style="{ animationDelay: `${idx * 60}ms` }"
                @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })"
              >

                <div class="card-body">
                  <!-- Header row -->
                  <div class="card-header">
                    <div class="card-icon" :class="`icon--${col.id}`">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                      </svg>
                    </div>
                    <span class="card-id">#{{ p.id }}</span>
                  </div>

                  <!-- Title -->
                  <h4 class="card-title">{{ p.nom }}</h4>

                  <!-- Description -->
                  <p class="card-desc">{{ p.description || 'Aucune description fournie.' }}</p>

                  <!-- Archive dates -->
                  <div v-if="p.statut === 'archive' && (p.date_debut || p.date_fin)" class="card-dates">
                    <div class="date-row">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/></svg>
                      <span>{{ fmt(p.date_debut) }} → {{ fmt(p.date_fin) }}</span>
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

                <!-- Hover arrow -->
                <div class="card-arrow">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';
import AppHeader from '../../components/AppHeader.vue';

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
      params: { search: search.value || undefined, statut: filter.value || undefined, page }
    });
    projects.value = r.data.data || r.data;
    if (r.data.current_page) pagination.value = r.data;
  } catch { /* silencieux */ }
  finally { loading.value = false; }
};

onMounted(() => fetchProjects());

const columns = [
  { id: 'ouvert',   title: 'Ouverts'  },
  { id: 'en_cours', title: 'En cours' },
  { id: 'archive',  title: 'Fermés'   },
];

const getProjectsByStatus = (status) => projects.value.filter(p => p.statut === status);
const countByStatus = (status) => projects.value.filter(p => p.statut === status).length;

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchProjects(1), 350);
};

const loadPage = (p) => {
  if (p >= 1 && p <= pagination.value.last_page) fetchProjects(p);
};

const fmt = d => d
  ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
  : '—';
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap');

*, *::before, *::after { box-sizing: border-box; }

/* ── Layout ─────────────────────────────────────────────────────── */
.layout { display: flex; min-height: 100vh; background: #f0f4f9; font-family: 'Plus Jakarta Sans', sans-serif; }
.main   { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }

/* ── Hero / Page header ─────────────────────────────────────────── */
.page-hero {
  background: #fff;
  border-bottom: 1px solid #e4eaf3;
  padding: 2rem 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.hero-content {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.hero-eyebrow {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: .6875rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-bottom: .5rem;
}
.eyebrow-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #3b82f6;
  animation: blink 2.4s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.35} }

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

/* ── States ─────────────────────────────────────────────────────── */
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
.col-indicator {
  width: 8px; height: 8px; border-radius: 50%;
  flex-shrink: 0;
}
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
  cursor: pointer;
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

/* Left accent line */
.card-accent { display: none; }
.project-card.accent--ouvert   { border-left: 4px solid #10b981; }
.project-card.accent--en_cours { border-left: 4px solid #3b82f6; }
.project-card.accent--archive  { border-left: 4px solid #cbd5e1; }

.card-body { padding: 1rem 1rem 1rem; }

/* Card header row */
.card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .6rem; }
.card-icon {
  width: 26px; height: 26px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
}
.icon--ouvert   { background: #ecfdf5; color: #059669; }
.icon--en_cours { background: #eff6ff; color: #2563eb; }
.icon--archive  { background: #f1f5f9; color: #64748b; }

.card-id { font-size: .7rem; font-weight: 700; color: #c0cfe0; letter-spacing: .04em; }

/* Title */
.card-title {
  margin: 0 0 .4rem;
  font-size: .9375rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.project-card:hover .card-title { color: #2563eb; }

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

/* Arrow hint */
.card-arrow {
  position: absolute;
  bottom: 1rem; right: 1rem;
  color: #c0cfe0;
  opacity: 0;
  transform: translateX(-4px);
  transition: opacity .18s, transform .18s, color .18s;
}
.project-card:hover .card-arrow {
  opacity: 1;
  transform: translateX(0);
  color: #3b82f6;
}

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
</style>