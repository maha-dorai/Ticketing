<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <AppHeader />

      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement du ticket...</div>

      <div v-else-if="ticket" class="space-y-6 max-w-5xl mx-auto pb-12">

        <!-- Header -->
        <div class="flex items-center justify-between mb-2">
          <button @click="$router.push({ name: 'Tickets', params: { projectId: route.params.projectId } })" class="text-blue-500 hover:text-blue-700 font-bold text-sm flex items-center gap-2 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Retour aux tickets
          </button>
          <div class="flex gap-2">
            <span :class="prioriteClass(ticket.priorite)" class="px-3 py-1 text-xs font-bold rounded-full border bg-white shadow-sm">{{ ticket.priorite }}</span>
          </div>
        </div>

        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight leading-tight">🎫 {{ ticket.titre }}</h1>

        <!-- Confirm dialog -->
        <div v-if="confirmDialog.show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-sm w-full transform scale-100 transition-all">
            <p class="text-slate-800 font-bold mb-6 text-center text-lg">{{ confirmDialog.message }}</p>
            <div class="flex justify-center gap-3">
              <button @click="confirmDialog.show = false" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Annuler</button>
              <button @click="confirmDialog.onConfirm(); confirmDialog.show = false" :class="confirmDialog.danger ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-600 hover:bg-blue-700'" class="px-5 py-2.5 rounded-xl font-bold text-white transition shadow-md">Confirmer</button>
            </div>
          </div>
        </div>

        <!-- Modal réclamation -->
        <div v-if="reclamationModal.show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
          <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-md w-full">
            <div class="flex items-center gap-3 mb-4">
              <span class="text-2xl">⚠️</span>
              <h3 class="text-lg font-extrabold text-slate-800">Raison de la réclamation</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Décrivez ce qui ne va pas avec la résolution proposée. Le développeur recevra ce message.</p>
            <textarea
              v-model="reclamationModal.raison"
              rows="4"
              placeholder="Ex: Le bug est toujours présent sur la page de connexion, le formulaire ne valide pas..."
              class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm resize-none outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100 text-slate-800 placeholder-slate-400"
              autofocus
            ></textarea>
            <p v-if="reclamationModal.error" class="text-xs text-red-500 mt-2 font-medium">{{ reclamationModal.error }}</p>
            <div class="flex justify-end gap-3 mt-5">
              <button @click="reclamationModal.show = false; reclamationModal.raison = ''; reclamationModal.error = ''" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Annuler</button>
              <button @click="submitReclamation" class="px-5 py-2.5 rounded-xl font-bold text-white bg-orange-500 hover:bg-orange-600 transition shadow-md">
                Envoyer la réclamation
              </button>
            </div>
          </div>
        </div>

        <!-- Timeline Interactive (Drag & Drop) -->
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-6 border border-slate-100">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-extrabold text-slate-700 uppercase tracking-widest flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-blue-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Pipeline d'état
            </h3>
            <span v-if="stateUpdating" class="text-xs text-blue-500 font-bold animate-pulse">Synchronisation...</span>
          </div>

          <div class="flex flex-col md:flex-row items-stretch gap-3 h-auto md:h-28">
            <div
              v-for="col in columns"
              :key="col.etat"
              class="flex-1 rounded-xl border-2 transition-all relative overflow-hidden flex flex-col justify-center items-center p-3"
              :class="[
                dragTarget === col.etat ? 'border-blue-400 bg-blue-50 scale-[1.02] shadow-inner' : 'border-slate-100 bg-slate-50',
                ticket.etat === col.etat ? 'border-blue-200 bg-blue-50/50' : ''
              ]"
              @dragover.prevent="onDragOver(col.etat)"
              @drop.prevent="onDrop(col.etat)"
            >
              <span class="text-[11px] font-extrabold text-slate-500 mb-3 uppercase tracking-wider text-center">{{ col.label }}</span>
              
              <div
                v-if="ticket.etat === col.etat"
                class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-bold px-5 py-2.5 rounded-full shadow-lg shadow-blue-600/30 transition-transform flex items-center gap-2"
                :class="canDragTicket ? 'cursor-grab active:cursor-grabbing hover:scale-105' : 'opacity-70 cursor-not-allowed'"
                :draggable="canDragTicket"
                @dragstart="onDragStart"
                @dragend="onDragEnd"
              >
                <span v-if="canDragTicket">✋ Glissez-moi</span>
                <span v-else>🔒 Actuel</span>
              </div>
            </div>
          </div>
          <p v-if="!canDragTicket" class="text-xs text-center text-slate-400 mt-4 font-medium italic">
            {{ isManager ? 'En tant que manager, vous êtes en lecture seule sur le flux Kanban.' : 'Vous n\'avez pas les droits de modifier cet état ou le ticket ne vous est pas assigné.' }}
          </p>
        </div>

        <!-- 🧠 Carte IA -->
        <div v-if="ticket.categorie_ia || ticket.priorite_ia || ticket.solution_ia" class="bg-gradient-to-br from-violet-50 to-purple-50 border border-violet-200 rounded-2xl shadow-lg shadow-violet-900/5 p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md shadow-violet-500/30 flex-shrink-0">
              <span class="text-white text-lg">🤖</span>
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-violet-900 tracking-tight">Analyse par Intelligence Artificielle</h3>
              <p class="text-[10px] text-violet-500 font-medium">Générée automatiquement à la création du ticket</p>
            </div>
            <button @click="reanalyzeAI" :disabled="aiLoading" class="ml-auto px-3 py-1.5 text-[10px] font-bold text-violet-600 border border-violet-300 bg-white hover:bg-violet-50 rounded-lg transition disabled:opacity-50" title="Relancer l'analyse IA">
              {{ aiLoading ? '⏳ ...' : '🔄 Relancer' }}
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Catégorie -->
            <div v-if="ticket.categorie_ia" class="bg-white rounded-xl p-4 border border-violet-100 shadow-sm">
              <p class="text-[10px] font-bold text-violet-400 uppercase tracking-widest mb-2">🏷️ Catégorie détectée</p>
              <span class="inline-block px-3 py-1 text-xs font-extrabold text-violet-700 bg-violet-100 rounded-full border border-violet-200">{{ categorieLabel(ticket.categorie_ia) }}</span>
            </div>

            <!-- Priorité IA -->
            <div v-if="ticket.priorite_ia" class="bg-white rounded-xl p-4 border border-violet-100 shadow-sm">
              <p class="text-[10px] font-bold text-violet-400 uppercase tracking-widest mb-2">⚡ Priorité suggérée</p>
              <span :class="prioriteClass(ticket.priorite_ia)" class="inline-block px-3 py-1 text-xs font-extrabold rounded-full border">{{ ticket.priorite_ia }}</span>
              <p v-if="ticket.priorite_ia !== ticket.priorite" class="text-[10px] text-slate-400 mt-1">Priorité actuelle: {{ ticket.priorite }}</p>
            </div>

            <!-- Confiance -->
            <div class="bg-white rounded-xl p-4 border border-violet-100 shadow-sm flex items-center justify-center">
              <div class="text-center">
                <div class="text-2xl font-black text-violet-600">NLP</div>
                <div class="text-[10px] text-violet-400 font-bold uppercase tracking-wider mt-1">Modèle Claude AI</div>
              </div>
            </div>
          </div>

          <!-- Solution suggérée — visible uniquement par le développeur assigné -->
          <div v-if="ticket.solution_ia && currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser?.id" class="mt-4 bg-white rounded-xl p-5 border border-violet-100 shadow-sm">
            <p class="text-[10px] font-bold text-violet-400 uppercase tracking-widest mb-3">💡 Solution suggérée par l'IA</p>
            <p class="text-sm text-slate-700 leading-relaxed">{{ ticket.solution_ia }}</p>
          </div>
        </div>

        <!-- Ticket layout: Details (Left) + Sidebar (Right) -->
        <div class="flex flex-col lg:flex-row gap-6">
          
          <!-- Left Content -->
          <div class="flex-1 space-y-6">
            
            <!-- Description Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-8 border border-slate-100">
              <!-- FIX: affichage description avec fallback propre -->
              <div class="prose prose-slate max-w-none text-sm leading-relaxed">
                <div v-if="ticket.description && ticket.description.trim()" v-html="formatDescription(ticket.description)"></div>
                <p v-else class="text-slate-400 italic text-sm flex items-center gap-2">
                  <span>📝</span> Aucune description fournie.
                </p>
              </div>
              
              <!-- Attachments -->
              <div v-if="ticket.attachments?.length" class="mt-8 pt-6 border-t border-slate-100">
                <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                  📎 Pièces jointes ({{ ticket.attachments.length }})
                </h4>
                <div class="flex flex-wrap gap-3">
                  <a v-for="att in ticket.attachments" :key="att.id" :href="'http://localhost:8000/storage/' + att.file_path" target="_blank" class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 hover:border-blue-400 hover:bg-blue-50 hover:shadow-md rounded-xl text-sm font-medium text-blue-700 transition-all group">
                    <span class="text-xl group-hover:scale-110 transition-transform">📄</span>
                    <span class="truncate max-w-[200px]">{{ att.file_name }}</span>
                  </a>
                </div>
              </div>
            </div>

            <!-- Comments Section -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 overflow-hidden border border-slate-100 flex flex-col">
              <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest flex items-center gap-2">💬 Commentaires ({{ ticket.comments?.length || 0 }})</h2>
              </div>

              <div class="p-8 overflow-y-auto space-y-6 max-h-[500px]" ref="chatBox">
                <div v-if="!ticket.comments?.length" class="text-center text-sm text-slate-400 py-10 font-medium italic">Aucun commentaire pour le moment.</div>

                <div
                  v-for="comment in ticket.comments"
                  :key="comment.id"
                  class="flex flex-col max-w-[85%]"
                  :class="comment.user_id === currentUser.id ? 'ml-auto items-end' : 'mr-auto items-start'"
                >
                  <div class="flex items-center gap-2 mb-1.5" :class="comment.user_id === currentUser.id ? 'flex-row-reverse' : ''">
                    <span class="text-xs font-bold text-slate-700">{{ comment.user?.prenom }} {{ comment.user?.nom }}</span>
                    <span class="text-[10px] font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ formatTime(comment.created_at) }}</span>
                    <span v-if="comment.user_id === currentUser.id" class="flex gap-1 ml-1 opacity-0 group-hover:opacity-100 transition">
                      <button @click="startEdit(comment)" class="text-slate-400 hover:text-blue-500 text-xs transition">✏️</button>
                      <button @click="ask('Supprimer ce commentaire ?', () => deleteComment(comment), true)" class="text-slate-400 hover:text-red-500 text-xs transition">🗑️</button>
                    </span>
                  </div>

                  <div
                    v-if="editingCommentId !== comment.id"
                    class="px-5 py-3.5 rounded-2xl text-sm shadow-sm group relative"
                    :class="comment.user_id === currentUser.id ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-br-none shadow-blue-600/20' : 'bg-slate-50 border border-slate-200 text-slate-800 rounded-bl-none'"
                  >
                    {{ comment.contenu }}
                  </div>

                  <div v-else class="flex flex-col gap-2 w-full mt-1">
                    <textarea v-model="editContent" rows="2" class="w-full px-4 py-3 border border-blue-200 rounded-xl text-sm resize-none outline-none focus:ring-4 focus:ring-blue-500/20 text-slate-800 bg-blue-50" @keydown.esc="cancelEdit"></textarea>
                    <div class="flex gap-2 justify-end">
                      <button @click="cancelEdit" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition">Annuler</button>
                      <button @click="saveEdit(comment)" :disabled="!editContent.trim() || savingEdit" class="px-4 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg shadow-md hover:bg-blue-700 transition">{{ savingEdit ? '...' : 'Enregistrer' }}</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="p-5 border-t border-slate-100 bg-slate-50">
                <div class="flex items-end gap-3">
                  <textarea v-model="newComment" rows="2" placeholder="Ajouter un commentaire..." class="flex-1 px-4 py-3 border-slate-200 rounded-xl text-sm resize-none outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 shadow-sm" @keydown.enter.prevent="submitComment"></textarea>
                  <button @click="submitComment" :disabled="!newComment.trim() || submittingComment" class="px-6 py-3 bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">Envoyer</button>
                </div>
              </div>
            </div>

          </div>

          <!-- Right Sidebar (Actions & Info) -->
          <div class="w-full lg:w-80 space-y-6 flex-shrink-0">
            
            <!-- Info Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-6 border border-slate-100 space-y-4">
              <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest border-b border-slate-100 pb-3">Informations</h3>
              
              <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                  <span class="text-slate-500 font-medium">Projet</span>
                  <span class="font-bold text-slate-800 truncate max-w-[140px]">{{ ticket.project?.nom }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-500 font-medium">Testeur</span>
                  <span class="font-bold text-slate-800">{{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}</span>
                </div>
                
                <div class="pt-3 border-t border-slate-100 flex flex-col gap-1">
                  <span class="text-slate-500 font-medium">Assignation</span>
                  <span v-if="ticket.assignment_status === 'approved' && ticket.developpeur" class="font-bold text-blue-700 bg-blue-50 py-1.5 px-3 rounded-lg border border-blue-100 flex items-center gap-2 mt-1">
                    👨‍💻 {{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}
                  </span>
                  <span v-else-if="ticket.assignment_status === 'pending' && ticket.proposed_developpeur" class="font-bold text-amber-700 bg-amber-50 py-1.5 px-3 rounded-lg border border-amber-100 flex items-center gap-2 mt-1 text-xs">
                    ⏳ Prop: {{ ticket.proposed_developpeur.prenom }}
                  </span>
                  <span v-else class="italic text-slate-400 mt-1">Non assigné</span>
                </div>

                <!-- Raison réclamation -->
                <div v-if="ticket.etat === 'RECLAMATION' && ticket.raison_reclamation" class="pt-3 border-t border-slate-100">
                  <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest mb-2">⚠️ Raison de la réclamation</p>
                  <div class="bg-orange-50 border border-orange-200 rounded-xl p-3">
                    <p class="text-sm text-orange-900 leading-relaxed">{{ ticket.raison_reclamation }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Time Tracking Card -->
            <div v-if="ticket.temps_estime" class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200 p-6 text-slate-800 relative overflow-hidden">
              <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/40 rounded-full blur-2xl"></div>
              <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-4">Suivi du temps</h3>
              
              <div class="flex justify-between items-end mb-2">
                <span class="text-3xl font-black text-slate-800">{{ ticket.temps_passe || 0 }}<span class="text-lg text-slate-400 font-bold">h</span></span>
                <span class="text-sm font-bold text-slate-400 mb-1">/ {{ ticket.temps_estime }}h</span>
              </div>
              
              <div class="w-full bg-slate-200/70 rounded-full h-3 border border-slate-300/50">
                <div class="h-3 rounded-full transition-all duration-1000 ease-out shadow-[0_0_8px_rgba(59,130,246,0.4)]" :class="ticket.temps_passe > ticket.temps_estime ? 'bg-gradient-to-r from-red-500 to-orange-500' : 'bg-gradient-to-r from-blue-400 to-indigo-500'" :style="{ width: Math.min(100, ((ticket.temps_passe || 0) / ticket.temps_estime) * 100) + '%' }"></div>
              </div>

              <!-- Log Time Input (Dev only) -->
              <div v-if="currentUser?.role === 'developpeur' && ticket.assignment_status === 'approved' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME'" class="mt-5 pt-5 border-t border-slate-200">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-2">Ajouter des heures</label>
                <div class="flex gap-2">
                  <input v-model="timeToAdd" type="number" step="0.5" min="0.5" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-slate-800 placeholder-slate-400 transition-all" placeholder="Ex: 1.5">
                  <button @click="logTime" :disabled="!timeToAdd || stateUpdating" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors shadow-md disabled:opacity-50 disabled:cursor-not-allowed">OK</button>
                </div>
              </div>
            </div>

            <!-- Actions Panel -->
            <div class="bg-white rounded-2xl shadow-xl shadow-blue-900/5 p-6 border border-slate-100 space-y-4">
              <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest border-b border-slate-100 pb-3">Actions requises</h3>

              <!-- Valider/refuser assignation -->
              <div v-if="isManager && ticket.assignment_status === 'pending' && ticket.etat === 'OUVERT'" class="space-y-3">
                <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl">
                  <p class="text-[10px] font-bold text-amber-700 uppercase mb-1">Développeur proposé</p>
                  <p class="text-sm font-bold text-amber-900">{{ ticket.proposed_developpeur?.prenom }} {{ ticket.proposed_developpeur?.nom }}</p>
                </div>
                <button @click="ask('Valider cette assignation et notifier le développeur ?', acceptTicket)" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-xl shadow-md transition">✅ Valider</button>
                <button @click="ask('Refuser cette assignation ?', rejectTicket, true)" class="w-full py-2.5 bg-white border-2 border-slate-200 hover:border-red-500 hover:text-red-600 text-slate-600 text-sm font-bold rounded-xl transition">❌ Refuser</button>
              </div>

              <!-- Assignation manuelle -->
              <div v-if="isManager && ticket.assignment_status !== 'approved' && ticket.etat === 'OUVERT'" class="space-y-3 mt-2">
                <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigner manuellement</h4>
                <div v-if="workloads.length === 0" class="text-xs text-slate-400 italic text-center py-2">Aucun développeur disponible</div>
                <div v-else class="space-y-2 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
                  <div v-for="dev in workloads" :key="dev.id" class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-100 transition group">
                    <div>
                      <div class="text-xs font-bold text-slate-800">{{ dev.prenom }} {{ dev.nom }}</div>
                      <div class="text-[10px] font-medium text-slate-500 mt-0.5"><span class="w-2 h-2 inline-block rounded-full bg-blue-500 mr-1"></span>{{ dev.active_tickets_count }} actifs</div>
                    </div>
                    <button @click="ask(`Assigner ce ticket à ${dev.prenom} ${dev.nom} ?`, () => reassignTicket(dev.id))" class="px-3 py-1.5 bg-white border border-slate-300 hover:border-blue-500 hover:text-blue-600 text-slate-600 text-[10px] font-bold uppercase rounded-lg shadow-sm transition opacity-0 group-hover:opacity-100">Go</button>
                  </div>
                </div>
              </div>

              <!-- Nothing to do -->
              <div v-if="!isManager && !canDragTicket && !(currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME') && !(currentUser?.role === 'testeur' && ticket.testeur_id === currentUser.id)" class="text-center py-6">
                <span class="text-4xl block mb-2">☕</span>
                <p class="text-xs font-medium text-slate-400">Aucune action requise de votre part sur ce ticket.</p>
              </div>

              <div v-if="stateUpdating" class="text-xs text-blue-500 font-bold text-center py-2 animate-pulse">Synchronisation...</div>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const route       = useRoute();
const router      = useRouter();
const authStore   = useAuthStore();
const currentUser = authStore.currentUser;
const isManager   = computed(() => ['chef_de_projet', 'admin'].includes(currentUser?.role));

const ticket        = ref(null);
const loading       = ref(true);
const stateUpdating = ref(false);
const chatBox       = ref(null);
const workloads     = ref([]);
const timeToAdd     = ref(null);
const aiLoading     = ref(false);

const newComment        = ref('');
const submittingComment = ref(false);
const editingCommentId  = ref(null);
const editContent       = ref('');
const savingEdit        = ref(false);

// Drag & Drop Timeline State
const columns = [
  { etat: 'OUVERT',      label: 'À traiter'   },
  { etat: 'EN_COURS',    label: 'En cours'    },
  { etat: 'A_TESTER',    label: 'À tester'    },
  { etat: 'RECLAMATION', label: 'Réclamation' },
  { etat: 'VALIDE',      label: 'Validé'      },
];
const dragTarget = ref(null);

const globalMsg = ref('');
const globalOk = ref(true);
const msg = (m, ok = true) => {
  globalMsg.value = m; globalOk.value = ok;
  setTimeout(() => globalMsg.value = '', 4000);
};

// Confirm dialog state
const confirmDialog = ref({ show: false, message: '', danger: false, onConfirm: () => {} });
const ask = (message, onConfirm, danger = false) => {
  confirmDialog.value = { show: true, message, onConfirm, danger };
};

// Modal réclamation
const reclamationModal = ref({ show: false, raison: '', error: '', pendingEtat: null });

const submitReclamation = async () => {
  if (!reclamationModal.value.raison.trim()) {
    reclamationModal.value.error = 'Veuillez décrire la raison de la réclamation.';
    return;
  }
  reclamationModal.value.show = false;
  await changeStatus('RECLAMATION', reclamationModal.value.raison.trim());
  reclamationModal.value.raison = '';
  reclamationModal.value.error = '';
};

const fetchTicket = async () => {
  try {
    const res = await api.get(`/tickets/${route.params.id}`);
    ticket.value = res.data;
    if (isManager.value && ticket.value.assignment_status !== 'approved') {
      fetchWorkloads();
    }
  } catch (e) {
    console.error(e);
    router.push({ name: 'Tickets', params: { projectId: route.params.projectId } });
  } finally {
    loading.value = false;
  }
};

const fetchWorkloads = async () => {
  try {
    const res = await api.get(`/projects/${ticket.value.project_id}/developers/workload`);
    workloads.value = res.data;
  } catch (e) { console.error('Erreur workloads', e); }
};

const acceptTicket = async () => {
  stateUpdating.value = true;
  try {
    await api.patch(`/tickets/${ticket.value.id}/accept`);
    msg("Ticket accepté avec succès !", true);
    await fetchTicket();
  }
  catch (e) { msg(e.response?.data?.message || "Erreur lors de l'acceptation", false); }
  finally { stateUpdating.value = false; }
};

const rejectTicket = async () => {
  stateUpdating.value = true;
  try {
    await api.patch(`/tickets/${ticket.value.id}/reject`);
    msg("Assignation refusée. Le ticket est réinitialisé.", true);
    await fetchTicket();
    if (isManager.value) fetchWorkloads();
  }
  catch { msg("Erreur lors du refus", false); }
  finally { stateUpdating.value = false; }
};

const reassignTicket = async (devId) => {
  stateUpdating.value = true;
  try {
    await api.patch(`/tickets/${ticket.value.id}/reassign`, { developpeur_id: devId });
    msg("Ticket réassigné avec succès !", true);
    await fetchTicket();
    fetchWorkloads();
  }
  catch { msg("Erreur lors de la réassignation", false); }
  finally { stateUpdating.value = false; }
};

const logTime = async () => {
  if (!timeToAdd.value || timeToAdd.value <= 0) return;
  stateUpdating.value = true;
  try {
    await api.post(`/tickets/${ticket.value.id}/log-time`, { temps_ajoute: timeToAdd.value });
    msg('Temps ajouté avec succès', true);
    timeToAdd.value = null;
    await fetchTicket();
  } catch (e) {
    msg(e.response?.data?.message || 'Erreur lors de l\'ajout de temps', false);
  } finally {
    stateUpdating.value = false;
  }
};

// Timeline Drag & Drop Handlers
const canDragTicket = computed(() => {
  if (!ticket.value) return false;
  const role = currentUser?.role;
  if (isManager.value) return false;
  if (role === 'developpeur') {
    return ticket.value.developpeur_id === currentUser?.id && ticket.value.assignment_status === 'approved';
  }
  if (role === 'testeur') {
    return ticket.value.testeur_id === currentUser?.id && ticket.value.etat === 'A_TESTER';
    // Le testeur ne peut dragger que depuis A_TESTER (vers RECLAMATION ou VALIDE)
  }
  return false;
});

const canTransition = (ticket, toEtat) => {
  const role = currentUser?.role;
  if (isManager.value) return false;
  if (role === 'developpeur') {
    return ['OUVERT', 'EN_COURS', 'A_TESTER'].includes(toEtat);
  }
  if (role === 'testeur') {
    return ['RECLAMATION', 'VALIDE'].includes(toEtat);
  }
  return false;
};

const onDragStart = () => {};
const onDragEnd = () => { dragTarget.value = null; };
const onDragOver = (etat) => {
  if (!isManager.value) dragTarget.value = etat;
};

const onDrop = async (etat) => {
  dragTarget.value = null;
  if (!ticket.value || ticket.value.etat === etat) return;

  if (!canTransition(ticket.value, etat)) {
    msg(`Vous n'êtes pas autorisé à glisser le ticket vers l'état "${etat}".`, false);
    return;
  }

  if (etat === 'RECLAMATION') {
    reclamationModal.value.show = true;
    reclamationModal.value.raison = '';
    reclamationModal.value.error = '';
    return;
  }

  await changeStatus(etat);
};

const changeStatus = async (etat, raisonReclamation = null) => {
  const oldEtat = ticket.value.etat;
  ticket.value.etat = etat;
  stateUpdating.value = true;
  try {
    const payload = { etat };
    if (raisonReclamation) payload.raison_reclamation = raisonReclamation;
    await api.put(`/tickets/${ticket.value.id}/status`, payload);
    msg("Statut du ticket mis à jour", true);
    await fetchTicket();
  } catch (e) {
    ticket.value.etat = oldEtat;
    msg(e.response?.data?.message || 'Erreur lors du déplacement.', false);
  } finally {
    stateUpdating.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value.trim()) return;
  submittingComment.value = true;
  try {
    const res = await api.post('/comments', { ticket_id: ticket.value.id, contenu: newComment.value.trim() });
    if (!ticket.value.comments) ticket.value.comments = [];
    ticket.value.comments.push(res.data);
    newComment.value = '';
    msg("Commentaire ajouté", true);
    await fetchTicket();
    nextTick(() => { if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight; });
  } catch {
    msg('Erreur lors de l\'envoi', false);
  } finally {
    submittingComment.value = false;
  }
};

const startEdit = (comment) => {
  editingCommentId.value = comment.id;
  editContent.value = comment.contenu;
};

const cancelEdit = () => {
  editingCommentId.value = null;
  editContent.value = '';
};

const saveEdit = async (comment) => {
  if (!editContent.value.trim() || editContent.value === comment.contenu) {
    cancelEdit();
    return;
  }
  savingEdit.value = true;
  try {
    const res = await api.put(`/comments/${comment.id}`, { contenu: editContent.value });
    comment.contenu = res.data.contenu;
    editingCommentId.value = null;
    msg("Commentaire modifié", true);
    await fetchTicket();
  } catch {
    msg('Erreur de modification', false);
  } finally {
    savingEdit.value = false;
  }
};

const deleteComment = async (comment) => {
  try {
    await api.delete(`/comments/${comment.id}`);
    ticket.value.comments = ticket.value.comments.filter(c => c.id !== comment.id);
  } catch {
    alert('Erreur de suppression');
  }
};

const formatDescription = (text) => {
  if (!text) return '';
  return text.replace(/\n/g, '<br>')
             .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
             .replace(/_(.*?)_/g, '<em>$1</em>')
             .replace(/- (.*)/g, '<li>$1</li>');
};

const categorieLabel = (cat) => {
  const map = {
    BUG: '🐛 Bug', PERFORMANCE: '⚡ Performance', SECURITE: '🔒 Sécurité',
    UI_UX: '🎨 UI/UX', BASE_DE_DONNEES: '🗄️ Base de données', API: '🔌 API',
    CONFIGURATION: '⚙️ Configuration', AUTRE: '📌 Autre', NON_CLASSE: '❓ Non classé'
  };
  return map[cat] || cat;
};

const reanalyzeAI = async () => {
  aiLoading.value = true;
  try {
    await api.post(`/tickets/${ticket.value.id}/analyze-ai`);
    await fetchTicket();
    msg('Analyse IA mise à jour !', true);
  } catch (e) {
    msg("Erreur lors de l'analyse IA", false);
  } finally {
    aiLoading.value = false;
  }
};

const prioriteClass = (prio) => {
  const map = { CRITIQUE: 'border-red-200 text-red-700 bg-red-50', HAUTE: 'border-orange-200 text-orange-700 bg-orange-50', MOYENNE: 'border-blue-200 text-blue-700 bg-blue-50', BASSE: 'border-emerald-200 text-emerald-700 bg-emerald-50' };
  return map[prio] || 'border-gray-200 text-gray-700 bg-gray-50';
};

const formatTime = (iso) => {
  const d = new Date(iso);
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(fetchTicket);
</script>

<style scoped>
.layout { display: flex; min-height: 100vh; background: #f8fafc; }
.main { flex: 1; padding: 2.5rem; overflow-y: auto; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>