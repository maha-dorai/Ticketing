```vue
<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 bg-white rounded shadow-lg">
      <h2 class="mb-6 text-2xl font-bold text-center text-gray-800">
        Portail Ticketing
      </h2>

      <form @submit.prevent="onSubmit">
        <!-- Email -->
        <div class="mb-4">
          <label class="block mb-2 text-sm font-bold text-gray-700">
            Email professionnel
          </label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200"
          />
        </div>

        <!-- Mot de passe -->
        <div class="mb-2">
          <label class="block mb-2 text-sm font-bold text-gray-700">
            Mot de passe
          </label>
          <div class="relative">
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200 pr-10"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700"
            >
              <!-- Oeil ouvert -->
              <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <!-- Oeil barré -->
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Lien mot de passe oublié -->
        <div class="mb-6 text-right">
          <router-link
            to="/forgot-password"
            class="text-sm text-blue-600 hover:underline"
          >
            Mot de passe oublié ?
          </router-link>
        </div>

        <!-- Messages -->
        <p
          v-if="errorMessage"
          class="mb-4 text-sm text-red-600 bg-red-100 p-2 rounded text-center font-medium"
        >
          {{ errorMessage }}
        </p>

        <p
          v-else-if="pendingMessage"
          class="mb-4 text-sm text-yellow-700 bg-yellow-100 p-2 rounded text-center font-medium"
        >
          {{ pendingMessage }}
        </p>

        <!-- Bouton -->
        <button
          type="submit"
          class="w-full px-4 py-2 font-bold text-white transition bg-blue-600 rounded hover:bg-blue-700"
        >
          Se Connecter
        </button>
      </form>

      <!-- Inscription -->
      <div class="mt-6 border-t pt-4 text-center">
        <p class="text-sm text-gray-600">
          Pas encore de compte ou Compétences à valider ?
        </p>
        <router-link
          to="/register"
          class="mt-2 inline-block font-semibold text-blue-600 hover:text-blue-800 transition"
        >
          S'inscrire et fournir ses preuves
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const showPassword = ref(false);

const errorMessage = ref('');
const pendingMessage = ref('');

const onSubmit = async () => {
  errorMessage.value = '';
  pendingMessage.value = '';

  const status = await authStore.login(email.value, password.value);

  if (status === 'success') {
    if (authStore.currentUser?.role === 'admin') {
      router.push({ name: 'UserManagement' });
    } else {
      router.push({ name: 'Profile' });
    }
  } else if (status === 'en_attente') {
    pendingMessage.value = "Votre compte est en attente de validation.";
  } else if (status === 'rejete') {
    errorMessage.value = "Compte rejeté. Contactez l'administrateur.";
  } else if (status === 'desactive') {
    errorMessage.value = "Votre compte a été désactivé.";
  } else {
    errorMessage.value = "Email ou mot de passe incorrect.";
  }
};
</script>
```