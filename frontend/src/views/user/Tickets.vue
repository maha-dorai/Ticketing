<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Header & Navigation -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <button @click="$router.push({ name: 'Projects' })" class="text-sm text-blue-600 hover:underline mb-1 flex items-center gap-1">
            ← Retour aux projets
          </button>
          <h1 class="text-2xl font-extrabold text-gray-900">
            📂 {{ projectName || 'Chargement...' }}
          </h1>
          <p class="text-gray-500 text-sm mt-0.5">
            {{ currentUser?.prenom }} {{ currentUser?.nom }} — {{ currentUser?.role }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="$router.push({ name: 'Notifications' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            🔔 Notifications
          </button>
          <button @click="$router.push({ name: 'Profile' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            👤 Mon compte
          </button>
          <button @click="logout" class="px-4 py-2 text-sm text-white bg-gray-600 rounded hover:bg-gray-700 font-semibold transition">
            Déconnexion
          </button>
        </div>
      </div>

      <!-- Actions bar -->
      <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800">Tickets du projet</h2>
        <button v-if="currentUser?.role === 'testeur'" @click="showCreateModal = true" class="px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 font-bold transition shadow-sm">
          + Créer un ticket
        </button>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement des tickets...</div>

      <!-- Aucun ticket -->
      <div v-else-if="tickets.length === 0" class="text-center py-16 bg-white rounded-xl shadow border border-gray-100 text-gray-400">
        <div class="text-4xl mb-3">🎫</div>
        <p class="font-medium text-gray-500">Aucun ticket trouvé pour ce projet.</p>
        <p class="text-sm mt-1">Créez le premier ticket !</p>
      </div>

      <!-- Grille de tickets -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="ticket in tickets" :key="ticket.id"
          @click="$router.push({ name: 'TicketDetails', params: { projectId: projectId, id: ticket.id } })"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-blue-300 transition cursor-pointer flex flex-col justify-between gap-4">
          
          <div>
            <div class="flex items-start justify-between gap-2 mb-2">
              <h3 class="text-base font-bold text-gray-900 leading-tight truncate" :title="ticket.titre">{{ ticket.titre }}</h3>
              <span :class="etatClass(ticket.etat)" class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap uppercase tracking-wider">
                {{ ticket.etat }}
              </span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed line-clamp-2">
              {{ ticket.description || 'Aucune description.' }}
            </p>
          </div>

          <div class="space-y-2 border-t pt-3">
            <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
              <span :class="prioriteClass(ticket.priorite)" class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase">
                {{ ticket.priorite }}
              </span>
              <span class="text-gray-400">{{ formatDate(ticket.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span v-if="ticket.developpeur" class="flex items-center gap-1" title="Développeur assigné">
                <span>👨‍💻</span> <span class="truncate">{{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}</span>
              </span>
              <span v-else class="italic text-gray-400">Non assigné</span>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal Création Ticket -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
          <h3 class="text-lg font-bold text-gray-900">Créer un ticket — {{ projectName }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Titre</label>
            <input v-model="form.titre" required type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 outline-none text-sm" placeholder="Titre du ticket">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 outline-none text-sm resize-none" placeholder="Description détaillée..."></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Priorité</label>
              <select v-model="form.priorite" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 outline-none text-sm bg-white">
                <option value="BASSE">Basse</option>
                <option value="MOYENNE">Moyenne</option>
                <option value="HAUTE">Haute</option>
                <option value="CRITIQUE">Critique</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Développeur (Optionnel)</label>
              <select v-model="form.developpeur_id" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 outline-none text-sm bg-white">
                <option value="">Non assigné</option>
                <option v-for="dev in projectDevs" :key="dev.id" :value="dev.id">{{ dev.prenom }} {{ dev.nom }}</option>
              </select>
            </div>
          </div>
          <div v-if="formError" class="text-sm text-red-600 bg-red-50 p-2 rounded">{{ formError }}</div>
          <div class="pt-4 flex justify-end gap-2 border-t">
            <button @click="closeModal" class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold transition">Annuler</button>
            <button @click="submitTicket" :disabled="submitting" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg font-bold transition shadow-sm">
              {{ submitting ? 'Création...' : 'Créer le ticket' }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const projectId = route.params.projectId;
const currentUser = authStore.currentUser;

const tickets = ref([]);
const projectName = ref('');
const projectDevs = ref([]);
const loading = ref(false);

const showCreateModal = ref(false);
const submitting = ref(false);
const formError = ref('');
const form = ref({
  titre: '',
  description: '',
  priorite: 'BASSE',
  developpeur_id: ''
});

const fetchProjectInfo = async () => {
  try {
    const res = await api.get('/projects');
    const allProjects = res.data.data || res.data;
    const current = allProjects.find(p => p.id == projectId);
    if (current) {
      projectName.value = current.nom;
      projectDevs.value = (current.users || []).filter(u => u.role === 'developpeur');
    }
  } catch (e) {
    console.error('Erreur projet', e);
  }
};

const fetchTickets = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/projects/${projectId}/tickets`);
    tickets.value = res.data;
  } catch (e) {
    console.error('Erreur tickets', e);
  } finally {
    loading.value = false;
  }
};

const submitTicket = async () => {
  if (!form.value.titre) { formError.value = 'Le titre est requis'; return; }
  submitting.value = true;
  formError.value = '';
  try {
    const payload = { ...form.value };
    if (!payload.developpeur_id) delete payload.developpeur_id;
    await api.post(`/projects/${projectId}/tickets`, payload);
    await fetchTickets();
    closeModal();
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la création du ticket';
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  formError.value = '';
  form.value = { titre: '', description: '', priorite: 'BASSE', developpeur_id: '' };
};

const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};

onMounted(() => {
  fetchProjectInfo();
  fetchTickets();
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '';

const etatClass = (etat) => {
  const map = {
    OUVERT: 'bg-green-100 text-green-800',
    EN_COURS: 'bg-yellow-100 text-yellow-800',
    RESOLU: 'bg-blue-100 text-blue-800',
    FERME: 'bg-gray-200 text-gray-700'
  };
  return map[etat] || 'bg-gray-100 text-gray-500';
};

const prioriteClass = (prio) => {
  const map = {
    BASSE: 'bg-gray-100 text-gray-600',
    MOYENNE: 'bg-blue-100 text-blue-600',
    HAUTE: 'bg-orange-100 text-orange-700',
    CRITIQUE: 'bg-red-100 text-red-700'
  };
  return map[prio] || 'bg-gray-100 text-gray-600';
};
</script>