<template>
  <AppLayout>
      <div class="glass-header">
        <div class="header-content">
          <h1 class="page-title">
            <Crown class="page-title-icon" aria-hidden="true" />
            <span>Gestion des Chefs de projet</span>
          </h1>
          <p class="page-sub">Créez et gérez les accès privilégiés de la plateforme avec simplicité.</p>
        </div>
      </div>

      <div class="page-content">
        <transition name="fade-slide">
          <AlertBanner
            v-if="globalMessage"
            :variant="globalSuccess ? 'sparkle' : 'error'"
            class="alert"
            :class="globalSuccess ? 'alert-ok' : 'alert-err'"
          >
            {{ globalMessage }}
          </AlertBanner>
        </transition>

        <div class="dashboard-grid">
          <!-- SECTION CRÉATION -->
          <div class="create-section">
            <div class="premium-card sticky-card">
              <div class="card-header">
                <div class="icon-circle">
                  <Plus :size="20" aria-hidden="true" />
                </div>
                <div>
                  <h2 class="card-title">Nouveau Chef de projet</h2>
                  <p class="card-desc">Génère automatiquement des accès temporaires sécurisés.</p>
                </div>
              </div>

              <form @submit.prevent="createAdmin" class="premium-form">
                <div class="input-group-row">
                  <div class="input-field">
                    <label>Prénom</label>
                    <input v-model="f.prenom" type="text" required />
                  </div>
                  <div class="input-field">
                    <label>Nom</label>
                    <input v-model="f.nom" type="text" required />
                  </div>
                </div>
                <div class="input-field">
                  <label>Email Professionnel</label>
                  <input v-model="f.email" type="email"  required />
                  <span class="field-hint">Le mot de passe y sera envoyé.</span>
                </div>
                
                <button type="submit" :disabled="creating" class="btn-gradient">
                  <Loader2 v-if="creating" :size="18" class="spin" aria-hidden="true" />
                  <span v-else>Créer le compte</span>
                </button>
              </form>
            </div>
          </div>

          <!-- SECTION LISTE & FILTRES -->
          <div class="list-section">
            <div class="section-header">
              <h2 class="section-title">Comptes enregistrés</h2>
              <div class="badge-count">{{ admins.length }}</div>
            </div>

            <!-- BARRE DE FILTRES -->
            <div class="filters-container">
              <div class="search-box">
                <Search :size="18" class="search-icon" aria-hidden="true" />
                <input v-model="searchQuery" type="text" placeholder="Rechercher par nom ou email..." />
              </div>
              <div class="filter-pills">
                <button @click="filterStatus = 'all'" :class="{ 'active-pill': filterStatus === 'all' }">Tous</button>
                <button @click="filterStatus = 'actif'" :class="{ 'active-pill': filterStatus === 'actif' }">Actifs</button>
                <button @click="filterStatus = 'pending'" :class="{ 'active-pill': filterStatus === 'pending' }">En attente</button>
                <button @click="filterStatus = 'desactive'" :class="{ 'active-pill': filterStatus === 'desactive' }">Désactivés</button>
              </div>
            </div>

            <div v-if="loading" class="state-container">
              <div class="loader-pulse"></div>
              <p>Chargement des données...</p>
            </div>

            <div v-else-if="!admins.length" class="state-container empty-state">
              <Crown class="empty-avatar" :size="40" aria-hidden="true" />
              <h3>Aucun chef de projet</h3>
              <p>Commencez par utiliser le formulaire pour donner l'accès à un collaborateur.</p>
            </div>
            
            <div v-else-if="filteredAdmins.length === 0" class="state-container empty-state">
              <Search class="empty-avatar" :size="40" aria-hidden="true" />
              <h3>Aucun résultat</h3>
              <p>Aucun compte ne correspond à votre recherche ou filtre actuel.</p>
            </div>

            <div v-else class="cards-grid">
              <div v-for="a in filteredAdmins" :key="a.id" class="user-card" :class="{ 'card-disabled': a.statut === 'desactive' }">
                <div class="card-top">
                  <div class="avatar-gradient">{{ ((a.prenom||'')[0] + (a.nom||'')[0]).toUpperCase() }}</div>
                  <div class="status-indicator" :class="a.statut === 'actif' ? 'is-active' : 'is-off'"></div>
                </div>
                <div class="card-info">
                  <h3 class="user-name">{{ a.prenom }} {{ a.nom }}</h3>
                  <p class="user-email">{{ a.email }}</p>
                  <div class="badges-area">
                    <span v-if="a.force_password_change" class="status-badge pending btn-with-icon">
                      <Clock :size="12" aria-hidden="true" />
                      En attente (MDP)
                    </span>
                    <span v-else-if="a.statut === 'actif'" class="status-badge active btn-with-icon">
                      <CheckCircle2 :size="12" aria-hidden="true" />
                      Actif
                    </span>
                    <span v-else class="status-badge disabled btn-with-icon">
                      <Ban :size="12" aria-hidden="true" />
                      Désactivé
                    </span>
                  </div>
                </div>
                <div class="card-actions" v-if="a.statut === 'actif'">
                  <button @click="askRevoke(a)" class="btn-ghost-danger">Révoquer l'accès</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- MODAL CONFIRMATION -->
        <transition name="modal-fade">
          <div v-if="adminToRevoke" class="modal-backdrop">
            <div class="premium-modal">
              <div class="modal-icon-danger">
                <AlertTriangle :size="28" aria-hidden="true" />
              </div>
              <h3 class="modal-title">Désactiver ce compte ?</h3>
              <p class="modal-text">
                Vous êtes sur le point de révoquer l'accès de <strong class="text-highlight">{{ adminToRevoke.prenom }} {{ adminToRevoke.nom }}</strong>. 
                Cette personne sera immédiatement déconnectée.
              </p>
              <div class="modal-buttons">
                <button @click="adminToRevoke = null" class="btn-secondary">Annuler</button>
                <button @click="confirmRevoke" class="btn-danger-fill">Confirmer la révocation</button>
              </div>
            </div>
          </div>
        </transition>
      </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { AlertTriangle, Ban, CheckCircle2, Clock, Crown, Loader2, Plus, Search } from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import AlertBanner from '../../components/ui/AlertBanner.vue';

const admins = ref([]);
const loading = ref(false);
const creating = ref(false);
const globalMessage = ref('');
const globalSuccess = ref(true);
const adminToRevoke = ref(null);
const f = ref({ nom: '', prenom: '', email: '' });

// Filtres
const searchQuery = ref('');
const filterStatus = ref('all');

const filteredAdmins = computed(() => {
  let result = admins.value;

  // Filtre par statut
  if (filterStatus.value === 'actif') {
    result = result.filter(a => a.statut === 'actif' && !a.force_password_change);
  } else if (filterStatus.value === 'pending') {
    result = result.filter(a => a.force_password_change);
  } else if (filterStatus.value === 'desactive') {
    result = result.filter(a => a.statut === 'desactive');
  }

  // Recherche textuelle
  if (searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(a => 
      (a.nom && a.nom.toLowerCase().includes(q)) || 
      (a.prenom && a.prenom.toLowerCase().includes(q)) || 
      (a.email && a.email.toLowerCase().includes(q))
    );
  }

  return result;
});

const fetchAdmins = async () => {
  loading.value = true;
  try { const r = await api.get('/admin/chefs'); admins.value = r.data; }
  catch { msg('Erreur lors de la récupération des données.', false); }
  finally { loading.value = false; }
};
onMounted(fetchAdmins);

const msg = (m, ok = true) => {
  globalMessage.value = m; globalSuccess.value = ok;
  setTimeout(() => globalMessage.value = '', 6000);
};

const createAdmin = async () => {
  creating.value = true;
  try {
    const r = await api.post('/admin/chefs', f.value);
    msg(r.data.message);
    f.value = { nom: '', prenom: '', email: '' };
    await fetchAdmins();
  } catch (e) { msg(e.response?.data?.message || 'Une erreur est survenue lors de la création.', false); }
  finally { creating.value = false; }
};

const askRevoke = (a) => { adminToRevoke.value = a; };
const confirmRevoke = async () => {
  try {
    await api.put(`/admin/chefs/${adminToRevoke.value.id}/revoke`);
    msg('Accès révoqué avec succès.');
    adminToRevoke.value = null;
    await fetchAdmins();
  } catch { msg('Erreur lors de la désactivation.', false); adminToRevoke.value = null; }
};
</script>

<style scoped>
/* ── HEADER GLASSMORPHISM ── */
.glass-header {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255,255,255,0.4);
  padding: 2.5rem 3rem;
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
}
.header-content { max-width: 1400px; margin: 0 auto; }
.page-title {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  font-size: 2rem;
  font-weight: 800;
  margin: 0 0 0.5rem;
  letter-spacing: -0.03em;
}
.page-title-icon { width: 1.75rem; height: 1.75rem; color: #3b82f6; flex-shrink: 0; }
.page-title span {
  background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.page-sub { font-size: 0.95rem; color: #64748b; margin: 0; font-weight: 500; }

.page-content { padding: 2.5rem 3rem; max-width: 1400px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; gap: 2rem; }

/* ── ALERTS ── */
.alert { padding: 1rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
.alert-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.search-box .search-icon { color: #94a3b8; flex-shrink: 0; }
.spin { animation: spin 1s linear infinite; }

/* ── GRID LAYOUT ── */
.dashboard-grid { display: grid; grid-template-columns: 350px 1fr; gap: 2.5rem; align-items: start; }

/* ── PREMIUM CARD (FORM) ── */
.premium-card { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.02); border: 1px solid rgba(226, 232, 240, 0.8); }
.sticky-card { position: sticky; top: 120px; }
.card-header { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 2rem; }
.icon-circle { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.card-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem; }
.card-desc { font-size: 0.8rem; color: #64748b; line-height: 1.5; margin: 0; }

.premium-form { display: flex; flex-direction: column; gap: 1.25rem; }
.input-group-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.input-field { display: flex; flex-direction: column; gap: 0.5rem; }
.input-field label { font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; }
.input-field input { width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.9rem; font-weight: 500; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); outline: none; }
.input-field input:focus { background: white; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); transform: translateY(-1px); }
.input-field input::placeholder { color: #cbd5e1; font-weight: 400; }
.field-hint { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }

.btn-gradient { padding: 0.875rem; border-radius: 10px; border: none; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); color: white; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); margin-top: 0.5rem; }
.btn-gradient:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(37, 99, 235, 0.3); }
.btn-gradient:active:not(:disabled) { transform: translateY(0); }
.btn-gradient:disabled { opacity: 0.7; filter: grayscale(50%); cursor: not-allowed; }

/* ── LIST SECTION & FILTERS ── */
.section-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
.section-title { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0; }
.badge-count { background: white; color: #3b82f6; font-size: 0.8rem; font-weight: 800; padding: 0.25rem 0.75rem; border-radius: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

.filters-container { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; background: white; padding: 0.75rem 1rem; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid rgba(226, 232, 240, 0.8); }
.search-box { display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 250px; background: #f8fafc; padding: 0.5rem 1rem; border-radius: 10px; border: 1px solid #e2e8f0; transition: border-color 0.2s; }
.search-box:focus-within { border-color: #3b82f6; background: white; }
.search-box input { border: none; background: transparent; width: 100%; font-size: 0.9rem; color: #1e293b; font-family: inherit; outline: none; }
.search-box input::placeholder { color: #cbd5e1; }

.filter-pills { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.filter-pills button { padding: 0.4rem 1rem; border-radius: 99px; border: 1px solid #e2e8f0; background: white; font-size: 0.8rem; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.2s; }
.filter-pills button:hover { border-color: #cbd5e1; background: #f8fafc; }
.filter-pills button.active-pill { background: #3b82f6; color: white; border-color: #3b82f6; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2); }

.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }

/* ── USER CARD ── */
.user-card { background: white; border-radius: 16px; padding: 1.5rem; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 10px 30px rgba(0,0,0,0.02); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; position: relative; overflow: hidden; }
.user-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #3b82f6, #8b5cf6); opacity: 0; transition: opacity 0.3s; }
.user-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); border-color: #cbd5e1; }
.user-card:hover::before { opacity: 1; }
.card-disabled { opacity: 0.65; filter: grayscale(20%); }
.card-disabled:hover { transform: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border-color: rgba(226, 232, 240, 0.8); }
.card-disabled::before { display: none; }

.card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; }
.avatar-gradient { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5), 0 4px 10px rgba(0,0,0,0.05); }
.status-indicator { width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 0 3px white; }
.status-indicator.is-active { background: #10b981; }
.status-indicator.is-off { background: #94a3b8; }

.card-info { flex: 1; }
.user-name { font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem; }
.user-email { font-size: 0.8rem; color: #64748b; margin: 0 0 1rem; }

.badges-area { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem; }
.status-badge { font-size: 0.7rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 8px; letter-spacing: 0.03em; }
.status-badge.active { background: rgba(16, 185, 129, 0.1); color: #059669; }
.status-badge.pending { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.status-badge.disabled { background: #f1f5f9; color: #64748b; }

.card-actions { border-top: 1px solid #f1f5f9; padding-top: 1rem; margin-top: auto; }
.btn-ghost-danger { width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid transparent; background: transparent; color: #ef4444; font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
.btn-ghost-danger:hover { background: #fef2f2; border-color: #fecaca; }

/* ── STATES ── */
.state-container { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; text-align: center; color: #64748b; background: white; border-radius: 20px; border: 1px dashed #cbd5e1; }
.loader-pulse { width: 40px; height: 40px; border-radius: 50%; border: 3px solid #e2e8f0; border-top-color: #3b82f6; animation: spin 1s infinite linear; margin-bottom: 1rem; }
.empty-avatar { margin-bottom: 1rem; color: #94a3b8; filter: drop-shadow(0 10px 10px rgba(0,0,0,0.05)); }
.status-badge.btn-with-icon { display: inline-flex; align-items: center; gap: 0.25rem; }
.empty-state h3 { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem; }
.empty-state p { font-size: 0.9rem; max-width: 300px; margin: 0; line-height: 1.5; }

/* ── MODAL ── */
.modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
.premium-modal { background: white; width: 100%; max-width: 420px; border-radius: 24px; padding: 2.5rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); text-align: center; }
.modal-icon-danger { width: 64px; height: 64px; border-radius: 50%; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
.modal-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 0.75rem; }
.modal-text { font-size: 0.9rem; color: #64748b; line-height: 1.6; margin: 0 0 2rem; }
.text-highlight { color: #1e293b; font-weight: 700; }
.modal-buttons { display: flex; gap: 1rem; }
.btn-secondary { flex: 1; padding: 0.75rem; background: #f1f5f9; color: #475569; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
.btn-secondary:hover { background: #e2e8f0; }
.btn-danger-fill { flex: 1; padding: 0.75rem; background: #ef4444; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
.btn-danger-fill:hover { background: #dc2626; box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4); }

/* ── ANIMATIONS ── */
@keyframes spin { to { transform: rotate(360deg); } }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

@media (max-width: 1024px) {
  .dashboard-grid { grid-template-columns: 1fr; }
  .sticky-card { position: relative; top: 0; }
}
</style>