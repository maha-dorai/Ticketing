<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 bg-white rounded shadow-lg">
      <h2 class="mb-2 text-2xl font-bold text-center text-gray-800">Mot de passe oublié</h2>
      <p class="mb-6 text-sm text-center text-gray-500">
        Saisissez votre email pour recevoir un lien de réinitialisation.
      </p>

      <form @submit.prevent="onSubmit">
        <div class="mb-4">
          <label class="block mb-2 text-sm font-bold text-gray-700">Adresse email</label>
          <input v-model="email" type="email" required
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
          {{ loading ? 'Envoi en cours...' : 'Envoyer le lien' }}
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
import api from '../services/api';

const email          = ref('');
const loading        = ref(false);
const successMessage = ref('');
const errorMessage   = ref('');

const onSubmit = async () => {
  loading.value        = true;
  successMessage.value = '';
  errorMessage.value   = '';
  try {
    await api.post('/auth/forgot-password', { email: email.value });
    successMessage.value = 'Lien envoyé ! Vérifiez votre boîte mail.';
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Une erreur est survenue.';
  } finally {
    loading.value = false;
  }
};
</script>