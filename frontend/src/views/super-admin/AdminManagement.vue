<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion des administrateurs</h1>
          <p class="page-sub">Créez et gérez les comptes administrateurs de la plateforme</p>
        </div>
      </div>

      <div class="page-content">
        <div v-if="globalMessage" class="alert" :class="globalSuccess ? 'alert-ok' : 'alert-err'">
          {{ globalSuccess ? '✓' : '✕' }} {{ globalMessage }}
        </div>

        <div class="two-col">
          <!-- ═══ FORMULAIRE ═══ -->
          <div class="form-card">
            <h2 class="card-title">➕ Créer un administrateur</h2>
            <p class="card-desc">
              Saisissez les informations du futur administrateur.
              Le système génère automatiquement un mot de passe temporaire
              et l'envoie par email. L'administrateur devra le changer à sa
              première connexion.
            </p>

            <form @submit.prevent="createAdmin" class="form">
              <div class="row2">
                <div class="field">
                  <label class="label">Prénom</label>
                  <input v-model="f.prenom" type="text" class="input" />
                </div>
                <div class="field">
                  <label class="label">Nom</label>
                  <input v-model="f.nom" type="text" class="input" />
                </div>
              </div>
              <div class="field">
                <label class="label">Adresse email professionnelle</label>
                <input v-model="f.email" type="email" class="input" />
                <p class="hint">📧 Le mot de passe temporaire sera envoyé à cette adresse</p>
              </div>
              <button type="submit" :disabled="creating" class="btn-primary">
                <svg v-if="creating" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
                <span v-else>✉ Créer et envoyer les accès →</span>
              </button>
            </form>
          </div>

          <!-- ═══ LISTE ADMINS ═══ -->
          <div class="list-card">
            <div class="list-header">
              <h2 class="card-title">Administrateurs</h2>
              <span class="cnt-badge">{{ activeAdmins.length }} actifs</span>
            </div>

            <div v-if="loading" class="loading-state">
              <svg class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.7"/></svg>
              Chargement...
            </div>

            <div v-else-if="!admins.length" class="empty-list">
              <div class="empty-icon">👑</div>
              <p>Aucun administrateur créé</p>
            </div>

            <div v-else class="admin-list">
              <div v-for="a in admins" :key="a.id" class="admin-row" :class="{ 'admin-row-disabled': a.statut === 'desactive' }">
                <div class="admin-av">{{ ((a.prenom||'')[0] + (a.nom||'')[0]).toUpperCase() }}</div>
                <div class="admin-info">
                  <p class="admin-name">{{ a.prenom }} {{ a.nom }}</p>
                  <p class="admin-email">{{ a.email }}</p>
                  <div class="admin-meta">
                    <span v-if="a.force_password_change" class="badge-pending">⏳ 1ère connexion en attente</span>
                    <span v-else-if="a.statut === 'actif'" class="badge-active">● Actif</span>
                    <span v-else class="badge-disabled">⊘ Désactivé</span>
                  </div>
                </div>
                <button v-if="a.statut === 'actif'" @click="askRevoke(a)" class="btn-revoke">Désactiver</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal confirmation -->
        <div v-if="adminToRevoke" class="overlay">
          <div class="modal">
            <h3 class="modal-title">Confirmer la désactivation</h3>
            <p class="modal-body">
              Voulez-vous désactiver le compte de
              <strong>{{ adminToRevoke.prenom }} {{ adminToRevoke.nom }}</strong> ?
              Il ne pourra plus se connecter.
            </p>
            <div class="modal-actions">
              <button @click="adminToRevoke = null" class="btn-cancel">Annuler</button>
              <button @click="confirmRevoke" class="btn-danger">Oui, désactiver</button>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const admins = ref([]);
const loading = ref(false);
const creating = ref(false);
const globalMessage = ref('');
const globalSuccess = ref(true);
const adminToRevoke = ref(null);
const f = ref({ nom: '', prenom: '', email: '' });

const activeAdmins = computed(() => admins.value.filter(a => a.statut === 'actif'));

const fetchAdmins = async () => {
  loading.value = true;
  try { const r = await api.get('/super-admin/admins'); admins.value = r.data; }
  catch { msg('Erreur de chargement.', false); }
  finally { loading.value = false; }
};
onMounted(fetchAdmins);

const msg = (m, ok = true) => {
  globalMessage.value = m; globalSuccess.value = ok;
  setTimeout(() => globalMessage.value = '', 5000);
};

const createAdmin = async () => {
  creating.value = true;
  try {
    const r = await api.post('/super-admin/admins', f.value);
    msg(r.data.message);
    f.value = { nom: '', prenom: '', email: '' };
    await fetchAdmins();
  } catch (e) { msg(e.response?.data?.message || 'Erreur.', false); }
  finally { creating.value = false; }
};

const askRevoke = (a) => { adminToRevoke.value = a; };
const confirmRevoke = async () => {
  try {
    await api.put(`/super-admin/admins/${adminToRevoke.value.id}/revoke`);
    msg('Administrateur désactivé.');
    adminToRevoke.value = null;
    await fetchAdmins();
  } catch { msg('Erreur.', false); adminToRevoke.value = null; }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;}
.page-header{padding:2rem 2.5rem 1.5rem;border-bottom:1px solid #e2e8f0;background:white;}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;}
.page-sub{font-size:.875rem;color:#64748b;margin:.25rem 0 0;}
.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;}
.alert-ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.alert-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;}
.form-card,.list-card{background:white;border:1px solid #e2e8f0;border-radius:14px;padding:1.75rem;}
.card-title{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 .625rem;}
.card-desc{font-size:.8125rem;color:#64748b;margin:0 0 1.5rem;line-height:1.6;}
.form{display:flex;flex-direction:column;gap:1rem;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
.field{display:flex;flex-direction:column;gap:.35rem;}
.label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;}
.input{width:100%;padding:.625rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;font-size:.9rem;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.input::placeholder{color:#cbd5e1;}
.input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);background:white;}
.hint{font-size:.75rem;color:#94a3b8;margin-top:.25rem;}
.btn-primary{padding:.75rem;background:#1e293b;color:white;border:none;border-radius:8px;font-size:.875rem;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;}
.btn-primary:hover:not(:disabled){background:#0f172a;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.list-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;}
.cnt-badge{background:#f1f5f9;color:#64748b;font-size:.75rem;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid #e2e8f0;}
.loading-state{display:flex;align-items:center;gap:.5rem;color:#94a3b8;font-size:.875rem;padding:1.5rem 0;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
.empty-list{text-align:center;padding:2.5rem 1rem;color:#94a3b8;}
.empty-icon{font-size:2rem;margin-bottom:.75rem;}
.empty-list p{font-size:.875rem;}
.admin-list{display:flex;flex-direction:column;gap:.625rem;}
.admin-row{display:flex;align-items:center;gap:.875rem;padding:.875rem 1rem;border-radius:10px;border:1px solid #f1f5f9;background:#fafafa;transition:border-color .15s;}
.admin-row:hover{border-color:#e2e8f0;}
.admin-row-disabled{opacity:.55;}
.admin-av{width:36px;height:36px;border-radius:9px;background:#dbeafe;color:#1d4ed8;font-size:.75rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-transform:uppercase;}
.admin-info{flex:1;min-width:0;}
.admin-name{font-size:.875rem;font-weight:700;color:#1e293b;margin:0;}
.admin-email{font-size:.75rem;color:#94a3b8;margin:0;}
.admin-meta{margin-top:.25rem;}
.badge-pending{font-size:.6875rem;font-weight:600;color:#d97706;background:#fef3c7;padding:2px 7px;border-radius:20px;}
.badge-active{font-size:.6875rem;font-weight:600;color:#16a34a;}
.badge-disabled{font-size:.6875rem;font-weight:600;color:#94a3b8;}
.btn-revoke{padding:5px 12px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;flex-shrink:0;transition:all .15s;}
.btn-revoke:hover{background:#dc2626;color:white;}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:50;padding:1rem;}
.modal{background:white;border-radius:14px;padding:1.75rem;max-width:400px;width:100%;box-shadow:0 20px 40px rgba(0,0,0,.2);}
.modal-title{font-size:1.125rem;font-weight:800;color:#1e293b;margin:0 0 .75rem;}
.modal-body{font-size:.875rem;color:#64748b;margin:0 0 1.5rem;line-height:1.6;}
.modal-actions{display:flex;gap:.75rem;justify-content:flex-end;}
.btn-cancel{padding:.5rem 1rem;background:white;color:#64748b;border:1px solid #e2e8f0;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;}
.btn-danger{padding:.5rem 1rem;background:#dc2626;color:white;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;}
</style>