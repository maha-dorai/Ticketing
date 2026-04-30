```vue
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

      <form class="mt-8 space-y-6" @submit.prevent="registerCandidate">
        
        <div class="rounded-md shadow-sm space-y-4">

          <!-- Nom -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Nom</label>
            <input v-model="form.nom" type="text" required placeholder="Dupont"
              class="w-full px-3 py-2 border border-gray-300 rounded-md" />
          </div>

          <!-- Prénom -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Prénom</label>
            <input v-model="form.prenom" type="text" required placeholder="Jean"
              class="w-full px-3 py-2 border border-gray-300 rounded-md" />
          </div>

          <!-- Email -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Email professionnel</label>
            <input v-model="form.email" type="email" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md"
              placeholder="jean@dev.com" />
          </div>

          <!-- Mot de passe -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Mot de passe</label>
            <input v-model="form.mot_de_passe" type="password" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md"
              placeholder="••••••••" />
          </div>

          <!-- Rôle -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">
              Choisissez votre rôle
            </label>
            <select v-model="form.role"
              class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
              
              <option value="developpeur">Développeur</option>
              <option value="testeur">Testeur (QA)</option>
            </select>
          </div>

          <!-- Github (seulement développeur) -->
          <div v-if="form.role === 'developpeur'" class="bg-blue-50 p-4 rounded">
            <label class="block mb-1 text-sm font-medium text-blue-800">
              Lien GitHub / Portfolio
            </label>
            <input v-model="form.github_link" type="url"
              class="w-full px-3 py-2 border border-blue-300 rounded-md"
              placeholder="https://github.com/username" />
          </div>

        </div>

        <!-- Erreur -->
        <p v-if="errorMessage" class="text-red-600 text-sm text-center">
          {{ errorMessage }}
        </p>

        <!-- Bouton -->
        <button type="submit"
          class="w-full py-2 px-4 bg-green-600 text-white rounded-md hover:bg-green-700">
          S'inscrire
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
const router = useRouter();

const errorMessage = ref('');

const form = ref({
  nom: '',
  prenom: '',
  email: '',
  mot_de_passe: '',
  role: 'developpeur',
  github_link: '',
});

const registerCandidate = async () => {
  try {
    await authStore.register({
      nom:          form.value.nom,
      prenom:       form.value.prenom,
      email:        form.value.email,
      mot_de_passe: form.value.mot_de_passe,
      role:         form.value.role,
      github_link:  form.value.role === 'developpeur'
        ? form.value.github_link
        : null,
    });

    alert("Compte créé ! En attente de validation.");
    router.push({ name: 'Login' });

  } catch (err: any) {
    const errors = err.response?.data?.errors;

    errorMessage.value = errors
      ? Object.values(errors).flat().join(' | ')
      : err.response?.data?.message || 'Erreur';
  }
};
</script>
```
