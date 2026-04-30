<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 bg-white rounded shadow-lg">
      <h2 class="mb-2 text-2xl font-bold text-center text-gray-800">Nouveau mot de passe</h2>
      <p class="mb-6 text-sm text-center text-gray-500">
        Saisissez et confirmez votre nouveau mot de passe.
      </p>

      <form @submit.prevent="onSubmit">
        <div class="mb-4">
          <label class="block mb-2 text-sm font-bold text-gray-700">Nouveau mot de passe</label>
          <input v-model="mot_de_passe" type="password" required
            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200"
            placeholder="Min 8 cars, MAJ, chiffre, symbole" />
        </div>

        <div class="mb-6">
          <label class="block mb-2 text-sm font-bold text-gray-700">Confirmer le mot de passe</label>
          <input v-model="confirmation" type="password" required
            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
        </div>

        <p v-if="successMessage" class="mb-4 text-sm text-green-700 bg-green-100 p-2 rounded text-center">
          {{ successMessage }}
        </p>
        <p v-if="errorMessage" class="mb-4 text-sm text-red-600 bg-red-100 p-2 rounded text-center">
          {{ errorMessage }}
        </p>

        <button type="submit" :disabled="loading"
          class="w-full px-4 py-2 font-bold text-white bg-blue-600 rounded hover:bg-blue-700 transition disabled:opacity-50">
          {{ loading ? 'Enregistrement...' : 'Réinitialiser' }}
        </button>
      </form>

      <div class="mt-4 text-center">
        <router-link to="/login" class="text-sm text-gray-500 hover:underline">
          Retour à la connexion
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const route  = useRoute();
const router = useRouter();

const mot_de_passe   = ref('');
const confirmation   = ref('');
const loading        = ref(false);
const successMessage = ref('');
const errorMessage   = ref('');

const onSubmit = async () => {
  errorMessage.value   = '';
  successMessage.value = '';

  if (mot_de_passe.value !== confirmation.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas.';
    return;
  }

  loading.value = true;
  try {
    const token = route.params.token as string;
    await api.post(`/auth/reset-password/${token}`, {
      mot_de_passe: mot_de_passe.value
    });
    successMessage.value = 'Mot de passe mis à jour ! Redirection...';
    setTimeout(() => router.push({ name: 'Login' }), 2000);
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Lien invalide ou expiré.';
  } finally {
    loading.value = false;
  }
};
</script>