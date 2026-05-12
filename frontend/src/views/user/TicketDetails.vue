<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Navigation Retour -->
      <div class="flex items-center justify-between">
        <button @click="$router.push({ name: 'Tickets' })" class="text-sm text-gray-500 hover:text-gray-900 font-semibold transition flex items-center gap-1">
          &larr; Retour aux tickets
        </button>
        <button @click="logout" class="px-4 py-2 text-sm text-white bg-gray-600 rounded hover:bg-gray-700 font-semibold transition">
          Déconnexion
        </button>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement du ticket...</div>

      <div v-else-if="ticket" class="space-y-6">
        
        <!-- Header du Ticket -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between gap-6">
          <div class="space-y-2 flex-1">
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-extrabold text-gray-900">{{ ticket.titre }}</h1>
              <span :class="etatClass(ticket.etat)" class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                {{ ticket.etat }}
              </span>
              <span :class="prioriteClass(ticket.priorite)" class="px-2.5 py-1 rounded text-xs font-bold uppercase">
                {{ ticket.priorite }}
              </span>
            </div>
            <p class="text-gray-600 whitespace-pre-wrap text-sm leading-relaxed">{{ ticket.description || 'Aucune description fournie.' }}</p>
            
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-500 pt-3 border-t mt-4">
              <div class="flex items-center gap-1">
                <span>📁</span> <span class="font-medium text-gray-700">{{ ticket.project?.nom }}</span>
              </div>
              <div class="flex items-center gap-1">
                <span>✍️</span> <span>Créé par: <span class="font-medium text-gray-700">{{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}</span></span>
              </div>
              <div class="flex items-center gap-1">
                <span>👨‍💻</span> 
                <span v-if="ticket.developpeur">Assigné à: <span class="font-medium text-gray-700">{{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}</span></span>
                <span v-else class="italic">Non assigné</span>
              </div>
            </div>
          </div>

          <!-- Actions d'État -->
          <div class="bg-gray-50 p-4 rounded-lg border flex flex-col gap-3 min-w-[200px] shrink-0">
            <h3 class="text-sm font-bold text-gray-800 border-b pb-2">Actions</h3>
            
            <div v-if="currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME'" class="space-y-2">
              <label class="text-xs font-semibold text-gray-600 block">Changer l'état</label>
              <select v-model="selectedState" @change="changeState" class="w-full px-2 py-1.5 text-sm border rounded focus:ring focus:ring-blue-200 outline-none">
                <option value="OUVERT">OUVERT</option>
                <option value="EN_COURS">EN_COURS</option>
                <option value="RESOLU">RESOLU</option>
              </select>
            </div>

            <div v-if="(currentUser?.role === 'testeur' && ticket.testeur_id === currentUser.id) || currentUser?.role === 'admin' || currentUser?.role === 'super_admin'">
              <button v-if="ticket.etat !== 'FERME'" @click="closeTicket" class="w-full px-3 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded font-bold transition">
                Fermer le ticket
              </button>
              <div v-else class="text-sm text-red-600 font-bold text-center py-2 bg-red-50 rounded">
                TICKET FERMÉ
              </div>
            </div>
            
            <div v-if="stateUpdating" class="text-xs text-blue-600 text-center animate-pulse">Mise à jour...</div>
          </div>
        </div>

        <!-- Section Commentaires -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col" style="min-h: 400px;">
          <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800">Commentaires ({{ ticket.comments?.length || 0 }})</h2>
          </div>
          
          <div class="flex-1 p-6 overflow-y-auto space-y-4 max-h-[500px] bg-gray-50">
            <div v-if="!ticket.comments?.length" class="text-center text-sm text-gray-400 py-8">
              Aucun commentaire pour le moment.
            </div>
            <div v-for="comment in ticket.comments" :key="comment.id" 
                 class="flex flex-col max-w-[85%]" 
                 :class="comment.user_id === currentUser.id ? 'ml-auto items-end' : 'mr-auto items-start'">
              <div class="flex items-center gap-2 mb-1" :class="comment.user_id === currentUser.id ? 'flex-row-reverse' : ''">
                <span class="text-xs font-bold text-gray-700">{{ comment.user?.prenom }} {{ comment.user?.nom }}</span>
                <span class="text-[10px] text-gray-400">{{ formatTime(comment.created_at) }}</span>
              </div>
              <div class="px-4 py-2.5 rounded-2xl text-sm shadow-sm"
                   :class="comment.user_id === currentUser.id ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white border text-gray-800 rounded-bl-none'">
                {{ comment.contenu }}
              </div>
            </div>
          </div>

          <!-- Zone de saisie -->
          <div class="p-4 bg-white border-t">
            <form @submit.prevent="submitComment" class="flex items-end gap-2">
              <textarea v-model="newComment" rows="2" required
                class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-200 outline-none text-sm resize-none"
                placeholder="Écrire un commentaire..."></textarea>
              <button type="submit" :disabled="!newComment.trim() || submittingComment" 
                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition disabled:opacity-50">
                Envoyer
              </button>
            </form>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const currentUser = authStore.currentUser;

const ticket = ref(null);
const loading = ref(true);
const selectedState = ref('');
const stateUpdating = ref(false);

const newComment = ref('');
const submittingComment = ref(false);

const fetchTicket = async () => {
  try {
    const res = await api.get(`/tickets/${route.params.id}`);
    ticket.value = res.data;
    selectedState.value = ticket.value.etat;
  } catch (e) {
    console.error(e);
    router.push({ name: 'Tickets' });
  } finally {
    loading.value = false;
  }
};

const changeState = async () => {
  if (selectedState.value === ticket.value.etat) return;
  stateUpdating.value = true;
  try {
    await api.put(`/tickets/${ticket.value.id}/status`, { etat: selectedState.value });
    ticket.value.etat = selectedState.value;
  } catch (e) {
    alert("Erreur lors du changement d'état");
    selectedState.value = ticket.value.etat; // revert
  } finally {
    stateUpdating.value = false;
  }
};

const closeTicket = async () => {
  if (!confirm('Voulez-vous vraiment fermer ce ticket ?')) return;
  stateUpdating.value = true;
  try {
    await api.put(`/tickets/${ticket.value.id}/close`);
    ticket.value.etat = 'FERME';
  } catch (e) {
    alert("Erreur lors de la fermeture du ticket");
  } finally {
    stateUpdating.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value.trim()) return;
  submittingComment.value = true;
  try {
    const res = await api.post('/comments', {
      ticket_id: ticket.value.id,
      contenu: newComment.value.trim()
    });
    // Add locally to avoid full refetch
    if (!ticket.value.comments) ticket.value.comments = [];
    ticket.value.comments.push(res.data);
    newComment.value = '';
    
    // Quick scroll to bottom (setTimeout hack for simplicity)
    setTimeout(() => {
      const container = document.querySelector('.overflow-y-auto');
      if (container) container.scrollTop = container.scrollHeight;
    }, 100);
  } catch (e) {
    alert("Erreur lors de l'envoi du commentaire");
  } finally {
    submittingComment.value = false;
  }
};

onMounted(() => {
  fetchTicket();
});

const logout = () => {
  authStore.logout();
  router.push({ name: 'Login' });
};

// Helpers
const formatTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }) : '';

const etatClass = (etat) => {
  const map = {
    OUVERT: 'bg-green-100 text-green-800',
    EN_COURS: 'bg-yellow-100 text-yellow-800',
    RESOLU: 'bg-blue-100 text-blue-800',
    FERME: 'bg-gray-200 text-gray-700'
  };
  return map[etat] || 'bg-gray-100 text-gray-500';
};

const prioriteClass = (prio) => {
  const map = {
    BASSE: 'bg-gray-100 text-gray-600',
    MOYENNE: 'bg-blue-100 text-blue-600',
    HAUTE: 'bg-orange-100 text-orange-700',
    CRITIQUE: 'bg-red-100 text-red-700'
  };
  return map[prio] || 'bg-gray-100 text-gray-600';
};
</script>
