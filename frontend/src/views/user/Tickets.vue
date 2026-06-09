<template>
  <AppLayout fixed>
    <div class="back-link-bar">
      <button type="button" class="ds-back-link" @click="goBack">
        <ArrowLeft :size="14" aria-hidden="true" />
        Retour
      </button>
    </div>

    <PageHeader variant="default" compact class="tickets-header">
      <template #title>{{ projectName || 'Chargement…' }}</template>
      <template #subtitle>{{ tickets.length }} ticket{{ tickets.length !== 1 ? 's' : '' }}</template>
      <template #actions>
        <BaseButton variant="secondary" size="sm" @click="$router.push({ name: 'ProjectDetail', params: { id: projectId } })">
          Infos du projet
        </BaseButton>
        <BaseButton v-if="currentUser?.role === 'testeur'" size="sm" @click="showCreateModal = true">
          <Plus :size="16" aria-hidden="true" />
          Nouveau ticket
        </BaseButton>
      </template>
    </PageHeader>

    <div
      v-if="globalMsg"
      class="ds-toast-inline"
      :class="globalOk ? 'ds-toast-inline--success' : 'ds-toast-inline--error'"
      role="status"
    >
      {{ globalMsg }}
    </div>

    <div v-if="loading" class="tickets-loading">
      <Loader2 class="spin" :size="20" aria-hidden="true" />
      Chargement…
    </div>

    <div v-else class="kanban-shell">
      <div class="kanban-scroll">
        <div class="ds-kanban-board ds-kanban-board--horizontal kanban-board">
          <div
            v-for="col in columns"
            :key="col.etat"
            class="ds-kanban-column ds-kanban-column--white ds-kanban-column--narrow kanban-column"
            :class="`ds-kanban-column--ticket-${col.key}`"
            :data-dragover="dragTarget === col.etat"
            @dragover.prevent="onDragOver(col.etat)"
            @drop.prevent="onDrop(col.etat)"
          >
            <div class="ds-kanban-column-header">
              <h3 class="ds-kanban-column-title">{{ col.label }}</h3>
              <span class="ds-kanban-column-count">{{ ticketsByEtat(col.etat).length }}</span>
            </div>

            <div class="ds-kanban-column-body">
              <article
                v-for="ticket in ticketsByEtat(col.etat)"
                :key="ticket.id"
                class="ds-kanban-card ds-kanban-card--row"
                :class="{ 'ds-kanban-card--dragging': dragging?.id === ticket.id }"
                :draggable="canDrag(ticket)"
                @dragstart="onDragStart(ticket)"
                @dragend="onDragEnd"
                @click="goToTicket(ticket)"
              >
                <div
                  class="ds-kanban-card__strip"
                  :class="`ds-kanban-card__strip--${ticket.priorite.toLowerCase()}`"
                />
                <div class="ds-kanban-card__inner">
                  <div class="ds-kanban-card__top">
                    <BaseBadge :variant="prioBadge(ticket.priorite)" pill>
                      {{ ticket.priorite }}
                    </BaseBadge>
                    <div style="display: flex; align-items: center; gap: 0.375rem">
                      <BaseBadge v-if="ticket.categorie_ia" variant="info" pill>
                        {{ categorieLabel(ticket.categorie_ia) }}
                      </BaseBadge>
                      <span class="ds-kanban-card__id">#{{ ticket.id }}</span>
                    </div>
                  </div>
                  <h3 class="ds-kanban-card__title">{{ ticket.titre }}</h3>
                  <div class="ds-kanban-card__footer">
                    <div
                      v-if="ticket.assignment_status === 'approved' && ticket.developpeur"
                      class="ds-kanban-card__dev"
                    >
                      <span class="ds-kanban-card__dev-av">{{ initials(ticket.developpeur) }}</span>
                      <span>{{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}</span>
                    </div>
                    <div
                      v-else-if="ticket.assignment_status === 'pending' && ticket.proposed_developpeur"
                      class="ds-kanban-card__dev"
                    >
                      <Clock :size="12" aria-hidden="true" />
                      <span>{{ ticket.proposed_developpeur.prenom }}</span>
                    </div>
                    <span v-else class="ds-kanban-card__unassigned">Non assigné</span>
                    <span class="ds-kanban-card__date">{{ formatDate(ticket.created_at) }}</span>
                  </div>
                </div>
              </article>

              <div v-if="dragTarget === col.etat && dragging" class="ds-drop-ghost">Déposer ici</div>
              <div
                v-if="!ticketsByEtat(col.etat).length && dragTarget !== col.etat"
                class="ds-kanban-column-empty"
              >
                Aucun ticket
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showCreateModal" class="ds-modal-backdrop" @click.self="closeModal">
      <div class="ds-modal ds-modal--sm" role="dialog" aria-labelledby="ticket-create-title">
        <template v-if="assignResult">
          <div class="ds-modal__header">
            <h3 id="ticket-create-title" class="ds-modal__title">
              <CheckCircle2 :size="18" aria-hidden="true" />
              Ticket créé
            </h3>
            <ModalCloseBtn @click="closeModal" />
          </div>
          <div class="ds-modal__body confirm-body">

            <!-- ── Résultats IA statiques ──────────────────────────────────── -->
            <div v-if="ticketResult?.categorie_ia || ticketResult?.priorite_ia" class="confirm-ai-result">
              <div class="confirm-ai-result__header">
                <Bot :size="13" aria-hidden="true" />
                Analyse IA
              </div>
              <div class="confirm-ai-result__chips">
                <div class="confirm-ai-chip" :class="`confirm-ai-chip--${(ticketResult.priorite_ia || ticketResult.priorite || 'basse').toLowerCase()}`">
                  <span class="confirm-ai-chip__label">Priorité</span>
                  <span class="confirm-ai-chip__value">{{ priorityLabel(ticketResult.priorite_ia || ticketResult.priorite) }}</span>
                </div>
                <div class="confirm-ai-chip confirm-ai-chip--cat">
                  <span class="confirm-ai-chip__label">Catégorie</span>
                  <span class="confirm-ai-chip__value">{{ ticketResult.categorie_ia ? categorieLabel(ticketResult.categorie_ia) : '—' }}</span>
                </div>
              </div>
            </div>

            <!-- ── Assignation ──────────────────────────────────────────────── -->
            <div class="assign-block" :class="assignResult.success ? 'assign-block--ok' : 'assign-block--warn'">
              <div class="assign-block__icon">
                <RotateCcw v-if="assignResult.is_retour" :size="16" aria-hidden="true" />
                <Clock v-else-if="assignResult.success" :size="16" aria-hidden="true" />
                <AlertTriangle v-else :size="16" aria-hidden="true" />
              </div>
              <div class="assign-block__text">
                <p class="assign-block__title">
                  {{ assignResult.is_retour
                    ? "Assigné d'office (retour)"
                    : assignResult.success
                      ? 'Assignation proposée'
                      : 'Aucun développeur disponible' }}
                </p>
                <p class="assign-block__sub" v-if="assignResult.success">
                  {{ assignResult.dev_prenom }} {{ assignResult.dev_nom }} —
                  {{ assignResult.is_retour ? 'développeur du ticket parent.' : 'en attente de validation admin.' }}
                </p>
                <p class="assign-block__sub" v-else>{{ assignResult.message }}</p>
              </div>
            </div>

          </div>
          <div class="ds-modal__footer">
            <BaseButton variant="secondary" @click="closeModal">Fermer</BaseButton>
          </div>
        </template>

        <template v-else>
          <div class="ds-modal__header">
            <h3 id="ticket-create-title" class="ds-modal__title">Nouveau ticket</h3>
            <ModalCloseBtn @click="closeModal" />
          </div>
          <div class="ds-modal__body">
            <div class="ds-form-stack">
              <div class="ds-field">
                <span class="ds-field__label">Type</span>
                <div class="ds-radio-group">
                  <label class="ds-radio-option" :class="{ 'ds-radio-option--active': form.type === 'NOUVEAU' }">
                    <input v-model="form.type" type="radio" value="NOUVEAU" hidden />
                    <Sparkles :size="14" aria-hidden="true" />
                    Nouveau
                  </label>
                  <label class="ds-radio-option" :class="{ 'ds-radio-option--active': form.type === 'RETOUR' }">
                    <input v-model="form.type" type="radio" value="RETOUR" hidden />
                    <RotateCcw :size="14" aria-hidden="true" />
                    Retour
                  </label>
                </div>
              </div>
              <div v-if="form.type === 'RETOUR'" class="ds-field">
                <label class="ds-field__label ds-field__label--required">Ticket concerné</label>
                <select v-model="form.parent_ticket_id" class="ds-select" style="width: 100%">
                  <option :value="null" disabled>— Sélectionner —</option>
                  <option v-for="t in validParentTickets" :key="t.id" :value="t.id">
                    #{{ t.id }} — {{ t.titre }}
                  </option>
                </select>
              </div>
              <BaseInput
                v-model="form.titre"
                label="Titre"
                required
                placeholder="Décrivez le problème en une phrase"
                @input="onFormInputChange"
              />
              <div class="ds-field">
                <label class="ds-field__label">Description</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="ds-textarea"
                  placeholder="Étapes pour reproduire, contexte…"
                  @input="onFormInputChange"
                />
              </div>

              <!-- ── AI Analysis Panel ───────────────────────────────────── -->
              <AIAnalysisPanel
                :result="aiPreviewResult"
                :is-running="aiAnalyzing"
                :triggered="aiTriggered"
              />

              <BaseInput v-model="form.temps_estime" label="Estimation (h)" type="number" required placeholder="Ex: 2.5" step="0.5" min="0.5" />
              <div class="ds-field">
                <label class="ds-field__label">Pièces jointes</label>
                <label class="ds-file-upload">
                  <Paperclip :size="15" aria-hidden="true" />
                  {{ attachments.length ? `${attachments.length} fichier(s) sélectionné(s)` : 'Choisir des fichiers' }}
                  <input type="file" multiple hidden @change="handleFileUpload" />
                </label>
              </div>
              <p v-if="formError" class="ds-field__error" role="alert">{{ formError }}</p>
            </div>
          </div>
          <div class="ds-modal__footer">
            <BaseButton variant="secondary" @click="closeModal">Annuler</BaseButton>
            <BaseButton :loading="submitting" @click="submitTicket">Créer</BaseButton>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>    <!-- ── Modal réclamation ──────────────────────────────────────────────── -->
    <div v-if="showReclModal" class="ds-modal-backdrop" @click.self="cancelRecl">
      <div class="ds-modal ds-modal--sm" role="dialog" aria-labelledby="recl-title">
        <div class="ds-modal__header">
          <h3 id="recl-title" class="ds-modal__title">
            <AlertTriangle :size="18" aria-hidden="true" />
            Soumettre une réclamation
          </h3>
          <ModalCloseBtn @click="cancelRecl" />
        </div>
        <div class="ds-modal__body">
          <p class="ds-caption" style="margin-bottom: 0.75rem">
            Expliquez pourquoi ce ticket nécessite une réclamation.
          </p>
          <textarea
            v-model="reclRaison"
            class="ds-input"
            rows="4"
            placeholder="Ex : Le bug…"
            style="width:100%; resize:vertical"
            autofocus
          />
        </div>
        <div class="ds-modal__footer">
          <BaseButton variant="secondary" @click="cancelRecl">Annuler</BaseButton>
          <BaseButton variant="primary" :disabled="!reclRaison.trim()" @click="confirmRecl">
            Confirmer la réclamation
          </BaseButton>
        </div>
      </div>
    </div>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';
import {
  AlertTriangle, Bot, CheckCircle2, Clock, Loader2,
  ArrowLeft, Paperclip, Plus, RotateCcw, Sparkles,
} from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseBadge from '../../components/ui/BaseBadge.vue';
import ModalCloseBtn from '../../components/ui/ModalCloseBtn.vue';
import AIAnalysisPanel from '../../components/ui/AIAnalysisPanel.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const projectId = route.params.projectId;
const currentUser = authStore.currentUser;
const isManager = authStore.isManager();

const tickets = ref([]);
const projectName = ref('');
const loading = ref(false);
const dragging = ref(null);
const dragTarget = ref(null);
const showReclModal  = ref(false);
const reclRaison     = ref('');
const pendingRecl    = ref(null);

const cancelRecl = () => {
  showReclModal.value = false;
  reclRaison.value    = '';
  pendingRecl.value   = null;
  fetchTickets();
};

const confirmRecl = async () => {
  if (!pendingRecl.value || !reclRaison.value.trim()) return;
  const { ticket, etat } = pendingRecl.value;
  showReclModal.value = false;
  ticket.etat = etat;
  try {
    await api.put(`/tickets/${ticket.id}/status`, { etat, raison_reclamation: reclRaison.value.trim() });
    msg('Réclamation soumise', true);
  } catch (e) {
    msg(e.response?.data?.message || 'Erreur lors du déplacement.', false);
    await fetchTickets();
  }
  reclRaison.value  = '';
  pendingRecl.value = null;
};
const submitting = ref(false);
const formError = ref('');
const assignResult = ref(null);
const ticketResult = ref(null);
const attachments = ref([]);

// ── AI preview state ──────────────────────────────────────────────────────────
const aiPreviewResult = ref(null);
const aiAnalyzing = ref(false);
const aiTriggered = ref(false);
let aiDebounceTimer = null;

const onFormInputChange = () => {
  const titre = form.value.titre?.trim() || '';
  const desc  = form.value.description?.trim() || '';
  if (!titre && !desc) return; // nothing to analyze yet
  if (!aiTriggered.value) aiTriggered.value = true;

  clearTimeout(aiDebounceTimer);
  // Only fire if title has meaningful content (≥ 6 chars)
  if (titre.length < 6) return;

  aiDebounceTimer = setTimeout(async () => {
    // Don't re-run if content hasn't changed meaningfully
    aiAnalyzing.value = true;
    aiPreviewResult.value = null;
    try {
      const res = await api.post('/ai/analyze', { titre, description: desc });
      aiPreviewResult.value = res.data;
    } catch {
      // Silently fail — AI preview is non-blocking
    } finally {
      aiAnalyzing.value = false;
    }
  }, 900); // 900ms debounce after user stops typing
};

const form = ref({ titre: '', description: '', temps_estime: null, type: 'NOUVEAU', parent_ticket_id: null });

const globalMsg = ref('');
const globalOk = ref(true);
const msg = (m, ok = true) => { globalMsg.value = m; globalOk.value = ok; setTimeout(() => (globalMsg.value = ''), 4000); };

const validParentTickets = computed(() =>
  tickets.value.filter((t) => t.developpeur_id && ['VALIDE', 'A_TESTER', 'RECLAMATION'].includes(t.etat)),
);
const handleFileUpload = (e) => { attachments.value = Array.from(e.target.files); };

const columns = [
  { etat: 'OUVERT',      key: 'open', label: 'À traiter' },
  { etat: 'EN_COURS',    key: 'prog', label: 'En cours' },
  { etat: 'A_TESTER',    key: 'test', label: 'À tester' },
  { etat: 'RECLAMATION', key: 'recl', label: 'Réclamation' },
  { etat: 'VALIDE',      key: 'done', label: 'Validé' },
];

const prioBadge = (p) => ({ BASSE: 'priority-low', MOYENNE: 'priority-medium', HAUTE: 'priority-high', CRITIQUE: 'priority-critical' }[(p || 'BASSE').toUpperCase()] || 'neutral');

const ticketsByEtat = (etat) =>
  tickets.value.filter((t) => t.etat === etat)
    .sort((a, b) => { const o = { CRITIQUE: 0, HAUTE: 1, MOYENNE: 2, BASSE: 3 }; return (o[a.priorite] ?? 9) - (o[b.priorite] ?? 9); });

const onDragStart = (t) => { dragging.value = t; };
const onDragEnd = () => { dragging.value = null; dragTarget.value = null; };
const onDragOver = (etat) => { if (!isManager) dragTarget.value = etat; };

const onDrop = async (etat) => {
  if (isManager) return;
  dragTarget.value = null;
  if (!dragging.value || dragging.value.etat === etat) return;
  const ticket = dragging.value;
  dragging.value = null;
  if (!canTransition(ticket, etat)) { msg('Transition non autorisée.', false); return; }

  if (etat === 'RECLAMATION') {
    pendingRecl.value   = { ticket, etat };
    reclRaison.value    = '';
    showReclModal.value = true;
    return;
  }

  ticket.etat = etat;
  try {
    await api.put(`/tickets/${ticket.id}/status`, { etat, raison_reclamation: null });
    msg('Statut mis à jour', true);
  } catch (e) {
    msg(e.response?.data?.message || 'Erreur lors du déplacement.', false);
    await fetchTickets();
  }
};

const canDrag = (ticket) => {
  const role = currentUser?.role;
  if (isManager) return false;
  if (role === 'developpeur') return ticket.developpeur_id === currentUser?.id && ticket.assignment_status === 'approved';
  if (role === 'testeur') return ticket.created_by === currentUser?.id && ticket.etat === 'A_TESTER';
  return false;
};

const canTransition = (ticket, toEtat) => {
  const role = currentUser?.role;
  if (isManager) return false;
  if (role === 'developpeur') return ticket.developpeur_id === currentUser?.id && ticket.assignment_status === 'approved' && ['OUVERT', 'EN_COURS', 'A_TESTER'].includes(toEtat);
  if (role === 'testeur') return ticket.created_by === currentUser?.id && ticket.etat === 'A_TESTER' && ['RECLAMATION', 'VALIDE'].includes(toEtat);
  return false;
};

const goBack = () => { if (isManager) router.push({ name: 'ProjectManagement' }); else router.push({ name: 'Projects' }); };
const goToTicket = (ticket) => { if (dragging.value) return; router.push({ name: 'TicketDetails', params: { projectId, id: ticket.id } }); };

const fetchProjectInfo = async () => {
  try {
    const res = await api.get(`/projects/${projectId}`);
    projectName.value = res.data.nom;
  } catch {
    try {
      const res = await api.get('/projects');
      const all = res.data.data || res.data;
      const cur = all.find((p) => p.id == projectId);
      if (cur) projectName.value = cur.nom;
    } catch { /* ignore */ }
  }
};

const fetchTickets = async () => {
  loading.value = true;
  try { const res = await api.get(`/projects/${projectId}/tickets`); tickets.value = res.data; }
  catch (e) { console.error(e); }
  finally { loading.value = false; }
};

const submitTicket = async () => {
  if (!form.value.titre) { formError.value = 'Le titre est requis.'; return; }
  if (!form.value.temps_estime || form.value.temps_estime <= 0) { formError.value = 'Une estimation de temps valide est requise.'; return; }
  submitting.value = true; formError.value = '';
  try {
    const formData = new FormData();
    formData.append('titre', form.value.titre);
    const desc = form.value.description?.trim();
    if (desc) formData.append('description', desc);
    formData.append('temps_estime', form.value.temps_estime);
    formData.append('type', form.value.type);
    if (form.value.type === 'RETOUR' && form.value.parent_ticket_id) formData.append('parent_ticket_id', form.value.parent_ticket_id);
    attachments.value.forEach((file, i) => formData.append(`attachments[${i}]`, file));
    const res = await api.post(`/projects/${projectId}/tickets`, formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    await fetchTickets();
    ticketResult.value = res.data.ticket;
    assignResult.value = res.data.auto_assign;
  } catch (e) { formError.value = e.response?.data?.message || 'Erreur lors de la création.'; }
  finally { submitting.value = false; }
};

const closeModal = () => {
  showCreateModal.value = false;
  form.value = { titre: '', description: '', temps_estime: null, type: 'NOUVEAU', parent_ticket_id: null };
  attachments.value = []; formError.value = ''; assignResult.value = null; ticketResult.value = null;
  // Reset AI state
  clearTimeout(aiDebounceTimer);
  aiPreviewResult.value = null;
  aiAnalyzing.value = false;
  aiTriggered.value = false;
};

onMounted(() => { fetchProjectInfo(); fetchTickets(); });

const initials = (u) => ((u?.prenom?.[0] || '') + (u?.nom?.[0] || '')).toUpperCase();
const categorieLabel = (cat) => ({ BUG: 'Bug', PERFORMANCE: 'Perf', SECURITE: 'Sécu', UI_UX: 'UI/UX', BASE_DE_DONNEES: 'BDD', API: 'API', CONFIGURATION: 'Config', AUTRE: 'Autre', NON_CLASSE: '?' }[cat] || cat);
const priorityLabel  = (p) => ({ BASSE: 'Basse', MOYENNE: 'Moyenne', HAUTE: 'Haute', CRITIQUE: 'Critique' }[(p || '').toUpperCase()] || p || '—');
const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '';
</script>

<style scoped>
.back-link-bar {
  padding: 0.75rem 2rem 0.25rem;
  background-color: var(--color-bg-app);
}

.tickets-header {
  padding-top: 0.5rem !important;
  padding-bottom: 0.5rem !important;
}

.back-link-bar .ds-back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  background-color: var(--color-bg-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  box-shadow: var(--shadow-sm);
  transition: all 0.2s ease;
}

.back-link-bar .ds-back-link:hover {
  color: var(--color-brand);
  border-color: var(--color-brand-muted);
  background-color: var(--color-brand-50);
  transform: translateX(-2px);
}
/*
 * Kanban layout.
 * Global fixes applied in components.css:
 *   - ds-kanban-board: removed overflow:hidden
 *   - ds-kanban-board--horizontal: changed align-items to stretch
 *   - ds-kanban-column: removed max-height: calc(100vh - 150px)
 *
 * Here we only need to wire the shell/scroll container heights.
 */

/* Fills all space below AppHeader + PageHeader */
.kanban-shell {
  flex: 1;
  min-height: 0;
  position: relative;
}

/* position:absolute so height:100% resolves correctly inside overflow-x:auto */
.kanban-scroll {
  position: absolute;
  inset: 0;
  overflow-x: hidden;
  overflow-y: hidden;
  padding: 1.5rem 2rem 0;
}

/* Board and columns fill the scroll container height */
.kanban-board {
  height: 100%;
}

.kanban-board.ds-kanban-board--horizontal {
  min-width: 0;
  width: 100%;
}

.kanban-column {
  height: 100%;
}

.kanban-column.ds-kanban-column--narrow {
  width: 0;
  flex: 1 1 0%;
  min-width: 0;
}

/* Loading state */
.tickets-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  padding: 4rem 2rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-muted);
}

.tickets-loading .spin { animation: spin 0.8s linear infinite; color: var(--color-brand); }

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Post-creation confirmation ─────────────────────────────────────────── */
.confirm-body {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.confirm-ai-result {
  padding: 0.875rem 1rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-subtle);
}

.confirm-ai-result__header {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--color-text-muted);
  margin-bottom: 0.625rem;
}

.confirm-ai-result__chips {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.confirm-ai-chip {
  display: flex;
  flex-direction: column;
  gap: 1px;
  padding: 6px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  min-width: 80px;
}

.confirm-ai-chip__label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--color-text-muted);
}

.confirm-ai-chip__value {
  font-size: 13px;
  font-weight: 700;
  color: var(--color-text-primary);
}

.confirm-ai-chip--critique { border-color: var(--color-error-100);   background: var(--color-error-50);   }
.confirm-ai-chip--critique .confirm-ai-chip__value { color: var(--color-error-700); }
.confirm-ai-chip--haute    { border-color: #fed7aa; background: #fff7ed; }
.confirm-ai-chip--haute    .confirm-ai-chip__value { color: #c2410c; }
.confirm-ai-chip--moyenne  { border-color: var(--color-warning-100); background: var(--color-warning-50); }
.confirm-ai-chip--moyenne  .confirm-ai-chip__value { color: var(--color-warning-700); }
.confirm-ai-chip--basse    { border-color: var(--color-border); background: var(--color-bg-subtle); }
.confirm-ai-chip--basse    .confirm-ai-chip__value { color: var(--color-text-secondary); }
.confirm-ai-chip--cat      { border-color: var(--color-brand-muted); background: var(--color-brand-subtle); }
.confirm-ai-chip--cat      .confirm-ai-chip__value { color: var(--color-brand-700); }

.assign-block {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: var(--radius-lg);
  border: 1px solid;
}

.assign-block--ok {
  background: var(--color-brand-subtle);
  border-color: var(--color-brand-muted);
}

.assign-block--warn {
  background: var(--color-warning-50);
  border-color: var(--color-warning-100);
}

.assign-block__icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-md);
}

.assign-block--ok .assign-block__icon {
  background: var(--color-brand-100);
  color: var(--color-brand-700);
}

.assign-block--warn .assign-block__icon {
  background: var(--color-warning-100);
  color: var(--color-warning-700);
}

.assign-block__text {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.assign-block__title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
}

.assign-block__sub {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  line-height: var(--line-height-normal);
}
</style>