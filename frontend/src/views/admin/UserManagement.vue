<template>
  <div class="p-8 min-h-screen bg-gray-50">
    <div class="flex items-center justify-between mb-8 pb-4 border-b">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900">Console d'Administration</h1>
        <p class="text-gray-500 mt-1">Gestion des candidatures et des membres actifs de la plateforme</p>
      </div>
      <button @click="logout" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600 transition shadow-sm font-semibold">Se déconnecter (Admin)</button>
    </div>

    <!-- ========================================== -->
    <!-- SECTION 1 : DOSSIERS EN ATTENTE            -->
    <!-- ========================================== -->
    <div class="mb-12">
      <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-yellow-500 pl-3">Dossiers en Attente d'Approbation</h2>
      
      <div v-if="pendingUsers.length === 0" class="text-center bg-white p-6 rounded-lg shadow border border-gray-100">
        <p class="text-gray-500 font-medium">Aucun utilisateur en attente de validation. 🎉</p>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identité Candidate</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Preuve Fournie (Link)</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Altération Rôle</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Verdict Admin</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in pendingUsers" :key="user.id" class="hover:bg-blue-50 transition duration-150">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-bold text-gray-900">{{ user.nom + ' ' + user.prenom }}</div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-normal max-w-xs">
                <a v-if="user.link" :href="user.link" target="_blank" class="text-blue-600 hover:text-blue-900 font-medium underline inline-flex items-center gap-1 mb-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                  Dossier Principal
                </a>
                <span v-else class="text-gray-400 text-sm italic block mb-1">Non fourni</span>
                
                <a v-if="user.extraLink" :href="user.extraLink" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs underline block mb-2">
                  + Lien annexe
                </a>
                <p v-if="user.description" class="text-xs text-gray-600 bg-gray-50 p-2 rounded border border-gray-200 mt-2 italic shadow-inner">
                  "{{ user.description }}"
                </p>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <select v-model="user.role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                  <option value="Développeur">Développeur</option>
                  <option value="Testeur">Testeur</option>
                </select>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                <button @click="validerCandidat(user.id)" class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center mr-2 transition">
                  ✓ Approuver
                </button>
                <button @click="refuserCandidat(user.id)" class="text-red-600 hover:text-white border border-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center transition">
                  ✕ Refuser
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>


    <!-- ========================================== -->
    <!-- SECTION 2 : ÉQUIPE ACTIVE (APPORUVÉE)      -->
    <!-- ========================================== -->
    <div>
      <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-green-500 pl-3">Équipe Active</h2>
      
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identité</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rôle Principal</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Panneau d'Administration</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in approvedUsers" :key="user.id" class="hover:bg-gray-50 transition duration-150">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-bold text-gray-900">
                  {{ user.nom + ' ' + user.prenom }}
                  <!-- Badge Administrateur -->
                  <span v-if="user.role === 'admin'" class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Admin</span>
                </div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 font-medium">{{ user.role }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <!-- On masque les boutons modifier/supprimer si l'utilisateur est lui même l'admin pour éviter les drames de permissions -->
                <button v-if="user.role !== 'Administrateur'" @click="ouvrirModalEdition(user)" class="text-blue-600 hover:text-blue-900 mr-4 font-semibold px-2 py-1 bg-blue-50 rounded">Modifier</button>
                <button v-if="user.role !== 'Administrateur'" @click="effacerDuMock(user.id)" class="text-red-600 hover:text-red-900 font-semibold px-2 py-1 bg-red-50 rounded">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>


    <!-- ========================================== -->
    <!-- MODALE D'ÉDITION POUR L'ÉQUIPE ACTIVE      -->
    <!-- ========================================== -->
    <div v-if="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="p-8 bg-white rounded-lg shadow-xl w-full max-w-md">
        <h2 class="mb-4 text-2xl font-bold text-gray-800">Modifier l'Employé</h2>
        
        <form @submit.prevent="sauvegarderUtilisateur">
          <!-- Champ Nom -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-bold text-gray-700">Nom Complet</label>
        <input
  v-model="form.nom"
  type="text"
  required
  placeholder="Nom"
  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
/>

<input
  v-model="form.prenom"
  type="text"
  required
  placeholder="Prénom"
  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
/></div>
          
          <!-- Champ Email -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-bold text-gray-700">Adresse Email</label>
            <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Champ Rôle -->
          <div class="mb-6">
            <label class="block mb-2 text-sm font-bold text-gray-700">Nouveau Rôle</label>
            <select v-model="form.role" class="w-full px-3 py-2 border border-gray-300 bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="Développeur">Développeur</option>
              <option value="Testeur">Testeur</option>
            </select>
          </div>
          
          <!-- Actions de la Modale -->
          <div class="flex justify-end gap-2">
            <button type="button" @click="fermerModal" class="px-4 py-2 font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Annuler</button>
            <button type="submit" class="px-4 py-2 font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition">Enregistrer</button>
          </div>
        </form>
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
const router = useRouter();

// ── Data من API ────────────────────────────────
const allUsers = ref([]);

const fetchUsers = async () => {
  try {
    const res = await api.get('/users');
    allUsers.value = res.data;
  } catch (err) {
    console.error('Erreur chargement users', err);
  }
};

onMounted(fetchUsers);

// ── Filtres ────────────────────────────────────
const pendingUsers = computed(() =>
  allUsers.value.filter(u => u.statut === 'en_attente')
);

const approvedUsers = computed(() =>
  allUsers.value.filter(u => u.statut === 'actif')
);

// ── Validation / Refus ─────────────────────────
const validerCandidat = async (id) => {
  await api.put(`/users/${id}/validate`, { action: 'accepter' });
  await fetchUsers();
};

const refuserCandidat = async (id) => {
  if (confirm("Ce dossier sera supprimé. Êtes vous certain ?")) {
    await api.put(`/users/${id}/validate`, { action: 'rejeter' });
    await fetchUsers();
  }
};

// ── Edition ────────────────────────────────────
const showModal = ref(false);
const form = ref({ id: null, nom: '', prenom: '', email: '', role: '' });

const ouvrirModalEdition = (user) => {
  form.value = {
    id:     user.id,
    nom:    user.nom,
    prenom: user.prenom,
    email:  user.email,
    role:   user.role
  };
  showModal.value = true;
};

const fermerModal = () => {
  showModal.value = false;
};

const sauvegarderUtilisateur = async () => {
  await api.put(`/users/${form.value.id}`, {
    nom:    form.value.nom,
    prenom: form.value.prenom,
    email:  form.value.email,
    role:   form.value.role
  });
  fermerModal();
  await fetchUsers();
};

const effacerDuMock = async (id) => {
  if (confirm("Supprimer ce membre définitivement ?")) {
    await api.delete(`/users/${id}`);
    await fetchUsers();
  }
};

// ── Logout ─────────────────────────────────────
const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};
</script>
