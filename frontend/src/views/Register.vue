<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">

      <!-- Header -->
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Rejoindre l'Équipe
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Inscription soumise à validation
        </p>
      </div>

      <!-- Message succès -->
      <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
        <p class="text-green-700 font-semibold text-sm">✅ {{ successMessage }}</p>
        <p class="text-green-600 text-xs mt-1">Redirection en cours...</p>
      </div>

      <form v-else class="mt-8 space-y-6" @submit.prevent="registerCandidate">

        <div class="rounded-md shadow-sm space-y-4">

          <!-- Nom -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Nom</label>
            <input v-model="form.nom" type="text" required placeholder="Dupont"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200" />
          </div>

          <!-- Prénom -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Prénom</label>
            <input v-model="form.prenom" type="text" required placeholder="Jean"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200" />
          </div>

          <!-- Email -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Email professionnel</label>
            <input v-model="form.email" type="email" required placeholder="jean@dev.com"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200" />
          </div>

          <!-- Mot de passe -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Mot de passe</label>
            <div class="relative">
              <input v-model="form.mot_de_passe" :type="showPassword ? 'text' : 'password'" required
                placeholder="••••••••"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200 pr-10" />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700">
                <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Min. 8 caractères, 1 majuscule, 1 chiffre, 1 caractère spécial</p>
          </div>

          <!-- Rôle -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Choisissez votre rôle</label>
            <select v-model="form.role"
              class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring focus:ring-blue-200">
              <option value="developpeur">Développeur</option>
              <option value="testeur">Testeur (QA)</option>
            </select>
          </div>

          <!-- Github -->
          <div v-if="form.role === 'developpeur'" class="bg-blue-50 p-4 rounded">
            <label class="block mb-1 text-sm font-medium text-blue-800">Lien GitHub / Portfolio</label>
            <input v-model="form.github_link" type="url"
              class="w-full px-3 py-2 border border-blue-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200"
              placeholder="https://github.com/username" />
          </div>

        </div>

        <!-- Erreur -->
        <p v-if="errorMessage" class="text-red-600 text-sm text-center bg-red-50 p-3 rounded border border-red-200">
          {{ errorMessage }}
        </p>

        <!-- Bouton -->
        <button type="submit" :disabled="loading"
          class="w-full py-2 px-4 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 font-semibold">
          {{ loading ? 'Envoi en cours...' : "S'inscrire" }}
        </button>
      </form>

      <!-- Retour login -->
      <div class="text-center mt-4">
        <router-link to="/login" class="text-sm text-gray-500 underline">
          Retour à la connexion
        </router-link>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router    = useRouter();

const errorMessage   = ref('');
const successMessage = ref('');
const loading        = ref(false);
const showPassword   = ref(false);

const form = ref({
  nom: '', prenom: '', email: '', mot_de_passe: '',
  role: 'developpeur', github_link: '',
});

const registerCandidate = async () => {
  errorMessage.value   = '';
  successMessage.value = '';
  loading.value        = true;

  try {
    await authStore.register({
      nom:          form.value.nom,
      prenom:       form.value.prenom,
      email:        form.value.email,
      mot_de_passe: form.value.mot_de_passe,
      role:         form.value.role,
      github_link:  form.value.role === 'developpeur' ? form.value.github_link : null,
    });

    // ✅ Message dans la page — plus d'alert()
    successMessage.value = 'Compte créé avec succès ! Votre demande est en attente de validation.';
    setTimeout(() => router.push({ name: 'Login' }), 2500);

  } catch (err: any) {
    const errors = err.response?.data?.errors;
    if (errors) {
      // Afficher uniquement le premier message d'erreur
      errorMessage.value = String(Object.values(errors).flat()[0]);
    } else {
      errorMessage.value = err.response?.data?.message || 'Une erreur est survenue.';
    }
  } finally {
    loading.value = false;
  }
};
</script>