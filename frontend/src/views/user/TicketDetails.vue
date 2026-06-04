<template>
  <AppLayout>
    <div class="ds-detail-page">

      <!-- Loading -->
      <div v-if="loading" class="ds-loading-state">
        <Loader2 class="spin" :size="20" aria-hidden="true" />
        Chargement du ticket…
      </div>

      <template v-else-if="ticket">

        <!-- Confirm dialog -->
        <div v-if="confirmDialog.show" class="ds-modal-backdrop">
          <div class="ds-modal ds-modal--sm" role="dialog">
            <div class="ds-modal__body" style="text-align: center">
              <p class="ds-callout__title" style="margin-bottom: 1.5rem">{{ confirmDialog.message }}</p>
              <div style="display: flex; justify-content: center; gap: 0.75rem">
                <BaseButton variant="secondary" @click="confirmDialog.show = false">Annuler</BaseButton>
                <BaseButton
                  :variant="confirmDialog.danger ? 'danger' : 'primary'"
                  @click="confirmDialog.onConfirm(); confirmDialog.show = false"
                >
                  Confirmer
                </BaseButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Reclamation modal -->
        <div v-if="reclamationModal.show" class="ds-modal-backdrop" @click.self="reclamationModal.show = false">
          <div class="ds-modal" role="dialog">
            <div class="ds-modal__header">
              <h3 class="ds-modal__title">
                <AlertTriangle :size="18" aria-hidden="true" />
                Raison de la réclamation
              </h3>
              <button
                type="button"
                class="ds-modal__close"
                aria-label="Fermer"
                @click="reclamationModal.show = false; reclamationModal.raison = ''; reclamationModal.error = ''"
              >
                &times;
              </button>
            </div>
            <div class="ds-modal__body">
              <p class="ds-callout__text" style="margin-bottom: 1rem">
                Décrivez ce qui ne va pas avec la résolution proposée. Le développeur recevra ce message.
              </p>
              <textarea
                v-model="reclamationModal.raison"
                rows="4"
                class="ds-textarea"
                placeholder="Ex: Le bug est toujours présent sur la page de connexion…"
                autofocus
              />
              <p v-if="reclamationModal.error" class="ds-field__error">{{ reclamationModal.error }}</p>
            </div>
            <div class="ds-modal__footer">
              <BaseButton
                variant="secondary"
                @click="reclamationModal.show = false; reclamationModal.raison = ''; reclamationModal.error = ''"
              >
                Annuler
              </BaseButton>
              <BaseButton variant="primary" @click="submitReclamation">Envoyer la réclamation</BaseButton>
            </div>
          </div>
        </div>

        <!-- ── Page header ─────────────────────────────────────────────── -->
        <div class="td-header">
          <div class="td-header__nav">
            <button
              type="button"
              class="ds-back-link"
              @click="$router.push({ name: 'Tickets', params: { projectId: route.params.projectId } })"
            >
              <ChevronLeft :size="16" aria-hidden="true" />
              Retour aux tickets
            </button>
            <span class="td-header__id">#{{ ticket.id }}</span>
          </div>

          <div class="td-header__title-row">
            <h1 class="td-title">{{ ticket.titre }}</h1>
            <div class="td-header__badges">
              <BaseBadge :variant="statusBadge(ticket.etat)" pill>{{ etatLabel(ticket.etat) }}</BaseBadge>
              <BaseBadge :variant="prioBadge(ticket.priorite)" pill>
                <Flag :size="11" aria-hidden="true" />
                {{ ticket.priorite }}
              </BaseBadge>
            </div>
          </div>

          <div class="td-header__meta">
            <span class="td-meta-item">
              <FolderKanban :size="13" aria-hidden="true" />
              {{ ticket.project?.nom }}
            </span>
            <span class="td-meta-sep" aria-hidden="true">·</span>
            <span class="td-meta-item">
              <Users :size="13" aria-hidden="true" />
              {{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}
            </span>
            <span class="td-meta-sep" aria-hidden="true">·</span>
            <span class="td-meta-item">
              <Calendar :size="13" aria-hidden="true" />
              {{ formatDate(ticket.created_at) }}
            </span>
            <span v-if="stateUpdating" class="td-meta-syncing">
              <Loader2 :size="12" class="spin" aria-hidden="true" />
              Synchronisation…
            </span>
          </div>
        </div>

        <!-- ── Main body ───────────────────────────────────────────────── -->
        <div class="ds-detail-layout">

          <!-- Left column: pipeline + AI + description + attachments + comments -->
          <div class="ds-detail-main">

            <!-- Status pipeline -->
            <BaseCard>
              <template #header>
                <h2 class="ds-section-label">
                  <Activity :size="14" aria-hidden="true" />
                  Pipeline d'état
                </h2>
              </template>
              <div class="ds-pipeline">
                <div
                  v-for="col in columns"
                  :key="col.etat"
                  class="ds-pipeline__step"
                  :class="{
                    'ds-pipeline__step--active': ticket.etat === col.etat,
                    'ds-pipeline__step--dragover': dragTarget === col.etat,
                  }"
                  @dragover.prevent="onDragOver(col.etat)"
                  @drop.prevent="onDrop(col.etat)"
                >
                  <span class="ds-pipeline__label">{{ col.label }}</span>
                  <div
                    v-if="ticket.etat === col.etat"
                    class="ds-pipeline__chip"
                    :class="canDragTicket ? 'ds-pipeline__chip--draggable' : 'ds-pipeline__chip--static'"
                    :draggable="canDragTicket"
                    @dragstart="onDragStart"
                    @dragend="onDragEnd"
                  >
                    <GripVertical v-if="canDragTicket" :size="14" aria-hidden="true" />
                    <Lock v-else :size="14" aria-hidden="true" />
                    {{ canDragTicket ? 'Glisser' : 'Actuel' }}
                  </div>
                </div>
              </div>
              <p v-if="!canDragTicket" class="ds-pipeline__hint">
                {{
                  isManager
                    ? 'En tant que manager, vous êtes en lecture seule sur le flux.'
                    : "Vous n'avez pas les droits de modifier cet état ou le ticket ne vous est pas assigné."
                }}
              </p>
            </BaseCard>

            <!-- AI metadata (catégorie + priorité) -->
            <div
              v-if="ticket.categorie_ia || ticket.priorite_ia"
              class="ds-ai-panel"
            >
              <div class="ds-ai-panel__head">
                <Bot :size="20" aria-hidden="true" />
                <div style="flex: 1">
                  <h3 class="ds-ai-panel__title">Analyse automatique</h3>
                  <p class="ds-ai-panel__sub">Générée à la création du ticket</p>
                </div>
              </div>
              <div class="ds-ai-tags">
                <div v-if="ticket.categorie_ia" class="ds-ai-tag">
                  <span class="ds-ai-tag__label">Catégorie</span>
                  <span>{{ categorieLabel(ticket.categorie_ia) }}</span>
                </div>
                <div v-if="ticket.priorite_ia" class="ds-ai-tag">
                  <span class="ds-ai-tag__label">Priorité suggérée</span>
                  <BaseBadge :variant="prioBadge(ticket.priorite_ia)" pill>{{ ticket.priorite_ia }}</BaseBadge>
                  <span v-if="ticket.priorite_ia !== ticket.priorite" class="ds-caption">
                    (actuelle : {{ ticket.priorite }})
                  </span>
                </div>
              </div>
            </div>

            <!-- AI Solution Assistant — développeur assigné uniquement -->
            <AIAssistantPanel
              v-if="currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser?.id"
              :ticket-id="ticket.id"
              :solution="ticket.solution_ia"
            />

            <!-- Description -->
            <BaseCard>
              <template #header>
                <h2 class="ds-section-label">
                  <FileText :size="14" aria-hidden="true" />
                  Description
                </h2>
              </template>

              <div
                v-if="ticket.description && ticket.description.trim()"
                class="ds-description"
                v-html="formatDescription(ticket.description)"
              />
              <p v-else class="ds-description ds-description--empty">
                <FileText :size="16" aria-hidden="true" />
                Aucune description fournie.
              </p>
            </BaseCard>

            <!-- Attachments -->
            <BaseCard v-if="ticket.attachments?.length">
              <template #header>
                <h2 class="ds-section-label">
                  <Paperclip :size="14" aria-hidden="true" />
                  Pièces jointes ({{ ticket.attachments.length }})
                </h2>
              </template>
              <div class="ds-attachment-grid">
                <a
                  v-for="att in ticket.attachments"
                  :key="att.id"
                  :href="storageAssetUrl(att.file_path)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="ds-attachment-link"
                >
                  <File :size="18" aria-hidden="true" />
                  <span>{{ att.file_name }}</span>
                </a>
              </div>
            </BaseCard>

            <!-- Comments -->
            <div class="ds-card ds-comments" style="padding: 0; overflow: hidden">
              <div class="ds-comments__header">
                <h2 class="ds-section-label">
                  <MessageSquare :size="16" aria-hidden="true" />
                  Commentaires ({{ ticket.comments?.length || 0 }})
                </h2>
              </div>

              <div ref="chatBox" class="ds-comments__list">
                <p v-if="!ticket.comments?.length" class="ds-comments__empty">
                  Aucun commentaire pour le moment.
                </p>

                <div
                  v-for="comment in ticket.comments"
                  :key="comment.id"
                  class="ds-comment group"
                  :class="{ 'ds-comment--own': comment.user_id === currentUser.id }"
                >
                  <div class="ds-comment__head">
                    <span class="ds-comment__author">
                      {{ comment.user?.prenom }} {{ comment.user?.nom }}
                    </span>
                    <span class="ds-comment__time">{{ formatTime(comment.created_at) }}</span>
                    <span v-if="comment.user_id === currentUser.id" class="ds-comment__actions">
                      <button
                        type="button"
                        class="ds-back-link"
                        aria-label="Modifier le commentaire"
                        @click="startEdit(comment)"
                      >
                        <Pencil :size="14" aria-hidden="true" />
                      </button>
                      <button
                        type="button"
                        class="ds-back-link"
                        aria-label="Supprimer le commentaire"
                        @click="ask('Supprimer ce commentaire ?', () => deleteComment(comment), true)"
                      >
                        <Trash2 :size="14" aria-hidden="true" />
                      </button>
                    </span>
                  </div>

                  <div
                    v-if="editingCommentId !== comment.id"
                    class="ds-comment__bubble"
                    :class="comment.user_id === currentUser.id ? 'ds-comment__bubble--own' : 'ds-comment__bubble--other'"
                  >
                    {{ comment.contenu }}
                  </div>

                  <div v-else class="ds-form-stack" style="width: 100%">
                    <textarea
                      v-model="editContent"
                      rows="2"
                      class="ds-textarea"
                      @keydown.esc="cancelEdit"
                    />
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end">
                      <BaseButton variant="ghost" size="sm" @click="cancelEdit">Annuler</BaseButton>
                      <BaseButton size="sm" :loading="savingEdit" :disabled="!editContent.trim()" @click="saveEdit(comment)">
                        Enregistrer
                      </BaseButton>
                    </div>
                  </div>
                </div>
              </div>

              <div class="ds-comments__composer">
                <textarea
                  v-model="newComment"
                  rows="2"
                  class="ds-textarea"
                  placeholder="Ajouter un commentaire…"
                  @keydown.enter.prevent="submitComment"
                />
                <BaseButton :disabled="!newComment.trim() || submittingComment" @click="submitComment">
                  Envoyer
                </BaseButton>
              </div>
            </div>
          </div>

          <!-- ── Sidebar ──────────────────────────────────────────────── -->
          <aside class="ds-detail-sidebar">

            <!-- Ticket info card -->
            <BaseCard>
              <template #header>
                <h3 class="ds-section-label">Informations</h3>
              </template>

              <dl class="td-info-list">
                <!-- Assignee -->
                <div class="td-info-row">
                  <dt class="td-info-label">
                    <User :size="13" aria-hidden="true" />
                    Assigné à
                  </dt>
                  <dd class="td-info-value">
                    <span
                      v-if="ticket.assignment_status === 'approved' && ticket.developpeur"
                      class="ds-assign-badge ds-assign-badge--approved"
                    >
                      <Code2 :size="14" aria-hidden="true" />
                      {{ ticket.developpeur.prenom }} {{ ticket.developpeur.nom }}
                    </span>
                    <span
                      v-else-if="ticket.assignment_status === 'pending' && ticket.proposed_developpeur"
                      class="ds-assign-badge ds-assign-badge--pending"
                    >
                      <Clock :size="14" aria-hidden="true" />
                      Proposé : {{ ticket.proposed_developpeur.prenom }}
                    </span>
                    <span v-else class="ds-caption">Non assigné</span>
                  </dd>
                </div>

                <!-- Reporter -->
                <div class="td-info-row">
                  <dt class="td-info-label">
                    <Users :size="13" aria-hidden="true" />
                    Rapporteur
                  </dt>
                  <dd class="td-info-value">
                    {{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}
                  </dd>
                </div>

                <!-- Project -->
                <div class="td-info-row">
                  <dt class="td-info-label">
                    <FolderKanban :size="13" aria-hidden="true" />
                    Projet
                  </dt>
                  <dd class="td-info-value td-info-value--truncate">{{ ticket.project?.nom }}</dd>
                </div>

                <!-- Category -->
                <div v-if="ticket.categorie_ia" class="td-info-row">
                  <dt class="td-info-label">
                    <Tags :size="13" aria-hidden="true" />
                    Catégorie
                  </dt>
                  <dd class="td-info-value">{{ categorieLabel(ticket.categorie_ia) }}</dd>
                </div>

                <!-- Created -->
                <div class="td-info-row">
                  <dt class="td-info-label">
                    <Calendar :size="13" aria-hidden="true" />
                    Créé le
                  </dt>
                  <dd class="td-info-value">{{ formatDate(ticket.created_at) }}</dd>
                </div>

                <!-- Updated -->
                <div class="td-info-row">
                  <dt class="td-info-label">
                    <Clock :size="13" aria-hidden="true" />
                    Modifié le
                  </dt>
                  <dd class="td-info-value">{{ formatDate(ticket.updated_at) }}</dd>
                </div>
              </dl>

              <!-- Reclamation reason -->
              <div
                v-if="ticket.etat === 'RECLAMATION' && ticket.raison_reclamation"
                class="td-reclamation"
              >
                <p class="ds-section-label" style="margin-bottom: 0.5rem">
                  <AlertTriangle :size="12" aria-hidden="true" />
                  Raison de la réclamation
                </p>
                <div class="ds-alert ds-alert--warning">
                  <p>{{ ticket.raison_reclamation }}</p>
                </div>
              </div>
            </BaseCard>

            <!-- Time tracking card -->
            <BaseCard v-if="ticket.temps_estime">
              <template #header>
                <h3 class="ds-section-label">
                  <Clock :size="14" aria-hidden="true" />
                  Suivi du temps
                </h3>
              </template>

              <div class="ds-progress-block__header">
                <span class="ds-progress-block__value">
                  {{ ticket.temps_passe || 0 }}<span>h</span>
                </span>
                <span class="ds-progress-block__target">/ {{ ticket.temps_estime }}h</span>
              </div>
              <div class="ds-progress">
                <div
                  class="ds-progress__bar"
                  :class="{ 'ds-progress__bar--over': ticket.temps_passe > ticket.temps_estime }"
                  :style="{
                    width: Math.min(100, ((ticket.temps_passe || 0) / ticket.temps_estime) * 100) + '%',
                  }"
                />
              </div>

              <div
                v-if="
                  currentUser?.role === 'developpeur' &&
                  ticket.assignment_status === 'approved' &&
                  ticket.developpeur_id === currentUser.id &&
                  ticket.etat !== 'FERME'
                "
                class="ds-form-stack"
                style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--color-border)"
              >
                <label class="ds-field__label">Ajouter des heures</label>
                <div style="display: flex; gap: 0.5rem">
                  <input
                    v-model="timeToAdd"
                    type="number"
                    step="0.5"
                    min="0.5"
                    class="ds-input"
                    placeholder="Ex: 1.5"
                  />
                  <BaseButton :disabled="!timeToAdd || stateUpdating" @click="logTime">OK</BaseButton>
                </div>
              </div>
            </BaseCard>

            <!-- Actions card -->
            <BaseCard>
              <template #header>
                <h3 class="ds-section-label">Actions requises</h3>
              </template>

              <!-- Manager: pending assignment -->
              <div
                v-if="isManager && ticket.assignment_status === 'pending' && ticket.etat === 'OUVERT'"
                class="ds-form-stack"
              >
                <div class="ds-alert ds-alert--warning">
                  <p class="ds-alert__title">Développeur proposé</p>
                  <p>
                    {{ ticket.proposed_developpeur?.prenom }} {{ ticket.proposed_developpeur?.nom }}
                  </p>
                </div>
                <BaseButton block @click="ask('Valider cette assignation et notifier le développeur ?', acceptTicket)">
                  <CheckCircle2 :size="16" aria-hidden="true" />
                  Valider
                </BaseButton>
                <BaseButton
                  block
                  variant="danger-outline"
                  @click="ask('Refuser cette assignation ?', rejectTicket, true)"
                >
                  <XCircle :size="16" aria-hidden="true" />
                  Refuser
                </BaseButton>
              </div>

              <!-- Manager: manual assignment -->
              <div
                v-if="isManager && ticket.assignment_status !== 'approved' && ticket.etat === 'OUVERT'"
                class="ds-form-stack"
                style="margin-top: 1rem"
              >
                <p class="ds-filter-label">Assigner manuellement</p>
                <p v-if="workloads.length === 0" class="ds-caption">Aucun développeur disponible</p>
                <div v-else class="ds-workload-list">
                  <div v-for="dev in workloads" :key="dev.id" class="ds-workload-item">
                    <div>
                      <div class="ds-member-card__name">{{ dev.prenom }} {{ dev.nom }}</div>
                      <div class="ds-caption">{{ dev.active_tickets_count }} actifs</div>
                    </div>
                    <BaseButton
                      size="sm"
                      variant="secondary"
                      @click="ask(`Assigner ce ticket à ${dev.prenom} ${dev.nom} ?`, () => reassignTicket(dev.id))"
                    >
                      Assigner
                    </BaseButton>
                  </div>
                </div>
              </div>

              <!-- No actions -->
              <div
                v-if="
                  !isManager &&
                  !canDragTicket &&
                  !(currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser.id && ticket.etat !== 'FERME') &&
                  !(currentUser?.role === 'testeur' && ticket.testeur_id === currentUser.id)
                "
                class="ds-empty-state"
                style="padding: 2rem 0"
              >
                <Coffee :size="32" aria-hidden="true" />
                <p class="ds-caption">Aucune action requise de votre part sur ce ticket.</p>
              </div>

              <p v-if="stateUpdating" class="ds-caption" style="text-align: center; margin-top: 0.5rem">
                Synchronisation…
              </p>
            </BaseCard>
          </aside>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import { storageAssetUrl } from '../../utils/storageUrl';
import {
  Activity,
  AlertTriangle,
  Bot,
  Calendar,
  ChevronLeft,
  CheckCircle2,
  Clock,
  Code2,
  Coffee,
  File,
  FileText,
  Flag,
  FolderKanban,
  GripVertical,
  Loader2,
  Lock,
  MessageSquare,
  Paperclip,
  Pencil,
  Tags,
  Ticket,
  Trash2,
  User,
  Users,
  XCircle,
} from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseBadge from '../../components/ui/BaseBadge.vue';
import BaseCard from '../../components/ui/BaseCard.vue';
import AIAssistantPanel from '../../components/ui/AIAssistantPanel.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const currentUser = authStore.currentUser;
const isManager = computed(() => ['chef_de_projet', 'admin'].includes(currentUser?.role));

const ticket = ref(null);
const loading = ref(true);
const stateUpdating = ref(false);
const chatBox = ref(null);
const workloads = ref([]);
const timeToAdd = ref(null);

const newComment = ref('');
const submittingComment = ref(false);
const editingCommentId = ref(null);
const editContent = ref('');
const savingEdit = ref(false);

const columns = [
  { etat: 'OUVERT', label: 'À traiter' },
  { etat: 'EN_COURS', label: 'En cours' },
  { etat: 'A_TESTER', label: 'À tester' },
  { etat: 'RECLAMATION', label: 'Réclamation' },
  { etat: 'VALIDE', label: 'Validé' },
];
const dragTarget = ref(null);

const confirmDialog = ref({ show: false, message: '', danger: false, onConfirm: () => {} });
const ask = (message, onConfirm, danger = false) => {
  confirmDialog.value = { show: true, message, onConfirm, danger };
};

const reclamationModal = ref({ show: false, raison: '', error: '', pendingEtat: null });

const prioBadge = (p) =>
  ({
    CRITIQUE: 'priority-critical',
    HAUTE: 'priority-high',
    MOYENNE: 'priority-medium',
    BASSE: 'priority-low',
  }[p] || 'neutral');

const statusBadge = (etat) =>
  ({
    OUVERT: 'open',
    EN_COURS: 'progress',
    A_TESTER: 'pending',
    RECLAMATION: 'warning',
    VALIDE: 'success',
    FERME: 'closed',
  }[etat] || 'neutral');

const etatLabel = (etat) =>
  ({
    OUVERT: 'À traiter',
    EN_COURS: 'En cours',
    A_TESTER: 'À tester',
    RECLAMATION: 'Réclamation',
    VALIDE: 'Validé',
    FERME: 'Fermé',
  }[etat] || etat);

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
  } catch (e) {
    console.error('Erreur workloads', e);
  }
};

const acceptTicket = async () => {
  stateUpdating.value = true;
  try {
    await api.patch(`/tickets/${ticket.value.id}/accept`);
    await fetchTicket();
  } catch (e) {
    console.error(e);
  } finally {
    stateUpdating.value = false;
  }
};

const rejectTicket = async () => {
  stateUpdating.value = true;
  try {
    await api.patch(`/tickets/${ticket.value.id}/reject`);
    await fetchTicket();
    if (isManager.value) fetchWorkloads();
  } catch {
    console.error('Erreur refus');
  } finally {
    stateUpdating.value = false;
  }
};

const reassignTicket = async (devId) => {
  stateUpdating.value = true;
  try {
    await api.patch(`/tickets/${ticket.value.id}/reassign`, { developpeur_id: devId });
    await fetchTicket();
    fetchWorkloads();
  } catch {
    console.error('Erreur réassignation');
  } finally {
    stateUpdating.value = false;
  }
};

const logTime = async () => {
  if (!timeToAdd.value || timeToAdd.value <= 0) return;
  stateUpdating.value = true;
  try {
    await api.post(`/tickets/${ticket.value.id}/log-time`, { temps_ajoute: timeToAdd.value });
    timeToAdd.value = null;
    await fetchTicket();
  } catch (e) {
    console.error(e);
  } finally {
    stateUpdating.value = false;
  }
};

const canDragTicket = computed(() => {
  if (!ticket.value) return false;
  const role = currentUser?.role;
  if (isManager.value) return false;
  if (role === 'developpeur') {
    return ticket.value.developpeur_id === currentUser?.id && ticket.value.assignment_status === 'approved';
  }
  if (role === 'testeur') {
    return ticket.value.testeur_id === currentUser?.id && ticket.value.etat === 'A_TESTER';
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
const onDragOver = (etat) => { if (!isManager.value) dragTarget.value = etat; };

const onDrop = async (etat) => {
  dragTarget.value = null;
  if (!ticket.value || ticket.value.etat === etat) return;
  if (!canTransition(ticket.value, etat)) return;
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
    await fetchTicket();
  } catch (e) {
    ticket.value.etat = oldEtat;
    console.error(e);
  } finally {
    stateUpdating.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value.trim()) return;
  submittingComment.value = true;
  try {
    await api.post('/comments', { ticket_id: ticket.value.id, contenu: newComment.value.trim() });
    newComment.value = '';
    await fetchTicket();
    nextTick(() => {
      if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight;
    });
  } catch {
    console.error('Erreur envoi commentaire');
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
    await fetchTicket();
  } catch {
    console.error('Erreur modification');
  } finally {
    savingEdit.value = false;
  }
};

const deleteComment = async (comment) => {
  try {
    await api.delete(`/comments/${comment.id}`);
    ticket.value.comments = ticket.value.comments.filter((c) => c.id !== comment.id);
  } catch {
    console.error('Erreur suppression');
  }
};

const formatDescription = (text) => {
  if (!text) return '';
  return text
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/_(.*?)_/g, '<em>$1</em>')
    .replace(/- (.*)/g, '<li>$1</li>');
};

const categorieLabel = (cat) => {
  const map = {
    BUG: 'Bug', PERFORMANCE: 'Performance', SECURITE: 'Sécurité', UI_UX: 'UI/UX',
    BASE_DE_DONNEES: 'Base de données', API: 'API', CONFIGURATION: 'Configuration',
    AUTRE: 'Autre', NON_CLASSE: 'Non classé',
  };
  return map[cat] || cat;
};

const formatTime = (iso) => {
  const d = new Date(iso);
  return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
};

const formatDate = (iso) => {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

onMounted(fetchTicket);
</script>

<style scoped>
/* ── Page-level header ───────────────────────────────────────────────── */
.td-header {
  margin-bottom: var(--space-8);
}

.td-header__nav {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  margin-bottom: var(--space-5);
}

.td-header__id {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-muted);
  background: var(--color-bg-subtle);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  padding: 0.125rem 0.625rem;
  letter-spacing: 0.04em;
}

.td-header__title-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: var(--space-4);
  margin-bottom: var(--space-3);
}

.td-title {
  flex: 1;
  min-width: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-extrabold);
  line-height: var(--line-height-tight);
  letter-spacing: var(--letter-spacing-tight);
  color: var(--color-text-primary);
  margin: 0;
}

.td-header__badges {
  display: flex;
  gap: var(--space-2);
  align-items: center;
  flex-shrink: 0;
  padding-top: 0.3rem; /* align with title cap height */
}

.td-header__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
}

.td-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.td-meta-sep {
  color: var(--color-border-strong);
}

.td-meta-syncing {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  color: var(--color-brand);
  font-weight: var(--font-weight-medium);
}

/* ── Sidebar info list ────────────────────────────────────────────────── */
.td-info-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin: 0;
  font-size: var(--font-size-sm);
}

.td-info-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-3);
  padding: var(--space-3) 0;
  border-bottom: 1px solid var(--color-border-subtle);
}

.td-info-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.td-info-row:first-child {
  padding-top: 0;
}

.td-info-label {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
  white-space: nowrap;
  flex-shrink: 0;
}

.td-info-value {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  text-align: right;
}

.td-info-value--truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 9rem;
}

/* ── Reclamation callout inside info card ─────────────────────────────── */
.td-reclamation {
  margin-top: var(--space-5);
  padding-top: var(--space-5);
  border-top: 1px solid var(--color-border);
}

/* ── Spinner ──────────────────────────────────────────────────────────── */
.spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>