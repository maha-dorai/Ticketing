<template>
  <AppLayout>
    <PageHeader variant="default" back-inline>
      <template #back>
        <BaseButton variant="ghost" size="sm" @click="$router.push({ name: 'Projects' })">
          <ArrowLeft :size="16" aria-hidden="true" />
          Mes Projets
        </BaseButton>
      </template>
      <template v-if="project" #title>{{ project.nom }}</template>
      <template v-if="project" #subtitle>
        <span class="ds-header-meta">
          <BaseBadge :variant="statusBadge(project.statut)" pill dot>
            {{ statusLabel(project.statut) }}
          </BaseBadge>
          <span class="ds-caption">{{ fmt(project.date_debut) }} → {{ fmt(project.date_fin) }}</span>
        </span>
      </template>
      <template v-if="isTesteur && activeTab === 'tickets'" #actions>
        <BaseButton @click="showCreateModal = true">
          <Plus :size="16" aria-hidden="true" />
          Nouveau ticket
        </BaseButton>
      </template>
    </PageHeader>

    <nav class="ds-tabs" aria-label="Sections du projet">
      <button
        type="button"
        class="ds-tab"
        :class="{ 'ds-tab--active': activeTab === 'info' }"
        @click="activeTab = 'info'"
      >
        <ClipboardList :size="16" aria-hidden="true" />
        Informations
      </button>
      <button
        type="button"
        class="ds-tab"
        :class="{ 'ds-tab--active': activeTab === 'tickets' }"
        @click="activeTab = 'tickets'"
      >
        <TicketIcon :size="16" aria-hidden="true" />
        Tickets
        <BaseBadge v-if="tickets.length" variant="brand" count>{{ tickets.length }}</BaseBadge>
      </button>
    </nav>

    <div v-if="loading" class="ds-loading-state">
      <Loader2 class="spin" :size="20" aria-hidden="true" />
      Chargement…
    </div>

    <div v-else class="ds-page-body">
      <template v-if="activeTab === 'info' && project">
        <div class="ds-info-grid">
          <div class="ds-info-item">
            <div class="ds-info-item__label">Description</div>
            <div class="ds-info-item__value">{{ project.description || 'Aucune description.' }}</div>
          </div>
          <div class="ds-info-item">
            <div class="ds-info-item__label">Statut</div>
            <BaseBadge :variant="statusBadge(project.statut)" pill>
              {{ statusLabel(project.statut) }}
            </BaseBadge>
          </div>
          <div class="ds-info-item">
            <div class="ds-info-item__label">Date de début</div>
            <div class="ds-info-item__value">{{ fmt(project.date_debut) }}</div>
          </div>
          <div class="ds-info-item">
            <div class="ds-info-item__label">Date de fin</div>
            <div class="ds-info-item__value">{{ fmt(project.date_fin) }}</div>
          </div>
          <div v-if="deadlineBadge" class="ds-info-item">
            <div class="ds-info-item__label">Temps restant</div>
            <span class="deadline-badge" :class="deadlineBadge.color">{{ deadlineBadge.label }}</span>
          </div>
        </div>

        <div v-if="project.users?.length" class="ds-filter-panel">
          <div class="ds-pill-group">
            <span class="ds-filter-label">Filtrer par rôle</span>
            <button
              type="button"
              class="ds-pill"
              :class="{ 'ds-pill--active': filterRole.includes('developpeur') }"
              @click="toggleRole('developpeur')"
            >
              <Code2 :size="14" aria-hidden="true" />
              Développeurs
            </button>
            <button
              type="button"
              class="ds-pill"
              :class="{ 'ds-pill--active': filterRole.includes('testeur') }"
              @click="toggleRole('testeur')"
            >
              <Search :size="14" aria-hidden="true" />
              Testeurs
            </button>
          </div>
          <div v-if="filterRole.length === 0 || filterRole.includes('developpeur')" class="ds-pill-group">
            <span class="ds-filter-label">
              Charge max. (devs) :
              <BaseBadge variant="neutral">{{ filterMaxTickets >= 10 ? 'Tous' : filterMaxTickets }}</BaseBadge>
            </span>
            <input v-model="filterMaxTickets" type="range" min="0" max="10" class="ds-select" style="min-width: 8rem" />
          </div>
        </div>

        <div v-if="project.users?.length" class="ds-panel">
          <h2 class="ds-panel__title">Membres du projet ({{ filteredMembers.length }})</h2>
          <p v-if="filteredMembers.length === 0" class="ds-caption">Aucun membre ne correspond à ces filtres.</p>
          <div v-else class="ds-members-grid">
            <div v-for="m in filteredMembers" :key="m.id" class="ds-member-card">
              <div class="ds-member-card__avatar" :class="`ds-member-card__avatar--${m.role}`">
                {{ ini(m) }}
              </div>
              <div>
                <div class="ds-member-card__name">{{ m.prenom }} {{ m.nom }}</div>
                <div class="ds-member-card__meta">
                  <BaseBadge :variant="roleBadge(m.role)" pill>{{ roleLabel(m.role) }}</BaseBadge>
                  <BaseBadge v-if="m.role === 'developpeur'" variant="neutral" pill>
                    {{ m.active_tickets_count }} actifs
                  </BaseBadge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <template v-if="activeTab === 'tickets'">
        <div class="ds-callout">
          <div>
            <p class="ds-callout__title">Tableau Kanban</p>
            <p class="ds-callout__text">Gérez vos tickets visuellement avec le glisser-déposer.</p>
          </div>
          <BaseButton @click="$router.push(`/projects/${projectId}/tickets`)">
            <BarChart3 :size="16" aria-hidden="true" />
            Ouvrir le tableau
          </BaseButton>
        </div>

        <div class="ds-filter-bar">
          <div class="ds-filter-group">
            <label class="ds-filter-label" for="filter-etat">Statut</label>
            <select id="filter-etat" v-model="filterEtat" class="ds-select">
              <option value="">Tous</option>
              <option value="OUVERT">Ouvert</option>
              <option value="EN_COURS">En cours</option>
              <option value="RESOLU">Résolu</option>
              <option value="FERME">Fermé</option>
            </select>
          </div>
          <div class="ds-filter-group">
            <label class="ds-filter-label" for="filter-prio">Priorité</label>
            <select id="filter-prio" v-model="filterPrio" class="ds-select">
              <option value="">Toutes</option>
              <option value="CRITIQUE">Critique</option>
              <option value="HAUTE">Haute</option>
              <option value="MOYENNE">Moyenne</option>
              <option value="BASSE">Basse</option>
            </select>
          </div>
        </div>

        <div v-if="filteredTickets.length === 0" class="ds-empty-panel">
          <TicketIcon :size="40" class="ds-empty-visual" aria-hidden="true" />
          <p class="ds-empty-panel__title">
            Aucun ticket{{ filterEtat || filterPrio ? ' pour ces filtres' : '' }}
          </p>
          <p v-if="isTesteur" class="ds-empty-panel__text">
            Créez votre premier ticket avec le bouton « Nouveau ticket ».
          </p>
        </div>

        <div v-else class="ds-list">
          <article
            v-for="t in filteredTickets"
            :key="t.id"
            class="ds-list-row"
            @click="$router.push({ name: 'TicketDetails', params: { projectId, id: t.id } })"
          >
            <div class="ds-list-row__prio" :class="`ds-list-row__prio--${t.priorite.toLowerCase()}`" />
            <div class="ds-list-row__main">
              <div class="ds-list-row__top">
                <span class="ds-list-row__id">#{{ t.id }}</span>
                <h3 class="ds-list-row__title">{{ t.titre }}</h3>
                <BaseBadge :variant="etatBadge(t.etat)" pill>{{ etatLabel(t.etat) }}</BaseBadge>
              </div>
              <p v-if="t.description" class="ds-list-row__desc">{{ t.description }}</p>
              <div class="ds-list-row__meta">
                <BaseBadge :variant="prioBadge(t.priorite)" pill dot>{{ t.priorite }}</BaseBadge>
                <span class="ds-list-row__meta-item">
                  <UserRound :size="14" aria-hidden="true" />
                  Créé par {{ t.testeur?.prenom }} {{ t.testeur?.nom }}
                </span>
                <span
                  v-if="t.assignment_status === 'approved' && t.developpeur"
                  class="ds-list-row__meta-item"
                >
                  <Code2 :size="14" aria-hidden="true" />
                  {{ t.developpeur.prenom }} {{ t.developpeur.nom }}
                </span>
                <span
                  v-else-if="t.assignment_status === 'pending' && t.proposed_developpeur"
                  class="ds-list-row__meta-item"
                >
                  <Clock :size="14" aria-hidden="true" />
                  Proposé : {{ t.proposed_developpeur.prenom }} {{ t.proposed_developpeur.nom }}
                </span>
                <span class="ds-list-row__meta-item">{{ formatDate(t.created_at) }}</span>
              </div>
            </div>
            <ArrowRight class="ds-list-row__arrow" :size="16" aria-hidden="true" />
          </article>
        </div>
      </template>
    </div>

    <div v-if="showCreateModal" class="ds-modal-backdrop" @click.self="closeModal">
      <div class="ds-modal" role="dialog" aria-labelledby="create-ticket-title">
        <template v-if="assignResult">
          <div class="ds-modal__header">
            <h3 id="create-ticket-title" class="ds-modal__title">
              <CheckCircle2 :size="18" aria-hidden="true" />
              Ticket créé
            </h3>
            <button type="button" class="ds-modal__close" aria-label="Fermer" @click="closeModal">&times;</button>
          </div>
          <div class="ds-modal__body">
            <div v-if="assignResult.success" class="ds-confirm-assign">
              <div class="ds-confirm-assign__icon">
                <RefreshCw v-if="assignResult.is_retour" :size="18" aria-hidden="true" />
                <Clock v-else :size="18" aria-hidden="true" />
              </div>
              <div>
                <p class="ds-callout__title">
                  {{
                    assignResult.is_retour
                      ? 'Assignation automatique (retour)'
                      : 'Assignation en validation'
                  }}
                </p>
                <p class="ds-callout__text">
                  <template v-if="assignResult.is_retour">
                    Assigné à {{ assignResult.dev_prenom }} {{ assignResult.dev_nom }}.
                  </template>
                  <template v-else>
                    Proposé à {{ assignResult.dev_prenom }} {{ assignResult.dev_nom }} — en attente de
                    validation admin.
                  </template>
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
          <div class="ds-modal__footer">
            <BaseButton @click="closeModal">Fermer</BaseButton>
          </div>
        </template>

        <template v-else>
          <div class="ds-modal__header">
            <h3 id="create-ticket-title" class="ds-modal__title">Nouveau ticket</h3>
            <button type="button" class="ds-modal__close" aria-label="Fermer" @click="closeModal">&times;</button>
          </div>
          <div class="ds-modal__body">
            <form class="ds-form-stack" @submit.prevent="submitTicket">
              <div class="ds-field">
                <span class="ds-field__label">Type de ticket</span>
                <div class="ds-radio-group">
                  <label class="ds-radio-option" :class="{ 'ds-radio-option--active': form.type === 'NOUVEAU' }">
                    <input v-model="form.type" type="radio" value="NOUVEAU" hidden />
                    Nouveau
                  </label>
                  <label class="ds-radio-option" :class="{ 'ds-radio-option--active': form.type === 'RETOUR' }">
                    <input v-model="form.type" type="radio" value="RETOUR" hidden />
                    Retour
                  </label>
                </div>
              </div>

              <div v-if="form.type === 'RETOUR'" class="ds-form-warning">
                <label class="ds-field__label ds-field__label--required">Ticket parent</label>
                <select v-model="form.parent_ticket_id" class="ds-select" style="width: 100%">
                  <option :value="null" disabled>— Sélectionner le ticket concerné —</option>
                  <option v-for="t in validParentTickets" :key="t.id" :value="t.id">
                    #{{ t.id }} — {{ t.titre }} ({{ t.etat }})
                  </option>
                </select>
              </div>

              <BaseInput v-model="form.titre" label="Titre" required placeholder="Titre du ticket" />
              <BaseInput
                v-model="form.temps_estime"
                label="Estimation (heures)"
                type="number"
                required
                placeholder="Ex: 2.5"
                step="0.5"
                min="0.5"
              />

              <div class="ds-field">
                <label class="ds-field__label">Étapes pour reproduire</label>
                <textarea v-model="form.etapes" rows="2" class="ds-textarea" placeholder="1. Cliquer sur X…" />
              </div>
              <div class="ds-field">
                <label class="ds-field__label">Résultat attendu vs obtenu</label>
                <textarea v-model="form.resultat" rows="2" class="ds-textarea" />
              </div>
              <div class="ds-field">
                <label class="ds-field__label">Notes supplémentaires</label>
                <textarea v-model="form.notes" rows="2" class="ds-textarea" />
              </div>

              <div class="ds-field">
                <label class="ds-field__label">Pièces jointes</label>
                <input type="file" multiple class="ds-input" @change="handleFileUpload" />
                <p v-if="attachments.length" class="ds-field__hint">
                  {{ attachments.length }} fichier(s) sélectionné(s)
                </p>
              </div>

              <div class="ds-field">
                <label class="ds-field__label" for="prio-select">Priorité</label>
                <select id="prio-select" v-model="form.priorite" class="ds-select" style="width: 100%">
                  <option value="BASSE">Basse</option>
                  <option value="MOYENNE">Moyenne</option>
                  <option value="HAUTE">Haute</option>
                  <option value="CRITIQUE">Critique</option>
                </select>
              </div>
            </form>
            <p v-if="formError" class="ds-field__error" role="alert">{{ formError }}</p>
          </div>
          <div class="ds-modal__footer">
            <BaseButton variant="secondary" @click="closeModal">Annuler</BaseButton>
            <BaseButton :loading="submitting" @click="submitTicket">
              {{ submitting ? 'Création…' : 'Créer le ticket' }}
            </BaseButton>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import {
  AlertTriangle,
  ArrowLeft,
  ArrowRight,
  BarChart3,
  CheckCircle2,
  ClipboardList,
  Clock,
  Code2,
  Loader2,
  Plus,
  RefreshCw,
  Search,
  Ticket as TicketIcon,
  UserRound,
} from 'lucide-vue-next';
import api from '../../services/api';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseBadge from '../../components/ui/BaseBadge.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const projectId = route.params.id;

const project = ref(null);
const tickets = ref([]);
const loading = ref(false);
const activeTab = ref('info');

const filterEtat = ref('');
const filterPrio = ref('');

const filterRole = ref([]);
const filterMaxTickets = ref(10);

const toggleRole = (role) => {
  if (filterRole.value.includes(role)) {
    filterRole.value = filterRole.value.filter((r) => r !== role);
  } else {
    filterRole.value.push(role);
  }
};

const showCreateModal = ref(false);
const submitting = ref(false);
const formError = ref('');
const assignResult = ref(null);
const form = ref({
  titre: '',
  etapes: '',
  resultat: '',
  notes: '',
  priorite: 'BASSE',
  temps_estime: null,
  type: 'NOUVEAU',
  parent_ticket_id: null,
});
const attachments = ref([]);

const validParentTickets = computed(() =>
  tickets.value.filter((t) => t.developpeur_id && ['VALIDE', 'A_TESTER', 'RECLAMATION'].includes(t.etat)),
);

const handleFileUpload = (event) => {
  attachments.value = Array.from(event.target.files);
};

const isTesteur = computed(() => authStore.currentUser?.role === 'testeur');

const filteredMembers = computed(() => {
  if (!project.value?.users) return [];
  return project.value.users.filter((m) => {
    if (filterRole.value.length > 0 && !filterRole.value.includes(m.role)) return false;
    if (m.role === 'developpeur' && filterMaxTickets.value < 10 && m.active_tickets_count > filterMaxTickets.value) {
      return false;
    }
    return true;
  });
});

const filteredTickets = computed(() =>
  tickets.value.filter((t) => {
    if (filterEtat.value && t.etat !== filterEtat.value) return false;
    if (filterPrio.value && t.priorite !== filterPrio.value) return false;
    return true;
  }),
);

const fetchProject = async () => {
  loading.value = true;
  try {
    const r = await api.get(`/projects/${projectId}`);
    project.value = r.data;
  } catch {
    router.push({ name: 'Projects' });
  } finally {
    loading.value = false;
  }
};

const fetchTickets = async () => {
  try {
    const r = await api.get(`/projects/${projectId}/tickets`);
    tickets.value = r.data;
  } catch (e) {
    console.error(e);
  }
};

onMounted(async () => {
  await fetchProject();
  await fetchTickets();
});

const submitTicket = async () => {
  if (!form.value.titre) {
    formError.value = 'Le titre est requis.';
    return;
  }
  if (!form.value.temps_estime || form.value.temps_estime <= 0) {
    formError.value = "L'estimation du temps est requise.";
    return;
  }
  submitting.value = true;
  formError.value = '';
  try {
    const formData = new FormData();
    formData.append('titre', form.value.titre);
    formData.append('priorite', form.value.priorite);
    formData.append('temps_estime', form.value.temps_estime);
    formData.append('type', form.value.type);
    if (form.value.type === 'RETOUR' && form.value.parent_ticket_id) {
      formData.append('parent_ticket_id', form.value.parent_ticket_id);
    }
    if (form.value.etapes) formData.append('etapes', form.value.etapes);
    if (form.value.resultat) formData.append('resultat', form.value.resultat);
    if (form.value.notes) formData.append('notes', form.value.notes);

    attachments.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file);
    });

    const res = await api.post(`/projects/${route.params.id}/tickets`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await fetchTickets();
    activeTab.value = 'tickets';
    assignResult.value = res.data.auto_assign;
  } catch (e) {
    formError.value = e.response?.data?.message || 'Erreur lors de la création.';
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  formError.value = '';
  form.value = {
    titre: '',
    etapes: '',
    resultat: '',
    notes: '',
    priorite: 'BASSE',
    temps_estime: null,
    type: 'NOUVEAU',
    parent_ticket_id: null,
  };
  attachments.value = [];
  assignResult.value = null;
};

const fmt = (d) =>
  d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '');
const ini = (u) => (u.prenom?.[0] || '') + (u.nom?.[0] || '');

const statusLabel = (s) => ({ ouvert: 'Ouvert', en_cours: 'En cours', archive: 'Archivé' }[s] || s);
const statusBadge = (s) => ({ ouvert: 'open', en_cours: 'progress', archive: 'closed' }[s] || 'neutral');

const deadlineBadge = computed(() => {
  if (!project.value?.date_fin || project.value?.statut === 'archive') return null;
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const fin = new Date(project.value.date_fin);
  const diff = Math.ceil((fin - today) / 86400000);
  if (diff < 0)  return { label: `En retard de ${Math.abs(diff)}j`, color: 'badge-red' };
  if (diff <= 14) return { label: `${diff}j restants`, color: 'badge-yellow' };
  return { label: `${diff}j restants`, color: 'badge-green' };
});

const roleLabel = (r) => ({ testeur: 'Testeur', developpeur: 'Développeur', admin: 'Admin' }[r] || r);
const roleBadge = (r) => ({ testeur: 'success', developpeur: 'brand', admin: 'neutral' }[r] || 'neutral');

const etatLabel = (e) =>
  ({ OUVERT: 'Ouvert', EN_COURS: 'En cours', RESOLU: 'Résolu', FERME: 'Fermé' }[e] || e);
const etatBadge = (e) =>
  ({ OUVERT: 'open', EN_COURS: 'progress', RESOLU: 'info', FERME: 'closed' }[e] || 'neutral');

const prioBadge = (p) =>
  ({
    BASSE: 'priority-low',
    MOYENNE: 'priority-medium',
    HAUTE: 'priority-high',
    CRITIQUE: 'priority-critical',
  }[p] || 'neutral');
</script>

<style scoped>
.deadline-badge {
  display: inline-block;
  padding: .25rem .65rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
}
.badge-green  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.badge-yellow { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.badge-red    { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
</style>