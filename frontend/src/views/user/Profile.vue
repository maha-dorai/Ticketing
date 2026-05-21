<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">

      <!-- Header Banner -->
      <div class="profile-banner">
        <div class="banner-bg"></div>
        <div class="banner-content">
          <div class="avatar-wrap">
            <div class="big-av">{{ initials }}</div>
            <div class="av-role" :class="roleColorClass">{{ roleLabel }}</div>
          </div>
          <div class="banner-info">
            <h1 class="profile-name">{{ user?.prenom }} {{ user?.nom }}</h1>
            <div class="profile-meta">
              <span class="meta-item">✉ {{ user?.email }}</span>
              <a v-if="isDeveloper && user?.github_link" :href="user.github_link" target="_blank" class="meta-item gh-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                GitHub ↗
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="page-content">

        <!-- Infos personnelles -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">👤 Informations personnelles</h2>
            <div class="header-actions">
              <button @click="$router.push({ name: 'MyStats' })" class="btn-stats">📈 Mes statistiques</button>
              <template v-if="!isAdminRole">
                <button v-if="!editingInfo" @click="startEdit" class="btn-edit">✏ Modifier</button>
                <button v-else @click="cancelEditInfo" class="btn-cancel-sm">Annuler</button>
              </template>
            </div>
          </div>
          <div v-if="infoMsg" class="alert" :class="infoOk ? 'alert-ok' : 'alert-err'">{{ infoOk ? '✓' : '✕' }} {{ infoMsg }}</div>

          <div v-if="!editingInfo" class="info-grid">
            <div class="info-item">
              <span class="info-label">Prénom</span>
              <span class="info-val">{{ user?.prenom }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Nom</span>
              <span class="info-val">{{ user?.nom }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rôle</span>
              <span class="rb" :class="roleColorClass">{{ roleLabel }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Statut</span>
              <span class="st-chip st-ok">Actif</span>
            </div>
            <div v-if="isDeveloper" class="info-item full">
              <span class="info-label">Lien GitHub</span>
              <a v-if="user?.github_link" :href="user.github_link" target="_blank" class="gh-val">{{ user.github_link }}</a>
              <span v-else class="info-empty">Non renseigné</span>
            </div>
          </div>

          <form v-else @submit.prevent="saveInfo" class="form">
            <div class="row2">
              <div class="field">
                <label class="label">Prénom</label>
                <input v-model="infoForm.prenom" required class="input" placeholder="Prénom" />
              </div>
              <div class="field">
                <label class="label">Nom</label>
                <input v-model="infoForm.nom" required class="input" placeholder="Nom" />
              </div>
            </div>
            <div v-if="isDeveloper" class="field">
              <label class="label">Lien GitHub (optionnel)</label>
              <input v-model="infoForm.github_link" class="input" placeholder="https://github.com/votre-profil" />
            </div>
            <div class="form-actions">
              <button type="submit" :disabled="infoL" class="btn-primary">
                <svg v-if="infoL" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
                <span v-else>Enregistrer</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Changer Email — utilisateurs uniquement -->
        <div v-if="!isAdminRole" class="card">
          <div class="card-header">
            <h2 class="card-title">✉ Adresse email</h2>
          </div>
          <div v-if="emMsg" class="alert" :class="emOk ? 'alert-ok' : 'alert-err'">{{ emOk ? '✓' : '✕' }} {{ emMsg }}</div>
          <form @submit.prevent="changeEm" class="form">
            <div class="row2">
              <div class="field">
                <label class="label">Nouvel email</label>
                <input v-model="em.email" type="email" required placeholder="nouveau@exemple.com" class="input" />
              </div>
              <div class="field">
                <label class="label">Mot de passe (confirmation)</label>
                <div class="iw">
                  <input v-model="em.mdp" :type="s4 ? 'text' : 'password'" required placeholder="••••••••" class="input ipr" />
                  <button type="button" class="eye" @click="s4 = !s4" tabindex="-1"><Eye :o="s4" /></button>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" :disabled="emL" class="btn-primary">
                <svg v-if="emL" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
                <span v-else>Mettre à jour l'email</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Changer MDP — utilisateurs uniquement -->
        <div v-if="!isAdminRole" class="card">
          <div class="card-header">
            <h2 class="card-title">🔒 Mot de passe</h2>
          </div>
          <div v-if="pwMsg" class="alert" :class="pwOk ? 'alert-ok' : 'alert-err'">{{ pwOk ? '✓' : '✕' }} {{ pwMsg }}</div>
          <form @submit.prevent="changePw" class="form">
            <div class="field">
              <label class="label">Mot de passe actuel</label>
              <div class="iw">
                <input v-model="pw.ancien" :type="s1 ? 'text' : 'password'" required placeholder="••••••••" class="input ipr" />
                <button type="button" class="eye" @click="s1 = !s1" tabindex="-1"><Eye :o="s1" /></button>
              </div>
            </div>
            <div class="row2">
              <div class="field">
                <label class="label">Nouveau mot de passe</label>
                <div class="iw">
                  <input v-model="pw.nouveau" :type="s2 ? 'text' : 'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="input ipr" />
                  <button type="button" class="eye" @click="s2 = !s2" tabindex="-1"><Eye :o="s2" /></button>
                </div>
                <div class="sbar"><div v-for="i in 4" :key="i" class="seg" :class="sc(i)"></div></div>
                <p class="str-hint" :class="strHintClass">{{ strHintText }}</p>
              </div>
              <div class="field">
                <label class="label">Confirmer le nouveau mot de passe</label>
                <div class="iw">
                  <input v-model="pw.confirm" :type="s3 ? 'text' : 'password'" required placeholder="••••••••" class="input ipr" :class="{ mismatch: pw.confirm && pw.nouveau !== pw.confirm }" />
                  <button type="button" class="eye" @click="s3 = !s3" tabindex="-1"><Eye :o="s3" /></button>
                </div>
                <p v-if="pw.confirm && pw.nouveau !== pw.confirm" class="mismatch-text">Les mots de passe ne correspondent pas</p>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" :disabled="pwL || (pw.confirm && pw.nouveau !== pw.confirm)" class="btn-primary">
                <svg v-if="pwL" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
                <span v-else>Mettre à jour le mot de passe</span>
              </button>
            </div>
          </form>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const authStore = useAuthStore();
const user = computed(() => authStore.currentUser);
const initials = computed(() => ((user.value?.prenom || '')[0] + (user.value?.nom || '')[0]).toUpperCase());
const roleLabel = computed(() => ({ super_admin: 'Super Admin', admin: 'Administrateur', developpeur: 'Développeur', testeur: 'Testeur' }[user.value?.role] || ''));
const roleColorClass = computed(() => ({ super_admin: 'role-super', admin: 'role-admin', developpeur: 'role-dev', testeur: 'role-test' }[user.value?.role] || ''));

// Admin et super_admin : profil en lecture seule (pas de modification)
const isAdminRole = computed(() => ['admin', 'super_admin'].includes(user.value?.role));
// Lien GitHub : affiché uniquement pour les développeurs
const isDeveloper = computed(() => user.value?.role === 'developpeur');

// ── Eye icon component ──
const Eye = defineComponent({ props: ['o'], setup: (p) => () => p.o
  ? h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: 17, height: 17, fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.8', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z' }), h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })])
  : h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: 17, height: 17, fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.8', stroke: 'currentColor' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88' })])
});

// ── Section info personnelle ──
const editingInfo = ref(false);
const infoMsg = ref(''), infoOk = ref(true), infoL = ref(false);
const infoForm = ref({ prenom: '', nom: '', github_link: '' });

const cancelEditInfo = () => { editingInfo.value = false; infoMsg.value = ''; };
const openEditInfo = () => {
  infoForm.value = { prenom: user.value?.prenom || '', nom: user.value?.nom || '', github_link: user.value?.github_link || '' };
  editingInfo.value = true;
};
// Watch editingInfo to prefill
const startEdit = () => {
  infoForm.value = { prenom: user.value?.prenom || '', nom: user.value?.nom || '', github_link: user.value?.github_link || '' };
  editingInfo.value = true;
};
// Override the button binding
const handleEditClick = () => { if (!editingInfo.value) startEdit(); else cancelEditInfo(); };

const saveInfo = async () => {
  infoL.value = true; infoMsg.value = '';
  try {
    await api.put('/users/profile', infoForm.value);
    if (authStore.currentUser) {
      authStore.currentUser.prenom = infoForm.value.prenom;
      authStore.currentUser.nom = infoForm.value.nom;
      authStore.currentUser.github_link = infoForm.value.github_link;
      localStorage.setItem('user', JSON.stringify(authStore.currentUser));
    }
    infoOk.value = true; infoMsg.value = 'Informations mises à jour ✓';
    editingInfo.value = false;
  } catch (e) { infoOk.value = false; infoMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { infoL.value = false; }
};

// ── Mot de passe ──
const pw = ref({ ancien: '', nouveau: '', confirm: '' }), pwMsg = ref(''), pwOk = ref(true), pwL = ref(false);
const s1 = ref(false), s2 = ref(false), s3 = ref(false), s4 = ref(false);
const str = computed(() => { const p = pw.value.nouveau; let s = 0; if (p.length >= 8) s++; if (/[A-Z]/.test(p)) s++; if (/[0-9]/.test(p)) s++; if (/[\W_]/.test(p)) s++; return s; });
const sc = (i) => { if (str.value < i) return 'seg-e'; return ['', 'seg-w', 'seg-f', 'seg-g', 'seg-s'][str.value] || 'seg-s'; };
const strHintText = computed(() => (['', 'Trop faible', 'Faible', 'Bon', 'Fort'][str.value] || ''));
const strHintClass = computed(() => (['', 'hint-w', 'hint-f', 'hint-g', 'hint-s'][str.value] || ''));

const changePw = async () => {
  if (pw.value.nouveau !== pw.value.confirm) { pwMsg.value = 'Les mots de passe ne correspondent pas.'; pwOk.value = false; return; }
  pwL.value = true; pwMsg.value = '';
  try { await api.put('/users/change-password', { ancien_mot_de_passe: pw.value.ancien, nouveau_mot_de_passe: pw.value.nouveau }); authStore.clearForcePasswordChange(); pwOk.value = true; pwMsg.value = 'Mot de passe modifié ✓'; pw.value = { ancien: '', nouveau: '', confirm: '' }; }
  catch (e) { pwOk.value = false; pwMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { pwL.value = false; }
};

// ── Email ──
const em = ref({ email: '', mdp: '' }), emMsg = ref(''), emOk = ref(true), emL = ref(false);
const changeEm = async () => {
  emL.value = true; emMsg.value = '';
  try { await api.put('/users/change-email', { new_email: em.value.email, mot_de_passe: em.value.mdp }); if (authStore.currentUser) { authStore.currentUser.email = em.value.email; localStorage.setItem('user', JSON.stringify(authStore.currentUser)); } emOk.value = true; emMsg.value = 'Email modifié ✓'; em.value = { email: '', mdp: '' }; }
  catch (e) { emOk.value = false; emMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { emL.value = false; }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;}

/* Banner */
.profile-banner{position:relative;background:#0f172a;overflow:hidden;}
.banner-bg{position:absolute;inset:0;background:linear-gradient(135deg,#1e3a5f 0%,#0f172a 60%,#1e1b4b 100%);opacity:.9;}
.banner-content{position:relative;display:flex;align-items:center;gap:1.5rem;padding:2rem 2.5rem;}
.avatar-wrap{position:relative;flex-shrink:0;}
.big-av{width:72px;height:72px;border-radius:18px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:white;font-size:1.5rem;font-weight:800;display:flex;align-items:center;justify-content:center;text-transform:uppercase;border:3px solid rgba(255,255,255,.15);box-shadow:0 8px 24px rgba(0,0,0,.3);}
.av-role{position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);font-size:.5625rem;font-weight:800;padding:2px 8px;border-radius:10px;white-space:nowrap;border:2px solid #0f172a;}
.banner-info{flex:1;}
.profile-name{font-size:1.625rem;font-weight:800;color:white;margin:0 0 .5rem;letter-spacing:-.02em;}
.profile-meta{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.meta-item{font-size:.8125rem;color:#94a3b8;display:flex;align-items:center;gap:.375rem;}
.gh-link{color:#60a5fa;text-decoration:none;transition:color .15s;}
.gh-link:hover{color:#93c5fd;}

/* Role colors */
.role-super{background:#fde68a;color:#92400e;}
.role-admin{background:#fecaca;color:#991b1b;}
.role-dev{background:#bfdbfe;color:#1e40af;}
.role-test{background:#e9d5ff;color:#6b21a8;}

/* Page content */
.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;max-width:780px;}

/* Cards */
.card{background:white;border:1px solid #e2e8f0;border-radius:14px;padding:1.75rem;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;}
.header-actions{display:flex;align-items:center;gap:.75rem;}
.card-title{font-size:1rem;font-weight:700;color:#1e293b;margin:0;}
.btn-stats{padding:.375rem .875rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px;font-size:.8125rem;font-weight:700;color:#1d4ed8;cursor:pointer;transition:all .15s;}
.btn-stats:hover{background:#dbeafe;}
.btn-edit{padding:.375rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;font-weight:600;color:#475569;cursor:pointer;font-family:inherit;transition:all .15s;}
.btn-edit:hover{background:#f1f5f9;border-color:#cbd5e1;}
.btn-cancel-sm{padding:.375rem .875rem;background:#fef2f2;border:1px solid #fecaca;border-radius:7px;font-size:.8125rem;font-weight:600;color:#dc2626;cursor:pointer;font-family:inherit;}

/* Info grid */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.info-item{display:flex;flex-direction:column;gap:.25rem;}
.info-item.full{grid-column:span 2;}
.info-label{font-size:.6875rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;}
.info-val{font-size:.9375rem;font-weight:600;color:#1e293b;}
.info-empty{font-size:.875rem;color:#cbd5e1;font-style:italic;}
.gh-val{font-size:.875rem;color:#3b82f6;text-decoration:none;word-break:break-all;}
.gh-val:hover{text-decoration:underline;}
.rb{font-size:.6875rem;font-weight:700;padding:3px 9px;border-radius:20px;width:fit-content;}
.st-chip{font-size:.6875rem;font-weight:700;padding:3px 9px;border-radius:20px;width:fit-content;}
.st-ok{background:#dcfce7;color:#166534;}

/* Form */
.form{display:flex;flex-direction:column;gap:1rem;}
.field{display:flex;flex-direction:column;gap:.35rem;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
.label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;}
.iw{position:relative;}
.input{width:100%;padding:.625rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;font-size:.9rem;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.input::placeholder{color:#cbd5e1;}
.input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);background:white;}
.ipr{padding-right:2.5rem;}
.mismatch{border-color:#ef4444!important;}
.mismatch-text,.str-hint{font-size:.75rem;margin-top:4px;}
.mismatch-text{color:#dc2626;}
.hint-w{color:#ef4444;}.hint-f{color:#f59e0b;}.hint-g{color:#3b82f6;}.hint-s{color:#22c55e;}
.eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;align-items:center;transition:color .15s;}
.eye:hover{color:#475569;}
.sbar{display:flex;gap:4px;margin-top:6px;}
.seg{height:3px;flex:1;border-radius:2px;transition:background .3s;}
.seg-e{background:#f1f5f9;}.seg-w{background:#ef4444;}.seg-f{background:#f59e0b;}.seg-g{background:#3b82f6;}.seg-s{background:#22c55e;}
.form-actions{display:flex;justify-content:flex-end;padding-top:.25rem;}
.btn-primary{padding:.625rem 1.25rem;background:#1e293b;color:white;border:none;border-radius:8px;font-size:.875rem;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;gap:.5rem;transition:background .15s;}
.btn-primary:hover:not(:disabled){background:#0f172a;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:1rem;}
.alert-ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.alert-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}

@media(max-width:640px){
  .row2{grid-template-columns:1fr;}
  .info-grid{grid-template-columns:1fr;}
  .info-item.full{grid-column:span 1;}
  .banner-content{flex-direction:column;align-items:flex-start;gap:1rem;padding:1.5rem;}
  .page-content{padding:1.25rem;}
}
</style>