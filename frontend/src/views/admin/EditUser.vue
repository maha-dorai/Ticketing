<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <div class="bg-white border-b px-8 py-4 flex items-center gap-4 shadow-sm">
      <button @click="$router.push({ name: 'UserManagement' })"
        class="text-gray-500 hover:text-gray-800 font-semibold flex items-center gap-1">
        ← Retour
      </button>
      <div>
        <h1 class="text-xl font-extrabold text-gray-900">Modifier l'utilisateur</h1>
        <p class="text-gray-500 text-sm">{{ form.prenom }} {{ form.nom }}</p>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="max-w-lg mx-auto mt-10 bg-white rounded-xl shadow p-8">

      <div v-if="loading" class="text-gray-400 text-center py-10">Chargement...</div>

      <div v-else>
        <div class="space-y-5">

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom</label>
            <input v-model="form.nom" type="text"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Prénom</label>
            <input v-model="form.prenom" type="text"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
            <input v-model="form.email" type="email"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Rôle</label>
            <select v-model="form.role"
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200 bg-white">
              <option value="testeur">Testeur</option>
              <option value="developpeur">Développeur</option>
              <option value="admin">Admin</option>
            </select>
          </div>

        </div>

        <!-- Message erreur / succès -->
        <p v-if="errorMsg" class="mt-4 text-sm text-red-600 bg-red-50 p-3 rounded text-center">
          {{ errorMsg }}
        </p>
        <p v-if="successMsg" class="mt-4 text-sm text-green-700 bg-green-50 p-3 rounded text-center">
          {{ successMsg }}
        </p>

        <!-- Boutons -->
        <div class="mt-6 flex gap-3">
          <button @click="$router.push({ name: 'UserManagement' })"
            class="flex-1 px-4 py-2 text-sm text-gray-600 border rounded hover:bg-gray-50 font-semibold">
            Annuler
          </button>
          <button @click="sauvegarder"
            class="flex-1 px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 font-semibold">
            Enregistrer
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';

const route  = useRoute();
const router = useRouter();

const loading    = ref(true);
const errorMsg   = ref('');
const successMsg = ref('');

const form = ref({ nom: '', prenom: '', email: '', role: '' });

// Charger les données de l'utilisateur
onMounted(async () => {
  try {
    const res = await api.get('/users');
    const user = res.data.find(u => u.id == route.params.id);
    if (!user) { errorMsg.value = 'Utilisateur introuvable.'; return; }
    form.value = { nom: user.nom, prenom: user.prenom, email: user.email, role: user.role };
  } catch {
    errorMsg.value = 'Erreur lors du chargement.';
  } finally {
    loading.value = false;
  }
});

const sauvegarder = async () => {
  errorMsg.value   = '';
  successMsg.value = '';
  try {
    await api.put(`/users/${route.params.id}`, form.value);
    successMsg.value = 'Modifications enregistrées avec succès.';
    setTimeout(() => router.push({ name: 'UserManagement' }), 1200);
  } catch (err) {
    const errors = err.response?.data?.errors;
    errorMsg.value = errors
      ? Object.values(errors).flat().join(' | ')
      : err.response?.data?.message || 'Erreur lors de la mise à jour.';
  }
};
</script>