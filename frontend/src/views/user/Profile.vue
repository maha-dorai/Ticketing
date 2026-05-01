<template>
  <div class="min-h-screen bg-gray-50 py-10 px-4">
    <div class="max-w-lg mx-auto space-y-8">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900">Mon Compte</h1>
          <p class="text-gray-500 text-sm mt-1">
            {{ currentUser?.prenom }} {{ currentUser?.nom }} — {{ currentUser?.role }}
          </p>
        </div>
        <button @click="logout"
          class="px-4 py-2 text-sm text-white bg-gray-500 rounded hover:bg-gray-600 transition font-semibold">
          Déconnexion
        </button>
      </div>

      <!-- Changer le mot de passe -->
      <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-blue-500 pl-3">
          Changer le mot de passe
        </h2>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
            <input v-model="pwForm.ancien" type="password" required
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
            <input v-model="pwForm.nouveau" type="password" required
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200"
              placeholder="Min 8 cars, MAJ, chiffre, symbole" />
          </div>
          <p v-if="pwMessage"
            :class="pwSuccess ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50'"
            class="text-sm p-2 rounded text-center">
            {{ pwMessage }}
          </p>
          <button type="submit"
            class="w-full py-2 font-bold text-white bg-blue-600 rounded hover:bg-blue-700 transition">
            Mettre à jour le mot de passe
          </button>
        </form>
      </div>

      <!-- Changer l'email -->
      <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-purple-500 pl-3">
          Changer l'adresse email
        </h2>
        <form @submit.prevent="changeEmail" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nouvel email</label>
            <input v-model="emailForm.new_email" type="email" required
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-purple-200" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Mot de passe actuel (confirmation)
            </label>
            <input v-model="emailForm.mot_de_passe" type="password" required
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-purple-200" />
          </div>
          <p v-if="emailMessage"
            :class="emailSuccess ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50'"
            class="text-sm p-2 rounded text-center">
            {{ emailMessage }}
          </p>
          <button type="submit"
            class="w-full py-2 font-bold text-white bg-purple-600 rounded hover:bg-purple-700 transition">
            Mettre à jour l'email
          </button>
        </form>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const authStore   = useAuthStore();
const router      = useRouter();
const currentUser = computed(() => authStore.currentUser);

// --- Changer mot de passe ---
const pwForm    = ref({ ancien: '', nouveau: '' });
const pwMessage = ref('');
const pwSuccess = ref(false);

const changePassword = async () => {
  pwMessage.value = '';
  try {
    await api.put('/users/change-password', {
      ancien_mot_de_passe:  pwForm.value.ancien,
      nouveau_mot_de_passe: pwForm.value.nouveau,
    });
    pwSuccess.value = true;
    pwMessage.value = 'Mot de passe modifié avec succès.';
    pwForm.value    = { ancien: '', nouveau: '' };
  } catch (err: any) {
    pwSuccess.value = false;
    pwMessage.value = err.response?.data?.message || 'Une erreur est survenue.';
  }
};

// --- Changer email ---
const emailForm    = ref({ new_email: '', mot_de_passe: '' });
const emailMessage = ref('');
const emailSuccess = ref(false);

const changeEmail = async () => {
  emailMessage.value = '';
  try {
    await api.put('/users/change-email', {
      new_email:    emailForm.value.new_email,
      mot_de_passe: emailForm.value.mot_de_passe,
    });
    // Mettre à jour le store pour refléter le nouvel email immédiatement
    if (authStore.currentUser) {
      authStore.currentUser.email = emailForm.value.new_email;
      localStorage.setItem('user', JSON.stringify(authStore.currentUser));
    }
    emailSuccess.value = true;
    emailMessage.value = 'Adresse email modifiée avec succès.';
    emailForm.value    = { new_email: '', mot_de_passe: '' };
  } catch (err: any) {
    emailSuccess.value = false;
    emailMessage.value = err.response?.data?.message || 'Une erreur est survenue.';
  }
};

// --- Logout ---
const logout = async () => {
  await authStore.logout();
  router.push({ name: 'Login' });
};
</script>