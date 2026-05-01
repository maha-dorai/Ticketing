<template>
  <!-- Conteneur principal : occupe tout l'écran, fond gris clair, centré -->
  <div class="flex items-center justify-center min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">

    <!-- Carte blanche avec ombre qui contient tout le formulaire d'inscription -->
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">

      <!-- En-tête de la page -->
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Rejoindre l'Équipe</h2>
        <p class="mt-2 text-center text-sm text-gray-600">Inscription soumise à validation</p>
      </div>

      <!-- Formulaire d'inscription : @submit.prevent empêche le rechargement et appelle registerCandidate() -->
      <form class="mt-8 space-y-6" @submit.prevent="registerCandidate">

        <div class="rounded-md shadow-sm space-y-4">

          <!-- Champ Nom complet : v-model lie le champ à form.name -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Nom complet</label>
            <input v-model="form.name" type="text" required placeholder="John Doe"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>

          <!-- Champ Email : v-model lie le champ à form.email -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Email professionnel</label>
            <input v-model="form.email" type="email" required placeholder="jean@dev.com"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>

          <!-- Champ Mot de passe : type="password" masque les caractères -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Mot de passe</label>
            <input v-model="form.password" type="password" required placeholder="••••••••"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
          </div>

          <!-- Sélecteur de rôle : quand l'utilisateur change le rôle, form.role se met à jour automatiquement -->
          <!-- Cela déclenche aussi l'affichage conditionnel du bloc GitHub ci-dessous -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Postulez pour un de nos rôles</label>
            <select v-model="form.role"
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
              <option value="Développeur">Je suis un Développeur</option>
              <option value="Testeur">Je suis un Testeur (QA)</option>
            </select>
          </div>

          <!-- Bloc GitHub : affiché UNIQUEMENT si le rôle choisi est "Développeur" -->
          <!-- v-if surveille form.role en temps réel grâce à la réactivité de Vue -->
          <div v-if="form.role === 'Développeur'" class="bg-blue-50 p-4 rounded-lg mt-4 border border-blue-100 transition-all space-y-4">

            <!-- Lien GitHub principal : obligatoire pour les développeurs -->
            <div>
              <label class="block mb-1 text-sm font-bold text-blue-800">🔗 Preuve Obligatoire (Github, Portfolio)</label>
              <!-- type="url" vérifie que l'utilisateur entre bien une URL valide -->
              <input v-model="form.link" type="url" required placeholder="https://github.com/mon-username"
                class="block w-full px-3 py-2 border border-blue-300 bg-white rounded-md text-gray-900 sm:text-sm focus:border-blue-500 focus:outline-none">
              <p class="mt-1 text-xs text-blue-600">Obligatoire pour l'accréditation développeur.</p>
            </div>

            <!-- Lien annexe facultatif (portfolio, CodePen, certificat...) -->
            <div>
              <label class="block mb-1 text-sm font-bold text-blue-800">📎 Autre lien (Facultatif)</label>
              <input v-model="form.extraLink" type="url" placeholder="https://lien-vers-certificat-ou-codepen.com"
                class="block w-full px-3 py-2 border border-blue-300 bg-white rounded-md text-gray-900 sm:text-sm focus:border-blue-500 focus:outline-none">
            </div>

            <!-- Zone de texte pour décrire ses compétences ou motivations -->
            <div>
              <label class="block mb-1 text-sm font-bold text-blue-800">📝 Présentation / Motivations</label>
              <textarea v-model="form.description" rows="3" placeholder="Décrivez vos compétences techniques ou vos projets..."
                class="block w-full px-3 py-2 border border-blue-300 bg-white rounded-md text-gray-900 sm:text-sm focus:border-blue-500 focus:outline-none"></textarea>
            </div>
          </div>

        </div>

        <!-- Bouton de soumission du formulaire -->
        <div>
          <button type="submit"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
            Soumettre le Dossier
          </button>
        </div>
      </form>

      <!-- Lien de retour vers la page de connexion -->
      <div class="text-center mt-4">
        <router-link to="/login" class="text-sm text-gray-500 hover:text-gray-900 transition underline">
          Annuler et revenir à la connexion
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// ref : pour créer des variables réactives dans Vue 3
import { ref } from 'vue';

// Le store d'auth qui contient la fonction addUser() pour appeler l'API
import { useAuthStore } from '../stores/authStore';

// useRouter : permet de naviguer vers une autre page par code
import { useRouter } from 'vue-router';

// Initialisation du store et du router
const authStore = useAuthStore();
const router = useRouter();

// Modèle du formulaire : chaque propriété est liée à un champ HTML via v-model
const form = ref({
  name: '',          // nom complet de l'utilisateur
  email: '',         // adresse email
  password: '',      // mot de passe choisi
  role: 'Développeur', // rôle par défaut — synchronisé avec le select HTML
  link: '',          // lien GitHub principal (obligatoire pour développeur)
  extraLink: '',     // lien annexe facultatif
  description: ''    // texte de présentation facultatif
});

// Fonction appelée quand l'utilisateur soumet le formulaire
const registerCandidate = async () => {
  try {
    // Appelle addUser() du store qui envoie les données à l'API backend
    // Le store se charge de convertir "Développeur" → "developpeur" pour le backend
    await authStore.addUser({
      name:     form.value.name,
      email:    form.value.email,
      password: form.value.password,
      role:     form.value.role,
      link:     form.value.link,
    });

    // Informe l'utilisateur que sa demande a bien été envoyée
    alert("Dossier Enregistré ! 🎉\nVotre compte est en attente de validation.");

    // Redirige automatiquement vers la page de connexion après l'inscription
    router.push({ name: 'Login' });

  } catch (err: any) {
    // Si le backend retourne une erreur (ex: email déjà utilisé, mot de passe trop faible)
    // on affiche le détail de l'erreur à l'utilisateur
    const msg = err.response?.data?.errors || err.response?.data?.message || 'Erreur inconnue';
    alert("Erreur : " + JSON.stringify(msg));
  }
};
</script>
