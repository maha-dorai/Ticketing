<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-md w-full max-w-md p-8">

      <!-- Icône + Titre -->
      <div class="text-center mb-6">
        <div class="text-5xl mb-3">🔐</div>
        <h1 class="text-2xl font-extrabold text-gray-900">Changement de mot de passe obligatoire</h1>
        <p class="text-gray-500 text-sm mt-2">
          Votre compte a été créé par le super administrateur.<br>
          Vous devez définir un nouveau mot de passe avant de continuer.
        </p>
      </div>

      <!-- Message erreur / succès -->
      <p v-if="message"
        :class="success ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
        class="border px-4 py-3 rounded text-sm mb-4 font-medium">
        {{ message }}
      </p>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="space-y-4">

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            Mot de passe temporaire (actuel)
          </label>
          <input
            v-model="form.ancien"
            type="password"
            placeholder="Entrez votre mot de passe temporaire"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            Nouveau mot de passe
          </label>
          <input
            v-model="form.nouveau"
            type="password"
            placeholder="Au moins 8 caractères, maj, chiffre, spécial"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            Confirmer le nouveau mot de passe
          </label>
          <input
            v-model="form.confirmation"
            type="password"
            placeholder="Répétez le nouveau mot de passe"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-gray-900 text-white py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-700 disabled:opacity-50 transition"
        >
          {{ loading ? 'Enregistrement...' : 'Définir mon mot de passe' }}
        </button>

      </form>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import api from '../services/api';

const router    = useRouter();
const authStore = useAuthStore();

const form    = ref({ ancien: '', nouveau: '', confirmation: '' });
const message = ref('');
const success = ref(false);
const loading = ref(false);

const handleSubmit = async () => {
  message.value = '';

  if (form.value.nouveau !== form.value.confirmation) {
    message.value = 'Les deux mots de passe ne correspondent pas.';
    success.value = false;
    return;
  }

  loading.value = true;
  try {
    await api.put('/users/change-password', {
      ancien_mot_de_passe:  form.value.ancien,
      nouveau_mot_de_passe: form.value.nouveau,
    });

    // Réinitialise le flag dans le store et redirige vers le tableau de bord
    authStore.clearForcePasswordChange();
    message.value = 'Mot de passe mis à jour. Redirection...';
    success.value = true;

    setTimeout(() => {
      router.push({ name: 'UserManagement' });
    }, 1500);

  } catch (err) {
    message.value = err.response?.data?.message || 'Erreur lors du changement de mot de passe.';
    success.value = false;
  } finally {
    loading.value = false;
  }
};
</script>