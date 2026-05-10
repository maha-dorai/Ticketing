<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <div class="bg-white border-b px-8 py-4 flex items-center justify-between shadow-sm">
      <div>
        <h1 class="text-2xl font-extrabold text-gray-900">Console Super Administrateur</h1>
        <p class="text-gray-500 text-sm mt-0.5">Gestion des comptes administrateurs</p>
      </div>
      <div class="flex gap-2">
        <button @click="$router.push({ name: 'UserManagement' })"
          class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold">
          👥 Membres
        </button>
        <button @click="$router.push({ name: 'ProjectManagement' })"
          class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold">
          📁 Projets
        </button>
        <button @click="logout"
          class="px-4 py-2 text-sm text-white bg-gray-600 rounded hover:bg-gray-700 font-semibold">
          Se déconnecter
        </button>
      </div>
    </div>

    <div class="px-8 py-6 space-y-10">

      <!-- Message global -->
      <p v-if="globalMessage"
        :class="globalSuccess ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
        class="border px-4 py-3 rounded text-sm font-medium">
        {{ globalMessage }}
      </p>

      <!-- ═══ FORMULAIRE CRÉER UN ADMIN ═══ -->
      <section class="bg-white rounded-xl shadow-sm border p-6 max-w-lg">
        <h2 class="text-lg font-bold text-gray-800 mb-1">➕ Créer un compte administrateur</h2>
        <p class="text-sm text-gray-500 mb-5">
          Renseignez les informations du futur admin. Le système génère automatiquement
          un mot de passe temporaire et l'envoie par email. L'admin sera obligé de le
          changer à sa première connexion.
        </p>

        <form @submit.prevent="createAdmin" class="space-y-4">

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Nom</label>
              <input v-model="newAdmin.nom" type="text" placeholder="Ex : Skhiri"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Prénom</label>
              <input v-model="newAdmin.prenom" type="text" placeholder="Ex : Ahmed"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
                required />
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
              Adresse email professionnelle
            </label>
            <input v-model="newAdmin.email" type="email" placeholder="ahmed.skhiri@company.com"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
              required />
            <p class="text-xs text-gray-400 mt-1">
              📧 Le mot de passe temporaire sera envoyé à cette adresse.
            </p>
          </div>

          <button type="submit" :disabled="creating"
            class="w-full bg-gray-900 text-white py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-700 disabled:opacity-50 transition">
            {{ creating ? 'Création en cours...' : '✉️ Créer et envoyer les accès par email' }}
          </button>

        </form>
      </section>

      <!-- ═══ LISTE DES ADMINS ═══ -->
      <section>
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
          Administrateurs actifs
          <span class="text-sm font-normal text-gray-500">({{ activeAdmins.length }})</span>
        </h2>

        <div v-if="loading" class="text-gray-400 text-sm">Chargement...</div>
        <div v-else-if="activeAdmins.length === 0" class="text-gray-400 text-sm italic">
          Aucun administrateur actif.
        </div>

        <table v-else class="w-full bg-white shadow rounded text-sm">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Nom complet</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Statut accès</th>
              <th class="px-4 py-3 text-left">Créé le</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="admin in activeAdmins" :key="admin.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium">{{ admin.nom }} {{ admin.prenom }}</td>
              <td class="px-4 py-3 text-gray-600">{{ admin.email }}</td>
              <td class="px-4 py-3">
                <span v-if="admin.force_password_change"
                  class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                  ⏳ En attente de 1ère connexion
                </span>
                <span v-else
                  class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                  ✓ Actif
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(admin.created_at) }}</td>
              <td class="px-4 py-3 text-center">
                <button @click="askRevoke(admin)"
                  class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 font-semibold">
                  ⊘ Désactiver
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- ═══ ADMINS DÉSACTIVÉS ═══ -->
      <section v-if="disabledAdmins.length > 0">
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="inline-block w-3 h-3 rounded-full bg-gray-400"></span>
          Administrateurs désactivés
          <span class="text-sm font-normal text-gray-500">({{ disabledAdmins.length }})</span>
        </h2>
        <table class="w-full bg-white shadow rounded text-sm opacity-70">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3 text-left">Nom complet</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Désactivé le</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="admin in disabledAdmins" :key="admin.id">
              <td class="px-4 py-3 font-medium">{{ admin.nom }} {{ admin.prenom }}</td>
              <td class="px-4 py-3 text-gray-600">{{ admin.email }}</td>
              <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(admin.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

    </div>

    <!-- Modal de confirmation désactivation -->
    <div v-if="adminToRevoke"
      class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Confirmer la désactivation</h3>
        <p class="text-sm text-gray-600 mb-5">
          Voulez-vous désactiver le compte admin de
          <strong>{{ adminToRevoke.prenom }} {{ adminToRevoke.nom }}</strong> ?
          Il ne pourra plus se connecter.
        </p>
        <div class="flex gap-3 justify-end">
          <button @click="adminToRevoke = null"
            class="px-4 py-2 text-sm border rounded hover:bg-gray-100 font-semibold">
            Annuler
          </button>
          <button @click="confirmRevoke"
            class="px-4 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700 font-semibold">
            Oui, désactiver
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';

const router    = useRouter();
const authStore = useAuthStore();

// ─── État ─────────────────────────────────────────────────────────────────────
const admins       = ref([]);
const loading      = ref(false);
const creating     = ref(false);
const globalMessage = ref('');
const globalSuccess = ref(true);
const adminToRevoke = ref(null);

const newAdmin = ref({ nom: '', prenom: '', email: '' });

// ─── Computed ─────────────────────────────────────────────────────────────────
const activeAdmins  = computed(() => admins.value.filter(a => a.statut === 'actif'));
const disabledAdmins = computed(() => admins.value.filter(a => a.statut === 'desactive'));

// ─── Chargement ───────────────────────────────────────────────────────────────
const fetchAdmins = async () => {
  loading.value = true;
  try {
    const res  = await api.get('/super-admin/admins');
    admins.value = res.data;
  } catch {
    showMessage('Erreur lors du chargement des admins.', false);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchAdmins);

// ─── Créer un admin ───────────────────────────────────────────────────────────
const createAdmin = async () => {
  creating.value = true;
  try {
    const res = await api.post('/super-admin/admins', newAdmin.value);
    showMessage(res.data.message, true);
    newAdmin.value = { nom: '', prenom: '', email: '' };
    await fetchAdmins();
  } catch (err) {
    showMessage(err.response?.data?.message || 'Erreur lors de la création.', false);
  } finally {
    creating.value = false;
  }
};

// ─── Désactiver un admin ──────────────────────────────────────────────────────
const askRevoke = (admin) => { adminToRevoke.value = admin; };

const confirmRevoke = async () => {
  try {
    await api.put(`/super-admin/admins/${adminToRevoke.value.id}/revoke`);
    showMessage('Compte admin désactivé.', true);
    adminToRevoke.value = null;
    await fetchAdmins();
  } catch (err) {
    showMessage(err.response?.data?.message || 'Erreur.', false);
    adminToRevoke.value = null;
  }
};

// ─── Helpers ──────────────────────────────────────────────────────────────────
const showMessage = (msg, ok = true) => {
  globalMessage.value = msg;
  globalSuccess.value  = ok;
  setTimeout(() => { globalMessage.value = ''; }, 5000);
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '—';

const logout = () => { authStore.logout(); router.push({ name: 'Login' }); };
</script>