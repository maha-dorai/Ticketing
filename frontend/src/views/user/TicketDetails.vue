<template>
  <AppLayout>
    <div class="ds-detail-page">
      <div v-if="loading" class="ds-loading-state">
        <Loader2 class="spin" :size="20" aria-hidden="true" />
        Chargement du ticket…
      </div>

      <template v-else-if="ticket">
        <div class="ds-detail-header">
          <button
            type="button"
            class="ds-back-link"
            @click="$router.push({ name: 'Tickets', params: { projectId: route.params.projectId } })"
          >
            <ChevronLeft :size="16" aria-hidden="true" />
            Retour aux tickets
          </button>
          <BaseBadge :variant="prioBadge(ticket.priorite)" pill>{{ ticket.priorite }}</BaseBadge>
        </div>

        <h1 class="ds-detail-title">
          <Ticket :size="24" aria-hidden="true" />
          {{ ticket.titre }}
        </h1>

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

        <BaseCard>
          <template #header>
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%">
              <h2 class="ds-section-label">
                <File :size="16" aria-hidden="true" />
                Pipeline d'état
              </h2>
              <span v-if="stateUpdating" class="ds-caption">Synchronisation…</span>
            </div>
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

        <div
          v-if="ticket.categorie_ia || ticket.priorite_ia || ticket.solution_ia"
          class="ds-ai-panel"
        >
          <div class="ds-ai-panel__head">
            <Bot :size="20" aria-hidden="true" />
            <div style="flex: 1">
              <h3 class="ds-ai-panel__title">Analyse automatique</h3>
              <p class="ds-ai-panel__sub">Générée à la création du ticket</p>
            </div>
            <BaseButton variant="ghost" size="sm" :loading="aiLoading" @click="reanalyzeAI">
              <RefreshCw v-if="!aiLoading" :size="14" aria-hidden="true" />
              {{ aiLoading ? 'Mise à jour…' : 'Relancer' }}
            </BaseButton>
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
          <div
            v-if="ticket.solution_ia && currentUser?.role === 'developpeur' && ticket.developpeur_id === currentUser?.id"
            class="ds-ai-solution"
          >
            <p class="ds-ai-tag__label">Solution suggérée</p>
            <p class="ds-description">{{ ticket.solution_ia }}</p>
          </div>
        </div>

        <div class="ds-detail-layout">
          <div class="ds-detail-main">
            <BaseCard>
              <div
                v-if="ticket.description && ticket.description.trim()"
                class="ds-description"
                v-html="formatDescription(ticket.description)"
              />
              <p v-else class="ds-description ds-description--empty">
                <FileText :size="16" aria-hidden="true" />
                Aucune description fournie.
              </p>

              <div v-if="ticket.attachments?.length" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border)">
                <h4 class="ds-section-label" style="margin-bottom: 1rem">
                  <Paperclip :size="14" aria-hidden="true" />
                  Pièces jointes ({{ ticket.attachments.length }})
                </h4>
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
              </div>
            </BaseCard>

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

          <aside class="ds-detail-sidebar">
            <BaseCard>
              <h3 class="ds-section-label" style="margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border)">
                Informations
              </h3>
              <dl class="ds-meta-list">
                <div class="ds-meta-list__row">
                  <dt class="ds-meta-list__label">Projet</dt>
                  <dd class="ds-meta-list__value ds-meta-list__value--truncate">{{ ticket.project?.nom }}</dd>
                </div>
                <div class="ds-meta-list__row">
                  <dt class="ds-meta-list__label">Testeur</dt>
                  <dd class="ds-meta-list__value">
                    {{ ticket.testeur?.prenom }} {{ ticket.testeur?.nom }}
                  </dd>
                </div>
                <div class="ds-meta-list__row" style="flex-direction: column; align-items: flex-start">
                  <dt class="ds-meta-list__label">Assignation</dt>
                  <dd>
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
              </dl>

              <div
                v-if="ticket.etat === 'RECLAMATION' && ticket.raison_reclamation"
                style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border)"
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

            <BaseCard v-if="ticket.temps_estime">
              <h3 class="ds-section-label" style="margin-bottom: 1rem">Suivi du temps</h3>
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

            <BaseCard>
              <h3 class="ds-section-label" style="margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--color-border)">
                Actions requises
              </h3>

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
  AlertTriangle,
  Bot,
  ChevronLeft,
  CheckCircle2,
  Coffee,
  Clock,
  Code2,
  File,
  FileText,
  GripVertical,
  Loader2,
  Lock,
  MessageSquare,
  Paperclip,
  Pencil,
  RefreshCw,
  Ticket,
  Trash2,
  XCircle,
} from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseBadge from '../../components/ui/BaseBadge.vue';
import BaseCard from '../../components/ui/BaseCard.vue';

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
const aiLoading = ref(false);

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
const onDragEnd = () => {
  dragTarget.value = null;
};
const onDragOver = (etat) => {
  if (!isManager.value) dragTarget.value = etat;
};

const onDrop = async (etat) => {
  dragTarget.value = null;
  if (!ticket.value || ticket.value.etat === etat) return;

  if (!canTransition(ticket.value, etat)) {
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
    BUG: 'Bug',
    PERFORMANCE: 'Performance',
    SECURITE: 'Sécurité',
    UI_UX: 'UI/UX',
    BASE_DE_DONNEES: 'Base de données',
    API: 'API',
    CONFIGURATION: 'Configuration',
    AUTRE: 'Autre',
    NON_CLASSE: 'Non classé',
  };
  return map[cat] || cat;
};

const reanalyzeAI = async () => {
  aiLoading.value = true;
  try {
    await api.post(`/tickets/${ticket.value.id}/analyze-ai`);
    await fetchTicket();
  } catch (e) {
    console.error(e);
  } finally {
    aiLoading.value = false;
  }
};

const formatTime = (iso) => {
  const d = new Date(iso);
  return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
};

onMounted(fetchTicket);
</script>