<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-5xl mx-auto space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900">Mes Projets</h1>
          <p class="text-gray-500 text-sm mt-0.5">
            {{ currentUser?.prenom }} {{ currentUser?.nom }} — {{ currentUser?.role }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="$router.push({ name: 'Projects' })" class="px-4 py-2 text-sm text-blue-700 bg-blue-100 rounded font-semibold ring-2 ring-blue-500">
            📂 Projets
          </button>
          <button @click="$router.push({ name: 'Tickets' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            🎟️ Tickets
          </button>
          <button @click="$router.push({ name: 'Notifications' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            🔔 Notifications
          </button>
          <button @click="$router.push({ name: 'Profile' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold">
            👤 Mon compte
          </button>
          <button @click="logout" class="px-4 py-2 text-sm text-white bg-gray-600 rounded hover:bg-gray-700 font-semibold">
            Déconnexion
          </button>
        </div>
      </div>

      <!-- Recherche -->
      <div class="flex items-center gap-3">
        <input v-model="searchQuery" @input="debouncedSearch" type="text"
          placeholder="Rechercher un projet..."
          class="w-full max-w-sm px-3 py-2 border rounded text-sm bg-white focus:outline-none focus:ring focus:ring-blue-200" />
        <span class="text-sm text-gray-400">{{ pagination.total ?? 0 }} projet(s)</span>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement...</div>

      <!-- Aucun projet -->
      <div v-else-if="projects.length === 0"
        class="text-center py-16 bg-white rounded-xl shadow text-gray-400">
        <div class="text-4xl mb-3">📂</div>
        <p class="font-medium text-gray-500">Aucun projet trouvé.</p>
        <p class="text-sm mt-1">Vous n'avez pas encore été affecté à un projet.</p>
      </div>

      <!-- Grille de projets -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="project in projects" :key="project.id"
          class="bg-white rounded-xl shadow border border-gray-100 p-5 hover:shadow-md transition flex flex-col justify-between gap-4">

          <!-- Haut : nom + statut -->
          <div>
            <div class="flex items-start justify-between gap-2 mb-2">
              <h3 class="text-base font-bold text-gray-900 leading-tight">{{ project.nom }}</h3>
              <span :class="statutClass(project.statut)"
                class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap">
                {{ statutLabel(project.statut) }}
              </span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed line-clamp-3">
              {{ project.description || 'Aucune description.' }}
            </p>
          </div>

          <!-- Bas : dates + membres -->
          <div class="space-y-2 border-t pt-3">
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <span>📅</span>
              <span>
                <span v-if="project.date_debut">{{ formatDate(project.date_debut) }}</span>
                <span v-else class="italic text-gray-400">Début non défini</span>
                <span class="mx-1">→</span>
                <span v-if="project.date_fin">{{ formatDate(project.date_fin) }}</span>
                <span v-else class="italic text-gray-400">Fin non définie</span>
              </span>
            </div>

            <div class="flex items-start gap-2 text-xs text-gray-500">
              <span>👥</span>
              <span v-if="project.users?.length">
                {{ project.users.map(u => u.prenom + ' ' + u.nom).join(', ') }}
              </span>
              <span v-else class="italic text-gray-400">Aucun membre affecté</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-center items-center gap-3 pt-2">
        <button @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-4 py-2 text-sm border rounded bg-white hover:bg-gray-100 disabled:opacity-40 font-semibold">
          ← Précédent
        </button>
        <span class="text-sm text-gray-500">
          Page {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 text-sm border rounded bg-white hover:bg-gray-100 disabled:opacity-40 font-semibold">
          Suivant →
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const authStore   = useAuthStore();
const router      = useRouter();
const currentUser = authStore.currentUser;

// ─── État ──────────────────────────────────────────────────────────────────────
const projects    = ref([]);
const loading     = ref(false);
const searchQuery = ref('');
const pagination  = ref({ current_page: 1, last_page: 1, total: 0 });

// ─── Fetch ─────────────────────────────────────────────────────────────────────
const fetchProjects = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page };
    if (searchQuery.value) params.search = searchQuery.value;
    const res = await api.get('/projects', { params });
    projects.value   = res.data.data;
    pagination.value = {
      current_page: res.data.current_page,
      last_page:    res.data.last_page,
      total:        res.data.total,
    };
  } catch {
    // Silencieux — pas de toast ici pour ne pas perturber l'UX
  } finally {
    loading.value = false;
  }
};

onMounted(() => fetchProjects());

// ─── Recherche avec délai ──────────────────────────────────────────────────────
let searchTimer = null;
const debouncedSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchProjects(1), 400);
};

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  fetchProjects(page);
};

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '—';

const statutLabel = (s) => ({ ouvert: 'Ouvert', en_cours: 'En cours', ferme: 'Fermé' }[s] ?? s);
const statutClass = (s) => ({
  ouvert:   'bg-green-100 text-green-700',
  en_cours: 'bg-blue-100 text-blue-700',
  ferme:    'bg-gray-200 text-gray-500',
}[s] ?? 'bg-gray-100 text-gray-500');

// ─── Logout ───────────────────────────────────────────────────────────────────
const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};
</script>