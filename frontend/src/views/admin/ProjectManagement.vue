<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <div class="bg-white border-b px-8 py-4 flex items-center justify-between shadow-sm">
      <div>
        <h1 class="text-2xl font-extrabold text-gray-900">Console d'Administration</h1>
        <p class="text-gray-500 text-sm mt-0.5">Gestion des projets</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="$router.push({ name: 'UserManagement' })"
          class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold">
          👥 Utilisateurs
        </button>
        <button @click="logout"
          class="px-4 py-2 text-sm text-white bg-gray-600 rounded hover:bg-gray-700 font-semibold">
          Se déconnecter
        </button>
      </div>
    </div>

    <div class="px-8 py-6 space-y-6">

      <!-- Message global -->
      <p v-if="globalMessage"
        :class="globalSuccess ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
        class="border px-4 py-3 rounded text-sm font-medium">
        {{ globalMessage }}
      </p>

      <!-- Bouton + Recherche -->
      <div class="flex items-center justify-between gap-4">
        <input v-model="searchQuery" @input="debouncedSearch" type="text"
          placeholder="Rechercher un projet..."
          class="w-full max-w-sm px-3 py-2 border rounded text-sm bg-white focus:outline-none focus:ring focus:ring-blue-200" />
        <button @click="openCreateModal"
          class="shrink-0 px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 font-semibold">
          + Nouveau projet
        </button>
      </div>

      <!-- ═══════ ONGLETS ═══════ -->
      <div class="flex border-b">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="activeTab === tab.key
            ? 'border-b-2 border-blue-600 text-blue-600 font-bold'
            : 'text-gray-500 hover:text-gray-700 font-semibold'"
          class="px-6 py-3 text-sm flex items-center gap-2 transition">
          <span :class="tab.dot" class="inline-block w-2.5 h-2.5 rounded-full"></span>
          {{ tab.label }}
          <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full ml-1">
            {{ count(tab.key) }}
          </span>
        </button>
      </div>

      <!-- ═══════ TABLEAU ═══════ -->
      <div v-if="loading" class="text-gray-400 text-sm py-6">Chargement...</div>

      <div v-else-if="filteredProjects.length === 0"
        class="text-center py-12 bg-white rounded-xl shadow text-gray-400">
        <div class="text-3xl mb-2">📂</div>
        <p class="text-sm italic">Aucun projet {{ activeTabLabel }} trouvé.</p>
      </div>

      <table v-else class="w-full bg-white shadow rounded text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">Nom</th>
            <th class="px-4 py-3 text-left">Description</th>
            <th class="px-4 py-3 text-left">Début</th>
            <th class="px-4 py-3 text-left">Fin</th>
            <th class="px-4 py-3 text-left">Membres</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="project in filteredProjects" :key="project.id">
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium">{{ project.nom }}</td>
              <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ project.description || '—' }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(project.date_debut) }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(project.date_fin) }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">
                <span v-if="project.users?.length">
                  {{ project.users.map(u => u.prenom + ' ' + u.nom).join(', ') }}
                </span>
                <span v-else class="italic text-gray-400">Aucun membre</span>
              </td>
              <td class="px-4 py-3 text-center space-x-1">
                <!-- Modifier — sauf si fermé -->
                <button v-if="project.statut !== 'ferme'" @click="openEditModal(project)"
                  class="px-2 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600 font-semibold">
                  ✏️ Modifier
                </button>
                <!-- Membres — sauf si fermé -->
                <button v-if="project.statut !== 'ferme'" @click="openAssignModal(project)"
                  class="px-2 py-1 bg-indigo-500 text-white text-xs rounded hover:bg-indigo-600 font-semibold">
                  👥 Membres
                </button>
                <!-- Fermer — seulement si en_cours -->
                <button v-if="project.statut === 'en_cours'" @click="demanderFermeture(project.id)"
                  class="px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 font-semibold">
                  🔒 Fermer
                </button>
                <!-- Badge fermé -->
                <span v-if="project.statut === 'ferme'"
                  class="px-2 py-1 bg-gray-200 text-gray-500 text-xs rounded font-semibold">
                  🔒 Fermé
                </span>
              </td>
            </tr>

            <!-- Confirmation fermeture inline -->
            <tr v-if="confirmFermetureId === project.id" class="bg-red-50 border-t border-red-200">
              <td colspan="6" class="px-4 py-3">
                <div class="flex items-center justify-between">
                  <p class="text-sm text-red-700 font-medium">
                    ⚠️ Confirmer la fermeture de <strong>{{ project.nom }}</strong> ? Action irréversible.
                  </p>
                  <div class="flex gap-2 ml-4 shrink-0">
                    <button @click="confirmFermetureId = null"
                      class="px-3 py-1 text-xs text-gray-600 border rounded hover:bg-gray-100 font-semibold">
                      Annuler
                    </button>
                    <button @click="confirmerFermeture(project.id)"
                      class="px-3 py-1 text-xs text-white bg-red-600 rounded hover:bg-red-700 font-semibold">
                      Oui, fermer
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex justify-center items-center gap-2 mt-2">
        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
          class="px-3 py-1 text-xs border rounded hover:bg-gray-100 disabled:opacity-40">← Préc.</button>
        <span class="text-sm text-gray-500">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-1 text-xs border rounded hover:bg-gray-100 disabled:opacity-40">Suiv. →</button>
      </div>
    </div>

    <!-- ═══════ MODAL CRÉER ═══════ -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-l-4 border-blue-500 pl-3">Nouveau projet</h3>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nom *</label>
            <input v-model="createForm.nom" type="text"
              class="w-full px-3 py-2 border rounded text-sm focus:outline-none focus:ring focus:ring-blue-200" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="createForm.description" rows="3"
              class="w-full px-3 py-2 border rounded text-sm focus:outline-none focus:ring focus:ring-blue-200"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Date de début</label>
              <input v-model="createForm.date_debut" type="date"
                class="w-full px-3 py-2 border rounded text-sm focus:outline-none focus:ring focus:ring-blue-200" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Date de fin</label>
              <input v-model="createForm.date_fin" type="date"
                class="w-full px-3 py-2 border rounded text-sm focus:outline-none focus:ring focus:ring-blue-200" />
            </div>
          </div>
          <p v-if="modalError" class="text-red-600 text-xs bg-red-50 p-2 rounded">{{ modalError }}</p>
        </div>
        <div class="flex justify-end gap-2 mt-5">
          <button @click="closeModals" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 font-semibold">Annuler</button>
          <button @click="createProject" :disabled="modalLoading"
            class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold disabled:opacity-50">
            {{ modalLoading ? 'Création...' : 'Créer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════ MODAL MODIFIER ═══════ -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-l-4 border-yellow-500 pl-3">
          Modifier — {{ editForm.nom }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nom *</label>
            <input v-model="editForm.nom" type="text"
              class="w-full px-3 py-2 border rounded text-sm focus:outline-none focus:ring focus:ring-yellow-200" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Statut *</label>
            <select v-model="editForm.statut"
              class="w-full px-3 py-2 border rounded text-sm focus:outline-none focus:ring focus:ring-yellow-200">
              <option value="en_cours">En cours</option>
              <option value="termine">Terminé</option>
            </select>
          </div>
          <p v-if="modalError" class="text-red-600 text-xs bg-red-50 p-2 rounded">{{ modalError }}</p>
        </div>
        <div class="flex justify-end gap-2 mt-5">
          <button @click="closeModals" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 font-semibold">Annuler</button>
          <button @click="updateProject" :disabled="modalLoading"
            class="px-4 py-2 text-sm bg-yellow-500 text-white rounded hover:bg-yellow-600 font-semibold disabled:opacity-50">
            {{ modalLoading ? 'Mise à jour...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══════ MODAL MEMBRES ═══════ -->
    <div v-if="showAssignModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-l-4 border-indigo-500 pl-3">
          Membres — {{ selectedProject?.nom }}
        </h3>
        <div v-if="usersLoading" class="text-gray-400 text-sm py-4 text-center">Chargement...</div>
        <div v-else>
          <p class="text-xs text-gray-500 mb-3">Sélectionnez les membres à affecter :</p>
          <div class="max-h-64 overflow-y-auto space-y-2 border rounded p-3">
            <label v-for="user in activeUsers" :key="user.id"
              class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
              <input type="checkbox" :value="user.id" v-model="assignedUserIds" class="w-4 h-4 accent-indigo-600" />
              <span class="text-sm">
                <span class="font-medium">{{ user.prenom }} {{ user.nom }}</span>
                <span :class="roleClass(user.role)" class="ml-2 px-1.5 py-0.5 rounded-full text-xs font-semibold">
                  {{ user.role }}
                </span>
              </span>
            </label>
            <p v-if="activeUsers.length === 0" class="text-gray-400 text-xs italic text-center py-2">
              Aucun utilisateur actif disponible.
            </p>
          </div>
          <p class="text-xs text-gray-400 mt-2">{{ assignedUserIds.length }} membre(s) sélectionné(s)</p>
          <p v-if="modalError" class="text-red-600 text-xs bg-red-50 p-2 rounded mt-2">{{ modalError }}</p>
        </div>
        <div class="flex justify-end gap-2 mt-5">
          <button @click="closeModals" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 font-semibold">Annuler</button>
          <button @click="assignUsers" :disabled="modalLoading || usersLoading"
            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 font-semibold disabled:opacity-50">
            {{ modalLoading ? 'Enregistrement...' : 'Confirmer' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const authStore = useAuthStore();
const router    = useRouter();

// ─── Onglets ────────────────────────────────────────────────────────────────
const tabs = [
  { key: 'en_cours', label: 'En cours',  dot: 'bg-blue-500'  },
  { key: 'termine',  label: 'Terminés',  dot: 'bg-green-500' },
  { key: 'ferme',    label: 'Fermés',    dot: 'bg-gray-400'  },
];
const activeTab = ref('en_cours');
const activeTabLabel = computed(() => tabs.find(t => t.key === activeTab.value)?.label ?? '');

// ─── État ────────────────────────────────────────────────────────────────────
const allProjects      = ref([]);
const loading          = ref(false);
const searchQuery      = ref('');
const pagination       = ref({ current_page: 1, last_page: 1, total: 0 });
const globalMessage    = ref('');
const globalSuccess    = ref(true);
const confirmFermetureId = ref(null);

// Modals
const showCreateModal = ref(false);
const showEditModal   = ref(false);
const showAssignModal = ref(false);
const selectedProject = ref(null);
const modalLoading    = ref(false);
const modalError      = ref('');

const createForm = ref({ nom: '', description: '', date_debut: '', date_fin: '' });
const editForm   = ref({ nom: '', statut: 'en_cours' });

const activeUsers     = ref([]);
const assignedUserIds = ref([]);
const usersLoading    = ref(false);

// ─── Computed : projets filtrés par onglet ────────────────────────────────────
const filteredProjects = computed(() =>
  allProjects.value.filter(p => p.statut === activeTab.value)
);

const count = (key) => allProjects.value.filter(p => p.statut === key).length;

// ─── Fetch tous les projets (sans pagination serveur pour les onglets) ────────
const fetchProjects = async () => {
  loading.value = true;
  try {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    // On récupère tout (per_page grand) pour que les onglets affichent les bons compteurs
    params.per_page = 200;
    const res = await api.get('/projects', { params });
    allProjects.value = res.data.data ?? res.data;
  } catch {
    showMessage('Erreur lors du chargement des projets.', false);
  } finally {
    loading.value = false;
  }
};

onMounted(() => fetchProjects());

let searchTimer = null;
const debouncedSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchProjects(), 400);
};

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return;
  fetchProjects();
};

// ─── Helpers ─────────────────────────────────────────────────────────────────
const showMessage = (msg, success = true) => {
  globalMessage.value = msg;
  globalSuccess.value  = success;
  setTimeout(() => { globalMessage.value = ''; }, 4000);
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '—';

const roleClass = (role) => ({
  admin:       'bg-red-100 text-red-700',
  developpeur: 'bg-blue-100 text-blue-700',
  chef_projet: 'bg-purple-100 text-purple-700',
}[role] ?? 'bg-gray-100 text-gray-600');

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value   = false;
  showAssignModal.value = false;
  selectedProject.value = null;
  modalError.value      = '';
  modalLoading.value    = false;
};

// ─── Créer ───────────────────────────────────────────────────────────────────
const openCreateModal = () => {
  createForm.value = { nom: '', description: '', date_debut: '', date_fin: '' };
  modalError.value  = '';
  showCreateModal.value = true;
};

const createProject = async () => {
  if (!createForm.value.nom.trim()) { modalError.value = 'Le nom est obligatoire.'; return; }
  modalLoading.value = true; modalError.value = '';
  try {
    await api.post('/projects', createForm.value);
    closeModals();
    showMessage('Projet créé avec succès.', true);
    activeTab.value = 'en_cours';
    await fetchProjects();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Erreur lors de la création.';
  } finally { modalLoading.value = false; }
};

// ─── Modifier ─────────────────────────────────────────────────────────────────
const openEditModal = (project) => {
  selectedProject.value = project;
  editForm.value = { nom: project.nom, statut: project.statut };
  modalError.value = '';
  showEditModal.value = true;
};

const updateProject = async () => {
  if (!editForm.value.nom.trim()) { modalError.value = 'Le nom est obligatoire.'; return; }
  modalLoading.value = true; modalError.value = '';
  try {
    await api.put(`/projects/${selectedProject.value.id}`, editForm.value);
    closeModals();
    showMessage('Projet mis à jour.', true);
    await fetchProjects();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Erreur lors de la mise à jour.';
  } finally { modalLoading.value = false; }
};

// ─── Fermer ──────────────────────────────────────────────────────────────────
const demanderFermeture  = (id) => { confirmFermetureId.value = id; };

const confirmerFermeture = async (id) => {
  confirmFermetureId.value = null;
  try {
    await api.delete(`/projects/${id}`);
    showMessage('Projet fermé avec succès.', true);
    // Basculer sur l'onglet Fermés
    await fetchProjects();
    activeTab.value = 'ferme';
  } catch (err) {
    showMessage(err.response?.data?.message || 'Erreur lors de la fermeture.', false);
  }
};

// ─── Membres ─────────────────────────────────────────────────────────────────
const openAssignModal = async (project) => {
  selectedProject.value = project;
  assignedUserIds.value = project.users?.map(u => u.id) ?? [];
  modalError.value = '';
  showAssignModal.value = true;
  usersLoading.value = true;
  try {
    const res = await api.get('/users');
    activeUsers.value = res.data.filter(u => u.statut === 'actif' && u.role !== 'admin');
  } catch { modalError.value = 'Impossible de charger les utilisateurs.'; }
  finally { usersLoading.value = false; }
};

const assignUsers = async () => {
  modalLoading.value = true; modalError.value = '';
  try {
    await api.post(`/projects/${selectedProject.value.id}/assign`, { user_ids: assignedUserIds.value });
    closeModals();
    showMessage('Membres affectés avec succès.', true);
    await fetchProjects();
  } catch (err) {
    modalError.value = err.response?.data?.message || "Erreur lors de l'affectation.";
  } finally { modalLoading.value = false; }
};

// ─── Logout ──────────────────────────────────────────────────────────────────
const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};
</script>