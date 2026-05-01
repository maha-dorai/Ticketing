<template>
  <!-- Conteneur principal : occupe tout l'écran avec un fond gris clair -->
  <div class="p-8 min-h-screen bg-gray-50">

    <!-- En-tête du dashboard : titre à gauche, bouton déconnexion à droite -->
    <div class="flex items-center justify-between mb-8 pb-4 border-b">
      <div>
        <h1 class="text-3xl font-extrabold text-gray-900">Console d'Administration</h1>
        <p class="text-gray-500 mt-1">Gestion des candidatures et des membres actifs de la plateforme</p>
      </div>
      <!-- Bouton déconnexion : appelle la fonction logout() définie dans le script -->
      <button @click="logout" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600 transition shadow-sm font-semibold">
        Se déconnecter (Admin)
      </button>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- SECTION 1 : DOSSIERS EN ATTENTE           -->
    <!-- Utilisateurs avec statut "en_attente"     -->
    <!-- ══════════════════════════════════════════ -->
    <div class="mb-12">
      <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-yellow-500 pl-3">
        Dossiers en Attente d'Approbation
      </h2>

      <!-- Message affiché si aucun utilisateur n'est en attente (liste vide) -->
      <div v-if="pendingUsers.length === 0" class="text-center bg-white p-6 rounded-lg shadow border border-gray-100">
        <p class="text-gray-500 font-medium">Aucun utilisateur en attente de validation. 🎉</p>
      </div>

      <!-- Tableau des candidats en attente : affiché seulement si la liste n'est pas vide -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identité Candidate</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Preuve Fournie (Link)</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Altération Rôle</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Verdict Admin</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">

            <!-- v-for : crée une ligne par utilisateur en attente -->
            <!-- :key="user.id" : identifiant unique pour que Vue gère la liste efficacement -->
            <tr v-for="user in pendingUsers" :key="user.id" class="hover:bg-blue-50 transition duration-150">

              <!-- Colonne : Nom et Email du candidat -->
              <!-- Le backend renvoie nom et prenom séparément -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-bold text-gray-900">{{ user.nom + ' ' + user.prenom }}</div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </td>

              <!-- Colonne : Lien GitHub du candidat -->
              <!-- ⚠️ BUG ICI : user.link n'existe pas — le backend renvoie user.github_link -->
              <td class="px-6 py-4 whitespace-normal max-w-xs">
                <!-- Affiche le lien principal si il existe -->
                <a v-if="user.github_link" :href="user.github_link" target="_blank"
                  class="text-blue-600 hover:text-blue-900 font-medium underline inline-flex items-center gap-1 mb-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                  Dossier Principal
                </a>
                <!-- Affiché si aucun lien n'a été fourni -->
                <span v-else class="text-gray-400 text-sm italic block mb-1">Non fourni</span>
              </td>

              <!-- Colonne : Sélecteur de rôle — l'admin peut changer le rôle avant d'approuver -->
              <!-- v-model="user.role" modifie directement la propriété role de l'objet user dans allUsers -->
              <td class="px-6 py-4 whitespace-nowrap">
                <select v-model="user.role"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                  <!-- ⚠️ BUG : valeurs "Développeur"/"Testeur" au lieu de "developpeur"/"testeur" -->
                  <option value="developpeur">Développeur</option>
                  <option value="testeur">Testeur</option>
                </select>
              </td>

              <!-- Colonne : Boutons Approuver / Refuser -->
              <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                <!-- Appelle validerCandidat() avec l'id du candidat → PUT /api/users/{id}/validate { action: 'accepter' } -->
                <button @click="validerCandidat(user.id)"
                  class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center mr-2 transition">
                  ✓ Approuver
                </button>
                <!-- Appelle refuserCandidat() avec l'id du candidat → PUT /api/users/{id}/validate { action: 'rejeter' } -->
                <button @click="refuserCandidat(user.id)"
                  class="text-red-600 hover:text-white border border-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center transition">
                  ✕ Refuser
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>


    <!-- ══════════════════════════════════════════ -->
    <!-- SECTION 2 : ÉQUIPE ACTIVE                 -->
    <!-- Utilisateurs avec statut "actif"          -->
    <!-- ══════════════════════════════════════════ -->
    <div>
      <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-green-500 pl-3">Équipe Active</h2>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identité</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rôle Principal</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Panneau d'Administration</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">

            <!-- v-for : une ligne par membre actif -->
            <tr v-for="user in approvedUsers" :key="user.id" class="hover:bg-gray-50 transition duration-150">

              <!-- Colonne : Nom + badge "Admin" si le rôle est admin -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-bold text-gray-900">
                  {{ user.nom + ' ' + user.prenom }}
                  <!-- Badge "Admin" affiché uniquement pour le compte administrateur -->
                  <!-- 'admin' en minuscule correspond à la valeur réelle du backend -->
                  <span v-if="user.role === 'admin'" class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Admin</span>
                </div>
                <div class="text-sm text-gray-500">{{ user.email }}</div>
              </td>

              <!-- Colonne : Rôle affiché en texte -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 font-medium">{{ user.role }}</div>
              </td>

              <!-- Colonne : Boutons Modifier / Supprimer -->
              <!-- v-if="user.role !== 'admin'" : on cache les boutons pour le compte admin lui-même -->
              <!-- Cela évite qu'un admin se supprime ou se modifie accidentellement -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <!-- Ouvre la modale de modification préremplie avec les données de cet utilisateur -->
                <button v-if="user.role !== 'admin'" @click="ouvrirModalEdition(user)"
                  class="text-blue-600 hover:text-blue-900 mr-4 font-semibold px-2 py-1 bg-blue-50 rounded">Modifier</button>
                <!-- Supprime définitivement l'utilisateur après confirmation -->
                <button v-if="user.role !== 'admin'" @click="effacerUtilisateur(user.id)"
                  class="text-red-600 hover:text-red-900 font-semibold px-2 py-1 bg-red-50 rounded">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>


    <!-- ══════════════════════════════════════════ -->
    <!-- MODALE D'ÉDITION                          -->
    <!-- Fenêtre popup pour modifier un membre     -->
    <!-- ══════════════════════════════════════════ -->

    <!-- v-if="showModal" : la modale n'existe dans le DOM que si showModal est true -->
    <!-- Le fond semi-transparent couvre toute la page (fixed inset-0) -->
    <div v-if="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <!-- Carte blanche de la modale -->
      <div class="p-8 bg-white rounded-lg shadow-xl w-full max-w-md">
        <h2 class="mb-4 text-2xl font-bold text-gray-800">Modifier l'Employé</h2>

        <!-- Formulaire de modification : @submit.prevent appelle sauvegarderUtilisateur() -->
        <form @submit.prevent="sauvegarderUtilisateur">

          <!-- Champ Nom : lié à form.nom -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-bold text-gray-700">Nom</label>
            <input v-model="form.nom" type="text" required placeholder="Nom"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Champ Prénom : lié à form.prenom -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-bold text-gray-700">Prénom</label>
            <input v-model="form.prenom" type="text" required placeholder="Prénom"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Champ Email : lié à form.email -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-bold text-gray-700">Adresse Email</label>
            <input v-model="form.email" type="email" required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Sélecteur de rôle : lié à form.role -->
          <div class="mb-6">
            <label class="block mb-2 text-sm font-bold text-gray-700">Nouveau Rôle</label>
            <select v-model="form.role"
              class="w-full px-3 py-2 border border-gray-300 bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <!-- Les valeurs correspondent aux enums du backend -->
              <option value="developpeur">Développeur</option>
              <option value="testeur">Testeur</option>
            </select>
          </div>

          <!-- Boutons d'action de la modale -->
          <div class="flex justify-end gap-2">
            <!-- Annuler : ferme la modale sans sauvegarder -->
            <button type="button" @click="fermerModal"
              class="px-4 py-2 font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition">Annuler</button>
            <!-- Enregistrer : soumet le formulaire → appelle sauvegarderUtilisateur() -->
            <button type="submit"
              class="px-4 py-2 font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
// ref : variable réactive simple | computed : variable calculée automatiquement | onMounted : exécuté au chargement
import { ref, computed, onMounted } from 'vue';

// Le store d'authentification (pour le logout)
import { useAuthStore } from '../../stores/authStore';

// Le router Vue pour naviguer après déconnexion
import { useRouter } from 'vue-router';

// Le pont HTTP pour appeler directement l'API Laravel
import api from '../../services/api';

// Initialisation du store et du router
const authStore = useAuthStore();
const router = useRouter();

// ─────────────────────────────────────────────
// DONNÉES
// ─────────────────────────────────────────────

// Tableau réactif contenant tous les utilisateurs chargés depuis l'API
// Il commence vide et est rempli par fetchUsers() au chargement de la page
const allUsers = ref([]);

// Fonction qui appelle l'API pour récupérer la liste des utilisateurs
const fetchUsers = async () => {
  try {
    // GET http://localhost:8000/api/users (route protégée : admin seulement)
    // api.js ajoute automatiquement le header Authorization: Bearer <token>
    const res = await api.get('/users');

    // On stocke la liste reçue dans allUsers — Vue re-rend le tableau automatiquement
    allUsers.value = res.data;
  } catch (err) {
    // Si l'API échoue (ex: token expiré, serveur éteint), on affiche l'erreur dans la console
    console.error('Erreur chargement users', err);
  }
};

// onMounted : exécute fetchUsers() dès que la page s'affiche dans le navigateur
onMounted(fetchUsers);

// ─────────────────────────────────────────────
// FILTRES CALCULÉS AUTOMATIQUEMENT
// computed : se recalcule automatiquement dès que allUsers change
// ─────────────────────────────────────────────

// Liste des utilisateurs en attente d'approbation
const pendingUsers = computed(() =>
  allUsers.value.filter(u => u.statut === 'en_attente')  // 'en_attente' = valeur exacte dans la DB
);

// Liste des utilisateurs actifs (approuvés par l'admin)
const approvedUsers = computed(() =>
  allUsers.value.filter(u => u.statut === 'actif')        // 'actif' = valeur exacte dans la DB
);

// ─────────────────────────────────────────────
// ACTIONS : APPROUVER / REJETER UN CANDIDAT
// ─────────────────────────────────────────────

// Approuve le candidat : change son statut de "en_attente" → "actif" dans la DB
const validerCandidat = async (id) => {
  // PUT http://localhost:8000/api/users/{id}/validate avec { action: 'accepter' }
  await api.put(`/users/${id}/validate`, { action: 'accepter' });

  // Recharge la liste pour refléter le changement (le candidat passe dans "Équipe Active")
  await fetchUsers();
};

// Rejette le candidat : change son statut à "rejete" dans la DB
const refuserCandidat = async (id) => {
  // Confirmation native du navigateur avant d'effectuer l'action irréversible
  if (confirm("Ce dossier sera rejeté. Êtes vous certain ?")) {
    // PUT http://localhost:8000/api/users/{id}/validate avec { action: 'rejeter' }
    await api.put(`/users/${id}/validate`, { action: 'rejeter' });

    // Recharge la liste
    await fetchUsers();
  }
};

// ─────────────────────────────────────────────
// ACTIONS : MODIFIER UN MEMBRE ACTIF
// ─────────────────────────────────────────────

// Contrôle l'affichage de la modale (true = visible, false = cachée)
const showModal = ref(false);

// Objet réactif contenant les données du formulaire de modification
// Prérempli par ouvrirModalEdition() quand l'admin clique sur "Modifier"
const form = ref({ id: null, nom: '', prenom: '', email: '', role: '' });

// Ouvre la modale et la prérempli avec les données de l'utilisateur sélectionné
const ouvrirModalEdition = (user) => {
  form.value = {
    id:     user.id,
    nom:    user.nom,
    prenom: user.prenom,
    email:  user.email,
    role:   user.role
  };
  // Rend la modale visible
  showModal.value = true;
};

// Ferme la modale sans sauvegarder
const fermerModal = () => {
  showModal.value = false;
};

// Sauvegarde les modifications en appelant l'API, puis ferme la modale et recharge la liste
const sauvegarderUtilisateur = async () => {
  // PUT http://localhost:8000/api/users/{id} avec les nouvelles valeurs
  await api.put(`/users/${form.value.id}`, {
    nom:    form.value.nom,
    prenom: form.value.prenom,
    email:  form.value.email,
    role:   form.value.role
  });

  // Ferme la modale après sauvegarde
  fermerModal();

  // Recharge la liste pour afficher les modifications
  await fetchUsers();
};

// ─────────────────────────────────────────────
// ACTION : SUPPRIMER UN MEMBRE ACTIF
// ─────────────────────────────────────────────

// Supprime définitivement un utilisateur de la base de données
const effacerUtilisateur = async (id) => {
  // Confirmation avant suppression définitive
  if (confirm("Supprimer ce membre définitivement ?")) {
    // DELETE http://localhost:8000/api/users/{id}
    await api.delete(`/users/${id}`);

    // Recharge la liste
    await fetchUsers();
  }
};

// ─────────────────────────────────────────────
// DÉCONNEXION
// ─────────────────────────────────────────────

// Déconnecte l'admin et le redirige vers la page de connexion
const logout = () => {
  // Appelle la fonction logout() du store (invalide le token côté serveur + nettoie localStorage)
  authStore.logout();

  // Redirige vers la page de connexion
  router.push({ name: 'Login' });
};
</script>
