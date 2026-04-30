```vue
<template>
  <div class="p-8 min-h-screen bg-gray-50">
    <div class="flex items-center justify-between mb-8 pb-4 border-b">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900">Console d'Administration</h1>
        <p class="text-gray-500 mt-1">Gestion des candidatures et des membres actifs</p>
      </div>
      <button @click="logout" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
        Se déconnecter
      </button>
    </div>

    <!-- ===== EN ATTENTE ===== -->
    <div class="mb-12">
      <h2 class="text-xl font-bold mb-4">Dossiers en Attente</h2>

      <table class="w-full bg-white shadow rounded">
        <tbody>
          <tr v-for="user in pendingUsers" :key="user.id">
            <td>{{ user.nom }} {{ user.prenom }}</td>

            <!-- ✅ FIX github_link -->
            <td>
              <a v-if="user.github_link" :href="user.github_link" target="_blank">
                Voir profil
              </a>
            </td>

            <td>
              <button @click="validerCandidat(user.id)">Valider</button>
              <button @click="refuserCandidat(user.id)">Refuser</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ===== ACTIFS ===== -->
    <div>
      <h2 class="text-xl font-bold mb-4">Équipe Active</h2>

      <table class="w-full bg-white shadow rounded">
        <tbody>
          <tr v-for="user in approvedUsers" :key="user.id">
            <td>{{ user.nom }} {{ user.prenom }}</td>
            <td>{{ user.role }}</td>

            <td>
              <button @click="ouvrirModalEdition(user)">Modifier</button>

              <!-- ✅ FIX bouton -->
              <button @click="desactiverCompte(user.id)">
                Désactiver
              </button>
            </td>
          </tr>
        </tbody>
      </table>
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

const allUsers = ref([]);

const fetchUsers = async () => {
  const res = await api.get('/users');
  allUsers.value = res.data;
};

onMounted(fetchUsers);

const pendingUsers = computed(() =>
  allUsers.value.filter(u => u.statut === 'en_attente')
);

const approvedUsers = computed(() =>
  allUsers.value.filter(u => u.statut === 'actif')
);

const validerCandidat = async (id) => {
  await api.put(`/users/${id}/validate`, { action: 'accepter' });
  fetchUsers();
};

const refuserCandidat = async (id) => {
  await api.put(`/users/${id}/validate`, { action: 'rejeter' });
  fetchUsers();
};

const ouvrirModalEdition = (user) => {
  console.log(user);
};


// ✅ NOUVELLE FONCTION
const desactiverCompte = async (id) => {
  if (confirm("Désactiver ce compte ?")) {
    await api.put(`/users/${id}/disable`);
    await fetchUsers();
  }
};

const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};
</script>
```
