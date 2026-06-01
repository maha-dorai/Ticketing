<template>
  <AppLayout>

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
              <span class="meta-item meta-item--icon">
                <Mail :size="14" aria-hidden="true" />
                {{ user?.email }}
              </span>
              <a v-if="isDeveloper && user?.github_link" :href="user.github_link" target="_blank" class="meta-item gh-link">
                <Github :size="14" aria-hidden="true" />
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
            <h2 class="card-title card-title--icon">
              <User :size="18" aria-hidden="true" />
              Informations personnelles
            </h2>
            <div class="header-actions">
              <button @click="$router.push({ name: 'MyStats' })" class="btn-stats btn-with-icon">
                <TrendingUp :size="14" aria-hidden="true" />
                Mes statistiques
              </button>
              <template v-if="!isManagerRole">
                <BaseButton v-if="!editingInfo" variant="secondary" size="sm" class="btn-with-icon" @click="startEdit">
                  <Pencil :size="14" aria-hidden="true" />
                  Modifier
                </BaseButton>
                <BaseButton v-else variant="danger-outline" size="sm" @click="cancelEditInfo">Annuler</BaseButton>
              </template>
            </div>
          </div>
          <BaseAlert v-if="infoMsg" :variant="infoOk ? 'success' : 'error'" :icon="infoOk ? CheckCircle2 : XCircle" class="ds-page-feedback">{{ infoMsg }}</BaseAlert>

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
                <BaseInput v-model="infoForm.prenom" required placeholder="Prénom" />
              </div>
              <div class="field">
                <label class="label">Nom</label>
                <BaseInput v-model="infoForm.nom" required placeholder="Nom" />
              </div>
            </div>
            <div v-if="isDeveloper" class="field">
              <label class="label">Lien GitHub (optionnel)</label>
              <BaseInput v-model="infoForm.github_link" placeholder="https://github.com/votre-profil" />
            </div>
            <div class="form-actions">
              <BaseButton type="submit" variant="slate" :disabled="infoL" :loading="infoL">Enregistrer</BaseButton>
            </div>
          </form>
        </div>

        <!-- Changer Email — utilisateurs uniquement -->
        <div v-if="!isManagerRole" class="card">
          <div class="card-header">
            <h2 class="card-title card-title--icon">
              <Mail :size="18" aria-hidden="true" />
              Adresse email
            </h2>
          </div>
          <BaseAlert v-if="emMsg" :variant="emOk ? 'success' : 'error'" :icon="emOk ? CheckCircle2 : XCircle" class="ds-page-feedback">{{ emMsg }}</BaseAlert>
          <form @submit.prevent="changeEm" class="form">
            <div class="row2">
              <div class="field">
                <label class="label">Nouvel email</label>
                <BaseInput v-model="em.email" type="email" required placeholder="nouveau@exemple.com" />
              </div>
              <div class="field">
                <label class="label">Mot de passe (confirmation)</label>
                <div class="iw">
                  <input v-model="em.mdp" :type="s4 ? 'text' : 'password'" required placeholder="••••••••" class="ds-input ipr" />
                  <button type="button" class="eye" @click="s4 = !s4" tabindex="-1">
                    <Eye v-if="s4" :size="17" :stroke-width="1.8" aria-hidden="true" />
                    <EyeOff v-else :size="17" :stroke-width="1.8" aria-hidden="true" />
                  </button>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <BaseButton type="submit" variant="slate" :disabled="emL" :loading="emL">Mettre à jour l'email</BaseButton>
            </div>
          </form>
        </div>

        <!-- Changer MDP — utilisateurs uniquement -->
        <div v-if="!isManagerRole" class="card">
          <div class="card-header">
            <h2 class="card-title card-title--icon">
              <Lock :size="18" aria-hidden="true" />
              Mot de passe
            </h2>
          </div>
          <BaseAlert v-if="pwMsg" :variant="pwOk ? 'success' : 'error'" :icon="pwOk ? CheckCircle2 : XCircle" class="ds-page-feedback">{{ pwMsg }}</BaseAlert>
          <form @submit.prevent="changePw" class="form">
            <div class="field">
              <label class="label">Mot de passe actuel</label>
              <div class="iw">
                <input v-model="pw.ancien" :type="s1 ? 'text' : 'password'" required placeholder="••••••••" class="ds-input ipr" />
                <button type="button" class="eye" @click="s1 = !s1" tabindex="-1">
                  <Eye v-if="s1" :size="17" :stroke-width="1.8" aria-hidden="true" />
                  <EyeOff v-else :size="17" :stroke-width="1.8" aria-hidden="true" />
                </button>
              </div>
            </div>
            <div class="row2">
              <div class="field">
                <label class="label">Nouveau mot de passe</label>
                <div class="iw">
                  <input v-model="pw.nouveau" :type="s2 ? 'text' : 'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="ds-input ipr" />
                  <button type="button" class="eye" @click="s2 = !s2" tabindex="-1">
                    <Eye v-if="s2" :size="17" :stroke-width="1.8" aria-hidden="true" />
                    <EyeOff v-else :size="17" :stroke-width="1.8" aria-hidden="true" />
                  </button>
                </div>
                <div class="sbar"><div v-for="i in 4" :key="i" class="seg" :class="sc(i)"></div></div>
                <p class="str-hint" :class="strHintClass">{{ strHintText }}</p>
              </div>
              <div class="field">
                <label class="label">Confirmer le nouveau mot de passe</label>
                <div class="iw">
                  <input v-model="pw.confirm" :type="s3 ? 'text' : 'password'" required placeholder="••••••••" class="ds-input ipr" :class="{ mismatch: pw.confirm && pw.nouveau !== pw.confirm }" />
                  <button type="button" class="eye" @click="s3 = !s3" tabindex="-1">
                    <Eye v-if="s3" :size="17" :stroke-width="1.8" aria-hidden="true" />
                    <EyeOff v-else :size="17" :stroke-width="1.8" aria-hidden="true" />
                  </button>
                </div>
                <p v-if="pw.confirm && pw.nouveau !== pw.confirm" class="mismatch-text">Les mots de passe ne correspondent pas</p>
              </div>
            </div>
            <div class="form-actions">
              <BaseButton type="submit" variant="slate" :disabled="pwL || (pw.confirm && pw.nouveau !== pw.confirm)" :loading="pwL">
                Mettre à jour le mot de passe
              </BaseButton>
            </div>
          </form>
        </div>

      </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import { CheckCircle2, Eye, EyeOff, Github, Lock, Mail, Pencil, TrendingUp, User, XCircle } from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import BaseAlert from '../../components/ui/BaseAlert.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';

const authStore = useAuthStore();
const user = computed(() => authStore.currentUser);
const initials = computed(() => ((user.value?.prenom || '')[0] + (user.value?.nom || '')[0]).toUpperCase());
const roleLabel = computed(() => ({ admin: 'Administrateur', chef_de_projet: 'Chef de Projet', developpeur: 'Développeur', testeur: 'Testeur' }[user.value?.role] || ''));
const roleColorClass = computed(() => ({ admin: 'role-admin', chef_de_projet: 'role-admin', developpeur: 'role-dev', testeur: 'role-test' }[user.value?.role] || ''));

// Admin et chef_de_projet : profil en lecture seule (pas de modification)
const isManagerRole = computed(() => ['admin', 'chef_de_projet'].includes(user.value?.role));
// Lien GitHub : affiché uniquement pour les développeurs
const isDeveloper = computed(() => user.value?.role === 'developpeur');


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
    infoOk.value = true; infoMsg.value = 'Informations mises à jour';
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
  try { await api.put('/users/change-password', { ancien_mot_de_passe: pw.value.ancien, nouveau_mot_de_passe: pw.value.nouveau }); authStore.clearForcePasswordChange(); pwOk.value = true; pwMsg.value = 'Mot de passe modifié'; pw.value = { ancien: '', nouveau: '', confirm: '' }; }
  catch (e) { pwOk.value = false; pwMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { pwL.value = false; }
};

// ── Email ──
const em = ref({ email: '', mdp: '' }), emMsg = ref(''), emOk = ref(true), emL = ref(false);
const changeEm = async () => {
  emL.value = true; emMsg.value = '';
  try { await api.put('/users/change-email', { new_email: em.value.email, mot_de_passe: em.value.mdp }); if (authStore.currentUser) { authStore.currentUser.email = em.value.email; localStorage.setItem('user', JSON.stringify(authStore.currentUser)); } emOk.value = true; emMsg.value = 'Email modifié'; em.value = { email: '', mdp: '' }; }
  catch (e) { emOk.value = false; emMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { emL.value = false; }
};
</script>

<style scoped>
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
.field :deep(.ds-input){width:100%;}
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
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}

@media(max-width:640px){
  .row2{grid-template-columns:1fr;}
  .info-grid{grid-template-columns:1fr;}
  .info-item.full{grid-column:span 1;}
  .banner-content{flex-direction:column;align-items:flex-start;gap:1rem;padding:1.5rem;}
  .page-content{padding:1.25rem;}
}
</style>