```vue
<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <div class="bg-white border-b px-8 py-4 flex items-center justify-between shadow-sm">
      <div>
        <h1 class="text-2xl font-extrabold text-gray-900">Console d'Administration</h1>
        <p class="text-gray-500 text-sm mt-0.5">Gestion des utilisateurs et des accès</p>
      </div>
      <button @click="logout" class="px-4 py-2 text-sm text-white bg-gray-600 rounded hover:bg-gray-700 font-semibold">
        Se déconnecter
      </button>
    </div>

    <div class="px-8 py-6 space-y-10">

      <!-- Message global -->
      <p v-if="globalMessage" :class="globalSuccess ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
        class="border px-4 py-3 rounded text-sm font-medium">
        {{ globalMessage }}
      </p>

      <!-- ═══════════════════ EN ATTENTE ═══════════════════ -->
      <section>
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-block w-3 h-3 rounded-full bg-yellow-400"></span>
          Dossiers en Attente
          <span class="text-sm font-normal text-gray-500">({{ pendingUsers.length }})</span>
        </h2>

        <div v-if="loading" class="text-gray-400 text-sm">Chargement...</div>
        <div v-else-if="pendingUsers.length === 0" class="text-gray-400 text-sm italic">Aucune demande en attente.</div>

        <table v-else class="w-full bg-white shadow rounded text-sm">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Nom complet</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Rôle</th>
              <th class="px-4 py-3 text-left">GitHub</th>
              <th class="px-4 py-3 text-left">Inscription</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in pendingUsers" :key="user.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium">{{ user.nom }} {{ user.prenom }}</td>
              <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
              <td class="px-4 py-3">
                <span :class="user.role === 'developpeur' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                  class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-4 py-3">
                <a v-if="user.github_link" :href="user.github_link" target="_blank"
                  class="text-blue-600 hover:underline text-xs">Voir profil ↗</a>
                <span v-else class="text-gray-400 text-xs">—</span>
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(user.created_at) }}</td>
              <td class="px-4 py-3 text-center space-x-2">
                <button @click="validerCandidat(user.id)"
                  class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 font-semibold">
                  ✓ Valider
                </button>
                <button @click="refuserCandidat(user.id)"
                  class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 font-semibold">
                  ✗ Rejeter
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- ═══════════════════ ACTIFS ═══════════════════ -->
      <section>
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
          Membres Actifs
          <span class="text-sm font-normal text-gray-500">({{ activeUsers.length }})</span>
        </h2>

        <div v-if="activeUsers.length === 0" class="text-gray-400 text-sm italic">Aucun membre actif.</div>

        <table v-else class="w-full bg-white shadow rounded text-sm">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Nom complet</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Rôle</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in activeUsers" :key="user.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium">{{ user.nom }} {{ user.prenom }}</td>
              <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
              <td class="px-4 py-3">
                <span :class="roleClass(user.role)" class="px-2 py-0.5 rounded-full text-xs font-semibold capitalize">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-4 py-3 text-center space-x-2">
                <button @click="ouvrirModalEdition(user)"
                  class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 font-semibold">
                  ✎ Modifier
                </button>
                <button v-if="user.role !== 'admin'" @click="desactiverCompte(user.id)"
                  class="px-3 py-1 bg-orange-500 text-white text-xs rounded hover:bg-orange-600 font-semibold">
                  ⊘ Désactiver
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- ═══════════════════ DÉSACTIVÉS ═══════════════════ -->
      <section>
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-block w-3 h-3 rounded-full bg-gray-400"></span>
          Comptes Désactivés
          <span class="text-sm font-normal text-gray-500">({{ disabledUsers.length }})</span>
        </h2>

        <div v-if="disabledUsers.length === 0" class="text-gray-400 text-sm italic">Aucun compte désactivé.</div>

        <table v-else class="w-full bg-white shadow rounded text-sm">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Nom complet</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Rôle</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in disabledUsers" :key="user.id" class="hover:bg-gray-50 opacity-70">
              <td class="px-4 py-3 font-medium">{{ user.nom }} {{ user.prenom }}</td>
              <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
              <td class="px-4 py-3 capitalize text-gray-500">{{ user.role }}</td>
              <td class="px-4 py-3 text-center">
                <button @click="activerCompte(user.id)"
                  class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 font-semibold">
                  ↺ Réactiver
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- ═══════════════════ REJETÉS ═══════════════════ -->
      <section>
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-block w-3 h-3 rounded-full bg-red-400"></span>
          Comptes Rejetés
          <span class="text-sm font-normal text-gray-500">({{ rejectedUsers.length }})</span>
        </h2>

        <div v-if="rejectedUsers.length === 0" class="text-gray-400 text-sm italic">Aucun compte rejeté.</div>

        <table v-else class="w-full bg-white shadow rounded text-sm">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Nom complet</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Rôle</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in rejectedUsers" :key="user.id" class="hover:bg-gray-50 opacity-60">
              <td class="px-4 py-3 font-medium">{{ user.nom }} {{ user.prenom }}</td>
              <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
              <td class="px-4 py-3 capitalize text-gray-500">{{ user.role }}</td>
            </tr>
          </tbody>
        </table>
      </section>

    </div>

    <!-- ═══════════════════ MODAL ÉDITION ═══════════════════ -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Modifier l'utilisateur</h3>

        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
            <input v-model="editForm.nom" type="text"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
            <input v-model="editForm.prenom" type="text"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input v-model="editForm.email" type="email"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
            <select v-model="editForm.role"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200 bg-white">
              <option value="testeur">Testeur</option>
              <option value="developpeur">Développeur</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>

        <p v-if="modalError" class="mt-3 text-sm text-red-600 bg-red-50 p-2 rounded text-center">{{ modalError }}</p>

        <div class="mt-5 flex gap-3 justify-end">
          <button @click="fermerModal"
            class="px-4 py-2 text-sm text-gray-600 border rounded hover:bg-gray-50 font-semibold">
            Annuler
          </button>
          <button @click="sauvegarderEdition"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 font-semibold">
            Enregistrer
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

// ─── État global ──────────────────────────────────────────────────────────────
const allUsers     = ref([]);
const loading      = ref(false);
const globalMessage = ref('');
const globalSuccess = ref(true);

// ─── Modal édition ────────────────────────────────────────────────────────────
const showModal   = ref(false);
const editUserId  = ref(null);
const modalError  = ref('');
const editForm    = ref({ nom: '', prenom: '', email: '', role: '' });

// ─── Computed : filtres par statut ────────────────────────────────────────────
const pendingUsers  = computed(() => allUsers.value.filter(u => u.statut === 'en_attente'));
const activeUsers   = computed(() => allUsers.value.filter(u => u.statut === 'actif'));
const disabledUsers = computed(() => allUsers.value.filter(u => u.statut === 'desactive'));
const rejectedUsers = computed(() => allUsers.value.filter(u => u.statut === 'rejete'));

// ─── Fetch ────────────────────────────────────────────────────────────────────
const fetchUsers = async () => {
  loading.value = true;
  try {
    const res = await api.get('/users');
    allUsers.value = res.data;
  } catch {
    showMessage('Erreur lors du chargement des utilisateurs.', false);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchUsers);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const showMessage = (msg, success = true) => {
  globalMessage.value = msg;
  globalSuccess.value = success;
  setTimeout(() => { globalMessage.value = ''; }, 4000);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('fr-FR');
};

const roleClass = (role) => {
  if (role === 'admin')      return 'bg-red-100 text-red-700';
  if (role === 'developpeur') return 'bg-blue-100 text-blue-700';
  return 'bg-purple-100 text-purple-700';
};

// ─── Actions ─────────────────────────────────────────────────────────────────
const validerCandidat = async (id) => {
  try {
    await api.put(`/users/${id}/validate`, { action: 'accepter' });
    showMessage('Compte validé. Un email de confirmation a été envoyé.', true);
    await fetchUsers();
  } catch {
    showMessage('Erreur lors de la validation.', false);
  }
};

const refuserCandidat = async (id) => {
  if (!confirm('Rejeter ce candidat ? Un email de notification lui sera envoyé.')) return;
  try {
    await api.put(`/users/${id}/validate`, { action: 'rejeter' });
    showMessage('Compte rejeté. Un email de notification a été envoyé.', true);
    await fetchUsers();
  } catch {
    showMessage('Erreur lors du rejet.', false);
  }
};

const desactiverCompte = async (id) => {
  if (!confirm('Désactiver ce compte ? L\'utilisateur ne pourra plus se connecter.')) return;
  try {
    await api.put(`/users/${id}/disable`);
    showMessage('Compte désactivé avec succès.', true);
    await fetchUsers();
  } catch (err) {
    showMessage(err.response?.data?.message || 'Erreur lors de la désactivation.', false);
  }
};

const activerCompte = async (id) => {
  try {
    await api.put(`/users/${id}/enable`);
    showMessage('Compte réactivé avec succès.', true);
    await fetchUsers();
  } catch {
    showMessage('Erreur lors de la réactivation.', false);
  }
};

// ─── Modal édition ────────────────────────────────────────────────────────────
const ouvrirModalEdition = (user) => {
  editUserId.value = user.id;
  editForm.value   = { nom: user.nom, prenom: user.prenom, email: user.email, role: user.role };
  modalError.value = '';
  showModal.value  = true;
};

const fermerModal = () => {
  showModal.value = false;
};

const sauvegarderEdition = async () => {
  modalError.value = '';
  try {
    await api.put(`/users/${editUserId.value}`, editForm.value);
    showMessage('Utilisateur mis à jour avec succès.', true);
    showModal.value = false;
    await fetchUsers();
  } catch (err) {
    const errors = err.response?.data?.errors;
    modalError.value = errors
      ? Object.values(errors).flat().join(' | ')
      : err.response?.data?.message || 'Erreur lors de la mise à jour.';
  }
};

// ─── Logout ──────────────────────────────────────────────────────────────────
const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};
</script>
```