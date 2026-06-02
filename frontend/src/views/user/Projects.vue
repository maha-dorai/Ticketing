<template>
  <AppLayout>
    <PageHeader variant="default" stacked>
      <template #title>Mes Projets</template>
      <template #subtitle>
        {{ projects.length }} projet{{ projects.length !== 1 ? 's' : '' }} assigné{{ projects.length !== 1 ? 's' : '' }}
      </template>
      <template #toolbar>
        <div class="ds-search">
          <Search class="ds-search-icon" :size="16" aria-hidden="true" />
          <input
            v-model="search"
            type="text"
            placeholder="Rechercher un projet…"
            class="ds-search-input"
            @input="onSearch"
          />
        </div>
      </template>
    </PageHeader>

    <div class="ds-page-body">
      <div v-if="loading" class="ds-loading-state">
        <Loader2 class="spin" :size="20" aria-hidden="true" />
        Chargement des projets…
      </div>

      <div v-else-if="!projects.length" class="ds-empty-state">
        <div class="ds-empty-visual">
          <Folder :size="48" :stroke-width="1.2" aria-hidden="true" />
        </div>
        <h3>Aucun projet trouvé</h3>
        <p>Vous n'avez aucun projet correspondant à votre recherche.</p>
      </div>

      <div v-else class="ds-kanban-board">
        <div
          v-for="col in columns"
          :key="col.id"
          class="ds-kanban-column ds-kanban-column--white"
          :class="`ds-kanban-column--${col.id}`"
        >
          <div class="ds-kanban-column-header">
            <h3 class="ds-kanban-column-title">
              <span class="ds-status-dot" :class="`ds-status-dot--${col.id}`" aria-hidden="true" />
              {{ col.title }}
            </h3>
            <span class="ds-kanban-column-count">{{ getProjectsByStatus(col.id).length }}</span>
          </div>

          <div class="ds-kanban-column-body">
            <div v-if="getProjectsByStatus(col.id).length === 0" class="ds-kanban-column-empty">
              <Plus :size="20" :stroke-width="1.5" aria-hidden="true" />
              <span>Aucun projet</span>
            </div>

            <article
              v-for="(p, idx) in getProjectsByStatus(col.id)"
              :key="p.id"
              class="ds-project-card"
              :class="`ds-project-card--${col.id}`"
              :style="{ animationDelay: `${idx * 60}ms` }"
              @click="$router.push({ name: 'Tickets', params: { projectId: p.id } })"
            >
              <div class="ds-project-card__body">
                <div class="ds-project-card__header">
                  <div class="ds-project-card__icon" :class="`ds-project-card__icon--${col.id}`">
                    <Folder :size="14" :stroke-width="2" aria-hidden="true" />
                  </div>
                  <span class="ds-project-card__id">#{{ p.id }}</span>
                </div>

                <h4 class="ds-project-card__title">{{ p.nom }}</h4>
                <p class="ds-project-card__desc">{{ p.description || 'Aucune description fournie.' }}</p>

                <div
                  v-if="p.statut === 'archive' && (p.date_debut || p.date_fin)"
                  class="ds-project-card__dates"
                >
                  <div class="ds-project-card__dates-row">
                    <Calendar :size="11" :stroke-width="2" aria-hidden="true" />
                    <span>{{ fmt(p.date_debut) }} → {{ fmt(p.date_fin) }}</span>
                  </div>
                </div>

                <div class="ds-project-card__footer">
                  <div v-if="p.users?.length" class="ds-avatar-stack">
                    <div
                      v-for="(m, i) in p.users.slice(0, 4)"
                      :key="m.id"
                      class="ds-avatar"
                      :style="{ zIndex: 10 - i }"
                      :title="`${m.prenom} ${m.nom}`"
                    >
                      {{ (m.prenom[0] || '') + (m.nom[0] || '') }}
                    </div>
                    <div v-if="p.users.length > 4" class="ds-avatar ds-avatar--more">
                      +{{ p.users.length - 4 }}
                    </div>
                  </div>
                  <span v-else class="ds-caption">Sans membres</span>

                  <div class="ds-meta-chip">
                    <Ticket :size="11" :stroke-width="2" aria-hidden="true" />
                    <span>{{ p.tickets_count || 0 }} ticket{{ (p.tickets_count || 0) !== 1 ? 's' : '' }}</span>
                  </div>
                </div>
              </div>

              <ChevronRight class="ds-project-card__arrow" :size="14" :stroke-width="2.5" aria-hidden="true" />
            </article>
          </div>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="ds-pagination">
        <button
          class="ds-page-btn"
          :disabled="pagination.current_page === 1"
          @click="loadPage(pagination.current_page - 1)"
        >
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
          />
        </div>
        <button
          class="ds-page-btn"
          :disabled="pagination.current_page === pagination.last_page"
          @click="loadPage(pagination.current_page + 1)"
        >
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
import { Calendar, ChevronLeft, ChevronRight, Folder, Loader2, Plus, Search, Ticket } from 'lucide-vue-next';
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
      params: { search: search.value || undefined, statut: filter.value || undefined, page },
    });
    projects.value = r.data.data || r.data;
    if (r.data.current_page) pagination.value = r.data;
  } catch {
    /* silencieux */
  } finally {
    loading.value = false;
  }
};

onMounted(() => fetchProjects());

const columns = [
  { id: 'ouvert', title: 'Ouverts' },
  { id: 'en_cours', title: 'En cours' },
  { id: 'archive', title: 'Fermés' },
];

const getProjectsByStatus = (status) => projects.value.filter((p) => p.statut === status);

const onSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchProjects(1), 350);
};

const loadPage = (p) => {
  if (p >= 1 && p <= pagination.value.last_page) fetchProjects(p);
};

const fmt = (d) =>
  d
    ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—';
</script>