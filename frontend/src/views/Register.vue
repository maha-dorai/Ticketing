<template>
  <!-- Base Visuelle simulant une application Web élégante centré -->
  <div class="flex items-center justify-center min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">
      
      <!-- L'En-Tête d'Inscription -->
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Rejoindre l'Équipe</h2>
        <p class="mt-2 text-center text-sm text-gray-600">Inscription soumise à validation</p>
      </div>

      <!-- FORMULAIRE (Empêchant l'envoi auto de la page grâce à @submit.prevent) -->
      <!-- Appelle la fonction registerCandidate() programmée en JS plus bas. -->
      <form class="mt-8 space-y-6" @submit.prevent="registerCandidate">
        
        <div class="rounded-md shadow-sm space-y-4">
          <!-- Partie "Nom". Attaching variable avec v-model et "required" oblige l'utilisateur à remplir HTML5 -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Nom complet</label>
            <input v-model="form.name" type="text" required class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="John Doe">
          </div>
          
          <!-- Partie Email -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Email professionnel</label>
            <input v-model="form.email" type="email" required class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="jean@dev.com">
          </div>
          
          <!-- L'utilisateur s'attribue lui-même un mot de passe sécurisé. -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Mot de passe</label>
            <input v-model="form.password" type="password" required class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="••••••••">
          </div>
          
          <!-- Le fameux rôle Selecteur (Point critique du cours : v-model) -->
          <!-- Dès que l'utilisateur clique et change la valeur de "form.role", la variable globale est modifiée en RAM !! -->
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Postulez pour un de nos rôles ("Lequel êtes vous ?")</label>
            <select v-model="form.role" class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white">
              <option value="Développeur">Je suis un Développeur</option>
              <option value="Testeur">Je suis un Testeur (QA)</option>
            </select>
          </div>

          <!-- LA MAGIE VUE 3 : RENDU CONDITIONNEL (Directives v-if / v-else)-->
          <!-- Si la variable form.role a été changée magiquement par le champ select ci-dessus et a la chaine "Développeur"...  -->
          <div v-if="form.role === 'Développeur'" class="bg-blue-50 p-4 rounded-lg mt-4 border border-blue-100 transition-all space-y-4">
            
            <!-- Preuve Principale -->
            <div>
              <label class="block mb-1 text-sm font-bold text-blue-800">🔗 Preuve Obligatoire (Github, Portfolio)</label>
              <!-- L'information est captée via un unique 'link' global. -->
              <input v-model="form.link" type="url" required class="block w-full px-3 py-2 border border-blue-300 bg-white rounded-md text-gray-900 sm:text-sm focus:border-blue-500 focus:outline-none" placeholder="https://github.com/mon-username">
              <p class="mt-1 text-xs text-blue-600">Obligatoire pour l'accréditation développeur "Administrateur Dev".</p>
            </div>

            <!-- Preuve supplémentaire (NOUVEAU) -->
            <div>
               <label class="block mb-1 text-sm font-bold text-blue-800">📎 Autre lien (Facultatif)</label>
               <input v-model="form.extraLink" type="url" class="block w-full px-3 py-2 border border-blue-300 bg-white rounded-md text-gray-900 sm:text-sm focus:border-blue-500 focus:outline-none" placeholder="https://lien-vers-certificat-ou-codepen.com">
            </div>

            <!-- Description / Motivations (NOUVEAU) -->
            <div>
               <label class="block mb-1 text-sm font-bold text-blue-800">📝 Présentation / Motivations</label>
               <textarea v-model="form.description" rows="3" class="block w-full px-3 py-2 border border-blue-300 bg-white rounded-md text-gray-900 sm:text-sm focus:border-blue-500 focus:outline-none" placeholder="Décrivez vos compétences techniques ou vos projets..."></textarea>
            </div>

          </div>

        </div>

        <!-- Dispositif validateur de formulaire -->
        <div>
          <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
            Soumettre le Dossier
          </button>
        </div>
      </form>
      
      <!-- Retour salvateur vers l'authentification grâce au routeur-lien interne ! -->
      <div class="text-center mt-4">
        <router-link to="/login" class="text-sm text-gray-500 hover:text-gray-900 transition underline">Annuler et revenir à la connexion</router-link>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
// L'ADN absolu de Vue.
import { ref } from 'vue';
// L'accès au réseau Mock (Store).
import { useAuthStore } from '../stores/authStore';
// L'injecteur de Route.
import { useRouter } from 'vue-router';

// On appelle le Mock Pinia afin de posséder un canal de communication avec notre base de données 'users' au complet.
const authStore = useAuthStore();
// Mécanisme de manipulation des Adresses Url par le Script !
const router = useRouter();

// Modèle (Le 'V-Model' !). C'est le clone fantôme de votre Formulaire Html dans un monde purement programmatique.
const form = ref({
  name: '',
  email: '',
  password: '',
  // Initialité primordiale. En donnant une option par défaut, tout est synchronisé visuellement sans vide HTML/JS.
  role: 'Développeur', 
  // Ce champs fusionne la preuve Github OU la preuve LinkedIn selon le choix de rôle !
  link: '',
  // Champs additionnels
  extraLink: '',
  description: ''
});

// Opération qui s'exécute SI et SEULEMENT SI le grand @submit.prevent Html passe la validation CSS / navigateur "Required" et "Email Type"
const registerCandidate = async () => {
  try {
    await authStore.addUser({
      name:      form.value.name,
      email:     form.value.email,
      password:  form.value.password,
      role:      form.value.role,
      link:      form.value.link,
    });
    alert("Dossier Enregistré ! 🎉\nVotre compte est en attente de validation.");
    router.push({ name: 'Login' });
  } catch (err: any) {
    const msg = err.response?.data?.errors || err.response?.data?.message || 'Erreur';
    alert("Erreur : " + JSON.stringify(msg));
  }
};
</script>
