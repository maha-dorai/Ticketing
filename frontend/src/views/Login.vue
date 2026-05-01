<template>
  <!-- Conteneur principal : occupe tout l'écran, fond gris, centré verticalement et horizontalement -->
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">

    <!-- Carte blanche centrale avec ombre qui contient tout le formulaire -->
    <div class="w-full max-w-md p-8 bg-white rounded shadow-lg">

      <!-- Titre de la page -->
      <h2 class="mb-6 text-2xl font-bold text-center text-gray-800">Portail Ticketing</h2>

      <!-- Formulaire : @submit.prevent empêche le rechargement de la page et appelle onSubmit() -->
      <form @submit.prevent="onSubmit">

        <!-- Champ Email -->
        <div class="mb-4">
          <label class="block mb-2 text-sm font-bold text-gray-700">Email professionnel</label>
          <!-- v-model="email" synchronise automatiquement la valeur du champ avec la variable email -->
          <input v-model="email" type="email" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
        </div>

        <!-- Champ Mot de passe -->
        <div class="mb-6">
          <label class="block mb-2 text-sm font-bold text-gray-700">Mot de passe</label>
          <!-- type="password" masque les caractères saisis -->
          <input v-model="password" type="password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-200" />
        </div>

        <!-- Message d'erreur rouge : affiché uniquement si errorMessage n'est pas vide -->
        <p v-if="errorMessage" class="mb-4 text-sm text-red-600 bg-red-100 p-2 rounded text-center font-medium">{{ errorMessage }}</p>

        <!-- Message d'attente jaune : affiché uniquement si le compte est en attente de validation -->
        <p v-else-if="pendingMessage" class="mb-4 text-sm text-yellow-700 bg-yellow-100 p-2 rounded text-center font-medium">{{ pendingMessage }}</p>

        <!-- Bouton de soumission : déclenche @submit.prevent du formulaire parent -->
        <button type="submit" class="w-full px-4 py-2 font-bold text-white transition bg-blue-600 rounded hover:bg-blue-700">Se Connecter</button>
      </form>

      <!-- Lien vers la page d'inscription -->
      <div class="mt-6 border-t pt-4 text-center">
        <p class="text-sm text-gray-600">Pas encore de compte ou Compétences à valider ?</p>
        <!-- router-link est le lien interne de Vue Router — ne recharge pas la page -->
        <router-link to="/register" class="mt-2 inline-block font-semibold text-blue-600 hover:text-blue-800 transition">
          S'inscrire et fournir ses preuves
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
// ref : pour créer des variables réactives dans Vue 3
import { ref } from 'vue';

// On importe le store d'auth pour appeler la fonction login()
import { useAuthStore } from '../stores/authStore';

// useRouter : permet de naviguer entre les pages par code
import { useRouter } from 'vue-router';

// Initialisation du router et du store
const router = useRouter();
const authStore = useAuthStore();

// Variables liées aux champs du formulaire via v-model
// ⚠️ BUG : encore pré-remplies avec l'ancien mock — à vider en production
const email = ref('marie@test.com');
const password = ref('123');

// Variables pour afficher les messages à l'utilisateur
const errorMessage = ref('');   // message rouge (mauvais identifiants ou compte rejeté)
const pendingMessage = ref(''); // message jaune (compte en attente de validation)

// Fonction appelée quand l'utilisateur soumet le formulaire
const onSubmit = async () => {

  // On remet les messages à vide avant chaque tentative
  errorMessage.value = '';
  pendingMessage.value = '';

  // On appelle la fonction login du store — elle contacte l'API et retourne un statut
  const status = await authStore.login(email.value, password.value);

  // Selon le statut retourné par l'API, on agit différemment
  if (status === 'success') {

    // Si l'utilisateur est admin → on le redirige vers le tableau de bord
    // Attention : le backend utilise 'admin' (minuscule)
    if (authStore.currentUser.role === 'admin') {
      router.push({ name: 'UserManagement' });
    } else {
      // Pour les autres rôles (testeur, développeur) → espace utilisateur à créer dans les prochains sprints
      alert("✅ Connecté en tant que " + authStore.currentUser.role);
    }

  } else if (status === 'en_attente') {
    // Le compte existe mais n'a pas encore été validé par l'admin
    pendingMessage.value = "Votre compte est en attente de validation.";

  } else if (status === 'rejete') {
    // L'admin a refusé ce compte
    errorMessage.value = "Compte rejeté. Contactez l'administrateur.";

  } else {
    // Email ou mot de passe incorrect
    errorMessage.value = "Identifiants incorrects !";
  }
};
</script>
