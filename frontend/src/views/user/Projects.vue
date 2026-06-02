<template>
  <AppLayout>

      <PageHeader variant="hero" stacked>
        <template #title>Mes Projets</template>
        <template #subtitle>{{ projects.length }} projet{{ projects.length !== 1 ? 's' : '' }} assigné{{ projects.length !== 1 ? 's' : '' }}</template>
        <template #toolbar>
          <div class="ds-search">
            <Search class="ds-search-icon" :size="16" aria-hidden="true" />
            <input v-model="search" @input="onSearch" type="text" placeholder="Rechercher un projet..." class="ds-search-input" />
            <kbd v-if="!search" class="ph-search__kbd">⌘K</kbd>
          </div>
        </template>
      </PageHeader>

      <div class="page-body">

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
          <p>Vous n'avez aucun projet correspondant à votre recherche.</p>
        </div>

        <!-- Kanban Board -->
        <div v-else class="ds-kanban-board">
          <div v-for="col in columns" :key="col.id" class="ds-kanban-column ds-kanban-column--white">
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
                :class="`accent--${col.id}`"
                :style="{ animationDelay: `${idx * 60}ms` }"
                @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })"
              >

                <div class="card-body">
                  <!-- Header row -->
                  <div class="card-header">
                    <div class="card-icon" :class="`icon--${col.id}`">
                      <Folder :size="14" :stroke-width="2" aria-hidden="true" />
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
                      <Calendar :size="11" :stroke-width="2" aria-hidden="true" />
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
                      <Ticket :size="11" :stroke-width="2" aria-hidden="true" />
                      <span>{{ p.tickets_count || 0 }} ticket{{ (p.tickets_count || 0) !== 1 ? 's' : '' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Hover arrow -->
                <div class="card-arrow">
                  <ChevronRight :size="14" :stroke-width="2.5" aria-hidden="true" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="ds-pagination">
          <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="ds-page-btn">
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
          <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="ds-page-btn">
            Suivant
            <ChevronRight :size="14" :stroke-width="2.5" aria-hidden="true" />
          </button>
        </div>
      </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { Calendar, ChevronLeft, ChevronRight, Folder, Plus, Search, Ticket } from "lucide-vue-next";
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';

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
/* ───────────────────────────────
   PAGE BODY (clean spacing)
─────────────────────────────── */
.page-body {
  flex: 1;
  padding: 2rem 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ───────────────────────────────
   STRIPE-LEVEL PROJECT CARD
─────────────────────────────── */
.project-card {
  position: relative;
  background: #ffffff;
  border: 1px solid #e8eef6;
  border-radius: 14px;
  cursor: pointer;
  overflow: hidden;

  transition: all 0.25s cubic-bezier(.2,.8,.2,1);
  animation: cardIn 0.35s ease both;

  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.project-card:hover {
  transform: translateY(-3px);
  border-color: rgba(59, 130, 246, 0.25);

  box-shadow:
    0 10px 30px rgba(16, 24, 40, 0.08),
    0 2px 8px rgba(59, 130, 246, 0.08);
}

/* ───────────────────────────────
   ACCENTS (status colors)
─────────────────────────────── */
.project-card.accent--ouvert {
  border-left: 4px solid #10b981;
}

.project-card.accent--en_cours {
  border-left: 4px solid #3b82f6;
}

.project-card.accent--archive {
  border-left: 4px solid #cbd5e1;
}

/* ───────────────────────────────
   CARD BODY
─────────────────────────────── */
.card-body {
  padding: 1rem;
}

/* header */
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .6rem;
}

.card-icon {
  width: 26px;
  height: 26px;
  border-radius: 8px;

  display: flex;
  align-items: center;
  justify-content: center;
}

.icon--ouvert   { background: #ecfdf5; color: #059669; }
.icon--en_cours { background: #eff6ff; color: #2563eb; }
.icon--archive  { background: #f1f5f9; color: #64748b; }

.card-id {
  font-size: .7rem;
  font-weight: 700;
  color: #c0cfe0;
}

/* title */
.card-title {
  margin: 0 0 .35rem;
  font-size: .95rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.project-card:hover .card-title {
  color: #2563eb;
}

/* description */
.card-desc {
  margin: 0 0 .9rem;
  font-size: .82rem;
  color: #667085;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ───────────────────────────────
   DATES (soft UI)
─────────────────────────────── */
.card-dates {
  margin-bottom: .75rem;
  padding: .5rem .625rem;
  background: #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 8px;
}

.date-row {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .7rem;
  color: #64748b;
  font-weight: 500;
}

/* ───────────────────────────────
   FOOTER
─────────────────────────────── */
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: .75rem;
  border-top: 1px solid #f1f5f9;
}

/* avatars */
.avatars {
  display: flex;
}

.avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #dbeafe;
  color: #1d4ed8;

  font-size: .55rem;
  font-weight: 800;

  display: flex;
  align-items: center;
  justify-content: center;

  border: 2px solid #fff;
  margin-left: -5px;
  text-transform: uppercase;
  transition: transform .15s;
}

.avatar:first-child {
  margin-left: 0;
}

.avatar:hover {
  transform: scale(1.15);
  z-index: 5;
}

.avatar-more {
  background: #f1f5f9;
  color: #64748b;
}

/* ticket count */
.ticket-count {
  display: flex;
  align-items: center;
  gap: 4px;

  font-size: .7rem;
  font-weight: 700;
  color: #94a3b8;

  background: #f8fafc;
  border: 1px solid #edf2f7;
  border-radius: 8px;
  padding: 3px 8px;
}

/* arrow */
.card-arrow {
  position: absolute;
  bottom: 1rem;
  right: 1rem;

  color: #c0cfe0;
  opacity: 0;
  transform: translateX(-4px);

  transition: all .18s ease;
}

.project-card:hover .card-arrow {
  opacity: 1;
  transform: translateX(0);
  color: #3b82f6;
}

/* ───────────────────────────────
   EMPTY STATE (ADORABLE)
─────────────────────────────── */
.adorable-empty {
  text-align: center;
  opacity: 0.9;
}

.bounce {
  animation: float 2.5s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

/* ───────────────────────────────
   PAGINATION (modern)
─────────────────────────────── */
.page-btn {
  display: flex;
  align-items: center;
  gap: 5px;

  padding: .5rem 1rem;
  background: #fff;
  border: 1px solid #e6edf5;
  border-radius: 10px;

  font-size: .8125rem;
  font-weight: 600;
  color: #475569;

  cursor: pointer;
  transition: all .2s ease;
}

.page-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #f8fafc;
  border-color: #bfcfe8;
}

.page-btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.page-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #e4eaf3;
  cursor: pointer;
  transition: all .15s;
}

.page-dot:hover {
  background: #bfcfe8;
}

.page-dot.active {
  background: #3b82f6;
  transform: scale(1.2);
}
