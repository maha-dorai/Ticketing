<template>
  <AppLayout fixed>
    <PageHeader variant="compact" back-inline>
      <template #back>
        <button type="button" class="ds-back-link" @click="goBack">
          <ArrowLeft :size="14" aria-hidden="true" />
          Retour
        </button>
      </template>
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

    <!-- Fixed toast — floats over the board -->
    <div
      v-if="globalMsg"
      class="ds-toast-inline"
      :class="globalOk ? 'ds-toast-inline--success' : 'ds-toast-inline--error'"
      role="status"
    >
      {{ globalMsg }}
    </div>

    <!-- Loading state — padded so it breathes below the header -->
    <div v-if="loading" class="tickets-loading">
      <Loader2 class="spin" :size="20" aria-hidden="true" />
      Chargement…
    </div>

    <!-- Kanban board -->
    <div v-else class="kanban-shell">
      <div class="kanban-scroll">
        <div class="ds-kanban-board kanban-board">
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

    <!-- Create ticket modal -->
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
          <div class="ds-modal__body">
            <div class="ds-confirm-block ds-confirm-block--brand">
              <p class="ds-confirm-block__title">
                <Bot :size="14" aria-hidden="true" />
                Analyse automatique
              </p>
              <div class="ds-confirm-grid">
                <div class="ds-confirm-grid__item">
                  <span class="ds-filter-label">Priorité</span>
                  <BaseBadge :variant="prioBadge(ticketResult?.priorite || 'BASSE')" pill>
                    {{ ticketResult?.priorite || '—' }}
                  </BaseBadge>
                </div>
                <div class="ds-confirm-grid__item">
                  <span class="ds-filter-label">Catégorie</span>
                  <BaseBadge variant="neutral" pill>
                    {{ ticketResult?.categorie_ia ? categorieLabel(ticketResult.categorie_ia) : '—' }}
                  </BaseBadge>
                </div>
              </div>
            </div>

            <div class="ds-confirm-block" style="margin-top: 1rem">
              <div v-if="assignResult.success" class="ds-confirm-assign">
                <div class="ds-confirm-assign__icon">
                  <RotateCcw v-if="assignResult.is_retour" :size="18" aria-hidden="true" />
                  <Clock v-else :size="18" aria-hidden="true" />
                </div>
                <div>
                  <p class="ds-callout__title">
                    {{ assignResult.is_retour ? "Assigné d'office (retour)" : 'Assignation proposée' }}
                  </p>
                  <p class="ds-callout__text">
                    {{ assignResult.dev_prenom }} {{ assignResult.dev_nom }} —
                    {{
                      assignResult.is_retour
                        ? 'développeur du ticket parent.'
                        : 'en attente de validation admin.'
                    }}
                  </p>
                </div>
              </div>
              <div v-else class="ds-alert ds-alert--warning">
                <AlertTriangle class="ds-alert__icon" :size="18" aria-hidden="true" />
                <div class="ds-alert__content">
                  <p class="ds-alert__title">Aucun développeur disponible</p>
                  <p>{{ assignResult.message }}</p>
                </div>
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
                  <label
                    class="ds-radio-option"
                    :class="{ 'ds-radio-option--active': form.type === 'NOUVEAU' }"
                  >
                    <input v-model="form.type" type="radio" value="NOUVEAU" hidden />
                    <Sparkles :size="14" aria-hidden="true" />
                    Nouveau
                  </label>
                  <label
                    class="ds-radio-option"
                    :class="{ 'ds-radio-option--active': form.type === 'RETOUR' }"
                  >
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

              <BaseInput v-model="form.titre" label="Titre" required placeholder="Décrivez le problème en une phrase" />
              <div class="ds-field">
                <label class="ds-field__label">Description</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="ds-textarea"
                  placeholder="Étapes pour reproduire, contexte…"
                />
              </div>
              <BaseInput
                v-model="form.temps_estime"
                label="Estimation (h)"
                type="number"
                required
                placeholder="Ex: 2.5"
                step="0.5"
                min="0.5"
              />

              <div class="ds-field">
                <label class="ds-field__label">Pièces jointes</label>
                <label class="ds-file-upload">
                  <Paperclip :size="15" aria-hidden="true" />
                  {{
                    attachments.length
                      ? `${attachments.length} fichier(s) sélectionné(s)`
                      : 'Choisir des fichiers'
                  }}
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
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter, useRoute } from 'vue-router';
import api from '../../services/api';
import {
  AlertTriangle,
  Bot,
  CheckCircle2,
  Clock,
  Loader2,
  ArrowLeft,
  Paperclip,
  Plus,
  RotateCcw,
  Sparkles,
} from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseBadge from '../../components/ui/BaseBadge.vue';
import ModalCloseBtn from '../../components/ui/ModalCloseBtn.vue';

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

const showCreateModal = ref(false);
const submitting = ref(false);
const formError = ref('');
const assignResult = ref(null);
const ticketResult = ref(null);
const attachments = ref([]);

const form = ref({
  titre: '',
  description: '',
  temps_estime: null,
  type: 'NOUVEAU',
  parent_ticket_id: null,
});

const globalMsg = ref('');
const globalOk = ref(true);
const msg = (m, ok = true) => {
  globalMsg.value = m;
  globalOk.value = ok;
  setTimeout(() => (globalMsg.value = ''), 4000);
};

const validParentTickets = computed(() =>
  tickets.value.filter((t) => t.developpeur_id && ['VALIDE', 'A_TESTER', 'RECLAMATION'].includes(t.etat)),
);

const handleFileUpload = (e) => {
  attachments.value = Array.from(e.target.files);
};

const columns = [
  { etat: 'OUVERT', key: 'open', label: 'À traiter' },
  { etat: 'EN_COURS', key: 'prog', label: 'En cours' },
  { etat: 'A_TESTER', key: 'test', label: 'À tester' },
  { etat: 'RECLAMATION', key: 'recl', label: 'Réclamation' },
  { etat: 'VALIDE', key: 'done', label: 'Validé' },
];

const prioBadge = (p) =>
  ({
    BASSE: 'priority-low',
    MOYENNE: 'priority-medium',
    HAUTE: 'priority-high',
    CRITIQUE: 'priority-critical',
  }[(p || 'BASSE').toUpperCase()] || 'neutral');

const ticketsByEtat = (etat) =>
  tickets.value
    .filter((t) => t.etat === etat)
    .sort((a, b) => {
      const o = { CRITIQUE: 0, HAUTE: 1, MOYENNE: 2, BASSE: 3 };
      return (o[a.priorite] ?? 9) - (o[b.priorite] ?? 9);
    });

const onDragStart = (t) => { dragging.value = t; };
const onDragEnd = () => { dragging.value = null; dragTarget.value = null; };
const onDragOver = (etat) => { if (!isManager) dragTarget.value = etat; };

const onDrop = async (etat) => {
  if (isManager) return;
  dragTarget.value = null;
  if (!dragging.value || dragging.value.etat === etat) return;
  const ticket = dragging.value;
  dragging.value = null;
  if (!canTransition(ticket, etat)) {
    msg('Transition non autorisée.', false);
    return;
  }
  ticket.etat = etat;
  try {
    await api.put(`/tickets/${ticket.id}/status`, { etat });
    msg('Statut mis à jour', true);
  } catch (e) {
    msg(e.response?.data?.message || 'Erreur lors du déplacement.', false);
    await fetchTickets();
  }
};

const canDrag = (ticket) => {
  const role = currentUser?.role;
  if (isManager) return false;
  if (role === 'developpeur') {
    return ticket.developpeur_id === currentUser?.id && ticket.assignment_status === 'approved';
  }
  if (role === 'testeur') return ticket.testeur_id === currentUser?.id && ticket.etat === 'A_TESTER';
  return false;
};

const canTransition = (ticket, toEtat) => {
  const role = currentUser?.role;
  if (isManager) return false;
  if (role === 'developpeur') {
    return (
      ticket.developpeur_id === currentUser?.id &&
      ticket.assignment_status === 'approved' &&
      ['OUVERT', 'EN_COURS', 'A_TESTER'].includes(toEtat)
    );
  }
  if (role === 'testeur') {
    return ticket.testeur_id === currentUser?.id && ticket.etat === 'A_TESTER' && ['RECLAMATION', 'VALIDE'].includes(toEtat);
  }
  return false;
};

const goBack = () => {
  if (isManager) router.push({ name: 'ProjectManagement' });
  else router.push({ name: 'Projects' });
};
const goToTicket = (ticket) => {
  if (dragging.value) return;
  router.push({ name: 'TicketDetails', params: { projectId, id: ticket.id } });
};

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
  try {
    const res = await api.get(`/projects/${projectId}/tickets`);
    tickets.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const submitTicket = async () => {
  if (!form.value.titre) { formError.value = 'Le titre est requis.'; return; }
  if (!form.value.temps_estime || form.value.temps_estime <= 0) {
    formError.value = 'Une estimation de temps valide est requise.'; return;
  }
  submitting.value = true;
  formError.value = '';
  try {
    const formData = new FormData();
    formData.append('titre', form.value.titre);
    const desc = form.value.description?.trim();
    if (desc) formData.append('description', desc);
    formData.append('temps_estime', form.value.temps_estime);
    formData.append('type', form.value.type);
    if (form.value.type === 'RETOUR' && form.value.parent_ticket_id) {
      formData.append('parent_ticket_id', form.value.parent_ticket_id);
    }
    attachments.value.forEach((file, i) => formData.append(`attachments[${i}]`, file));
    const res = await api.post(`/projects/${projectId}/tickets`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await fetchTickets();
    ticketResult.value = res.data.ticket;
    assignResult.value = res.data.auto_assign;
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la création.';
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  form.value = { titre: '', description: '', temps_estime: null, type: 'NOUVEAU', parent_ticket_id: null };
  attachments.value = [];
  formError.value = '';
  assignResult.value = null;
  ticketResult.value = null;
};

onMounted(() => {
  fetchProjectInfo();
  fetchTickets();
});

const initials = (u) => ((u?.prenom?.[0] || '') + (u?.nom?.[0] || '')).toUpperCase();
const categorieLabel = (cat) => {
  const map = {
    BUG: 'Bug', PERFORMANCE: 'Perf', SECURITE: 'Sécu', UI_UX: 'UI/UX',
    BASE_DE_DONNEES: 'BDD', API: 'API', CONFIGURATION: 'Config', AUTRE: 'Autre', NON_CLASSE: '?',
  };
  return map[cat] || cat;
};
const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '';
</script>

<style scoped>
/*
 * Kanban layout — self-contained, no dependency on ds-page-body / ds-kanban-scroll globals.
 *
 * Chrome heights (measured from source):
 *   AppHeader  (AppHeader.vue scoped) : 64px
 *   PageHeader compact                : ~92px  (padding 1.5rem + content + 1.25rem)
 *   Total                             : 156px  → use 158px with 2px safety
 *
 * These classes are LOCAL to this file so there is zero risk of specificity conflict.
 */

/* Full-height shell that fills whatever .main gives us */
.kanban-shell {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow: hidden;
}

/* Horizontal scroll wrapper — fills the shell exactly */
.kanban-scroll {
  flex: 1;
  min-height: 0;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 1.5rem 2rem;
  /* Remove bottom padding so columns reach the edge cleanly */
  padding-bottom: 0;
}

/* Board row — columns laid out horizontally, stretch to full height */
.kanban-board {
  display: flex !important;
  flex-direction: row !important;
  gap: 1rem;
  align-items: stretch !important;  /* columns all same height */
  min-width: max-content;
  height: 100%;
  min-height: calc(100vh - 158px);
}

/* Individual column — fills board height, content scrolls inside */
.kanban-column {
  max-height: none !important;   /* remove the global max-height calc */
  height: 100%;
  min-height: calc(100vh - 182px); /* 158 + 24px top padding of scroll */
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

.tickets-loading .spin {
  animation: spin 0.8s linear infinite;
  color: var(--color-brand);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>