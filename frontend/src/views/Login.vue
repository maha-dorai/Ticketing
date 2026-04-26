<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <!-- Conteneur global centré hébergeant le processus de d'Authentification -->
    <div class="w-full max-w-md p-8 bg-white rounded shadow-lg">
      <h2 class="mb-6 text-2xl font-bold text-center text-gray-800">Portail Ticketing</h2>
      
      <form @submit.prevent="onSubmit">
        <!-- Champ Email classique -->
        <div class="mb-4">
          <label class="block mb-2 text-sm font-bold text-gray-700">Email professionnel</label>
          <input v-model="email" type="email" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
        </div>
        
        <!-- Champ Password caché -->
        <div class="mb-6">
          <label class="block mb-2 text-sm font-bold text-gray-700">Mot de passe</label>
          <input v-model="password" type="password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
        </div>
        
        <!-- Affichage dynamique du message d'erreur si la variable errorMessage n'est pas vide (v-if) -->
        <p v-if="errorMessage" class="mb-4 text-sm text-red-600 bg-red-100 p-2 rounded text-center font-medium">{{ errorMessage }}</p>
        
        <!-- Affichage spécifique (v-if / v-else-if) pour le nouveau concept de compte 'en attente' -->
        <p v-else-if="pendingMessage" class="mb-4 text-sm text-yellow-700 bg-yellow-100 p-2 rounded text-center font-medium">{{ pendingMessage }}</p>

        <!-- Bouton de soumission du formulaire vers onSubmit -->
        <button type="submit" class="w-full px-4 py-2 font-bold text-white transition bg-blue-600 rounded hover:bg-blue-700">Se Connecter</button>
      </form>

      <!-- Section "Créer un compte". Lien <router-link> de Vue-Router propulsant l'utilisateur sur la page /register -->
      <div class="mt-6 border-t pt-4 text-center">
        <p class="text-sm text-gray-600">Pas encore de compte ou Compétences à valider ?</p>
        <router-link to="/register" class="mt-2 inline-block font-semibold text-blue-600 hover:text-blue-800 transition">
          S'inscrire et fournir ses preuves
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
// Le classique Vue3 pour manipuler les données
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useRouter } from 'vue-router';

// Les outils d'état globaux 
const router = useRouter();
const authStore = useAuthStore();

// Les variables du formulaire, reliées par v-model. 
// Pré-rempli avec marie@test.com pour que vous fassiez le test "compte en attente" d'entrée de jeu !
const email = ref('marie@test.com');
const password = ref('123');

// Gestionnaires Textuels 
const errorMessage = ref('');
const pendingMessage = ref('');

// Fonction d'interception d'envoi. Remplace l'action native et bloque sur l'Authentification.
const onSubmit = async () => {
  errorMessage.value = '';
  pendingMessage.value = '';

  const status = await authStore.login(email.value, password.value);

  if (status === 'success') {
    if (authStore.currentUser.role === 'admin') {
      router.push({ name: 'UserManagement' });
    } else {
      alert("✅ Connecté en tant que " + authStore.currentUser.role);
    }
  } else if (status === 'en_attente') {
    pendingMessage.value = "Votre compte est en attente de validation.";
  } else if (status === 'rejete') {
    errorMessage.value = "Compte rejeté. Contactez l'administrateur.";
  } else {
    errorMessage.value = "Identifiants incorrects !";
  }
};
</script>
