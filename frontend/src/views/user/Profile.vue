<template>
  <div class="page">
    <!-- Header -->
    <div class="topbar">
      <div class="topbar-left">
        <span class="topbar-logo">🎫</span>
        <div>
          <p class="topbar-name">{{ currentUser?.prenom }} {{ currentUser?.nom }}</p>
          <span class="role-badge">{{ roleLabel }}</span>
        </div>
      </div>
      <div class="topbar-right">
        <button @click="$router.push({ name: 'Projects' })" class="btn-ghost">📁 Mes Projets</button>
        <button @click="logout" class="btn-danger">Déconnexion</button>
      </div>
    </div>

    <div class="content">
      <h1 class="page-title">Mon Profil</h1>

      <!-- Changer mot de passe -->
      <div class="card">
        <h2 class="card-title">🔒 Changer le mot de passe</h2>
        <div v-if="pwMessage" class="alert" :class="pwSuccess ? 'alert-success' : 'alert-error'">
          {{ pwSuccess ? '✓' : '✕' }} {{ pwMessage }}
        </div>
        <form @submit.prevent="changePassword" class="form">
          <div class="field">
            <label class="label">Mot de passe actuel</label>
            <div class="input-wrap">
              <input v-model="pwForm.ancien" :type="showPw1 ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" />
              <button type="button" class="eye-btn" @click="showPw1 = !showPw1" tabindex="-1"><EyeSvg :open="showPw1" /></button>
            </div>
          </div>
          <div class="field">
            <label class="label">Nouveau mot de passe</label>
            <div class="input-wrap">
              <input v-model="pwForm.nouveau" :type="showPw2 ? 'text' : 'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="input input-pr" />
              <button type="button" class="eye-btn" @click="showPw2 = !showPw2" tabindex="-1"><EyeSvg :open="showPw2" /></button>
            </div>
          </div>
          <div class="field">
            <label class="label">Confirmer le nouveau mot de passe</label>
            <div class="input-wrap">
              <input v-model="pwForm.confirm" :type="showPw3 ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" :class="{ 'input-mismatch': pwForm.confirm && pwForm.nouveau !== pwForm.confirm }" />
              <button type="button" class="eye-btn" @click="showPw3 = !showPw3" tabindex="-1"><EyeSvg :open="showPw3" /></button>
            </div>
            <p v-if="pwForm.confirm && pwForm.nouveau !== pwForm.confirm" class="hint-error">Les mots de passe ne correspondent pas</p>
          </div>
          <button type="submit" :disabled="pwLoading || (pwForm.confirm && pwForm.nouveau !== pwForm.confirm)" class="btn-primary">
            <Spinner v-if="pwLoading" /> <span v-else>Mettre à jour</span>
          </button>
        </form>
      </div>

      <!-- Changer email -->
      <div class="card">
        <h2 class="card-title">✉ Changer l'adresse email</h2>
        <p class="card-sub">Email actuel : <strong>{{ currentUser?.email }}</strong></p>
        <div v-if="emailMessage" class="alert" :class="emailSuccess ? 'alert-success' : 'alert-error'">
          {{ emailSuccess ? '✓' : '✕' }} {{ emailMessage }}
        </div>
        <form @submit.prevent="changeEmail" class="form">
          <div class="field">
            <label class="label">Nouvel email</label>
            <input v-model="emailForm.new_email" type="email" required placeholder="nouveau@exemple.com" class="input" />
          </div>
          <div class="field">
            <label class="label">Mot de passe actuel (confirmation)</label>
            <div class="input-wrap">
              <input v-model="emailForm.mot_de_passe" :type="showPw4 ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" />
              <button type="button" class="eye-btn" @click="showPw4 = !showPw4" tabindex="-1"><EyeSvg :open="showPw4" /></button>
            </div>
          </div>
          <button type="submit" :disabled="emailLoading" class="btn-primary">
            <Spinner v-if="emailLoading" /> <span v-else>Mettre à jour l'email</span>
          </button>
        </form>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const authStore = useAuthStore();
const router = useRouter();
const currentUser = computed(() => authStore.currentUser);
const roleLabel = computed(() => ({ admin: 'Administrateur', super_admin: 'Super Admin', developpeur: 'Développeur', testeur: 'Testeur' }[currentUser.value?.role] || ''));

// Eye toggle component
const EyeSvg = defineComponent({
  props: ['open'],
  setup(p) {
    return () => p.open
      ? h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: 17, height: 17, fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.8', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z' }),
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' }),
        ])
      : h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: 17, height: 17, fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.8', stroke: 'currentColor' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88' }),
        ]);
  }
});

const Spinner = defineComponent({ setup: () => () => h('svg', { class: 'spin', xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', width: 18, height: 18 }, [h('circle', { cx: 12, cy: 12, r: 10, stroke: 'currentColor', 'stroke-width': 4, style: 'opacity:.25' }), h('path', { fill: 'currentColor', d: 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z', style: 'opacity:.75' })]) });

// Password form
const pwForm = ref({ ancien: '', nouveau: '', confirm: '' });
const pwMessage = ref(''); const pwSuccess = ref(false); const pwLoading = ref(false);
const showPw1 = ref(false); const showPw2 = ref(false); const showPw3 = ref(false);

const changePassword = async () => {
  if (pwForm.value.nouveau !== pwForm.value.confirm) { pwMessage.value = 'Les mots de passe ne correspondent pas.'; pwSuccess.value = false; return; }
  pwMessage.value = ''; pwLoading.value = true;
  try {
    await api.put('/users/change-password', { ancien_mot_de_passe: pwForm.value.ancien, nouveau_mot_de_passe: pwForm.value.nouveau });
    authStore.clearForcePasswordChange();
    pwSuccess.value = true; pwMessage.value = 'Mot de passe modifié avec succès.';
    pwForm.value = { ancien: '', nouveau: '', confirm: '' };
  } catch (err) { pwSuccess.value = false; pwMessage.value = err.response?.data?.message || 'Erreur.'; }
  finally { pwLoading.value = false; }
};

// Email form
const emailForm = ref({ new_email: '', mot_de_passe: '' });
const emailMessage = ref(''); const emailSuccess = ref(false); const emailLoading = ref(false);
const showPw4 = ref(false);

const changeEmail = async () => {
  emailMessage.value = ''; emailLoading.value = true;
  try {
    await api.put('/users/change-email', { new_email: emailForm.value.new_email, mot_de_passe: emailForm.value.mot_de_passe });
    if (authStore.currentUser) { authStore.currentUser.email = emailForm.value.new_email; localStorage.setItem('user', JSON.stringify(authStore.currentUser)); }
    emailSuccess.value = true; emailMessage.value = 'Email modifié avec succès.';
    emailForm.value = { new_email: '', mot_de_passe: '' };
  } catch (err) { emailSuccess.value = false; emailMessage.value = err.response?.data?.message || 'Erreur.'; }
  finally { emailLoading.value = false; }
};

const logout = async () => { await authStore.logout(); router.push({ name: 'Login' }); };
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
.page { min-height: 100vh; background: #0f172a; }
.topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 0.875rem 2rem; display: flex; align-items: center; justify-content: space-between; }
.topbar-left { display: flex; align-items: center; gap: 0.875rem; }
.topbar-logo { font-size: 1.5rem; }
.topbar-name { font-size: 0.9375rem; font-weight: 700; color: #f1f5f9; margin: 0; }
.role-badge { font-size: 0.6875rem; font-weight: 600; background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.25); border-radius: 20px; padding: 2px 8px; letter-spacing: 0.03em; text-transform: uppercase; }
.topbar-right { display: flex; gap: 0.5rem; }
.btn-ghost { padding: 0.5rem 1rem; background: transparent; border: 1px solid #334155; color: #94a3b8; border-radius: 7px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.btn-ghost:hover { border-color: #475569; color: #f1f5f9; }
.btn-danger { padding: 0.5rem 1rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #fca5a5; border-radius: 7px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.btn-danger:hover { background: rgba(239,68,68,0.2); }
.content { max-width: 560px; margin: 0 auto; padding: 2rem 1rem; display: flex; flex-direction: column; gap: 1.5rem; }
.page-title { font-size: 1.5rem; font-weight: 800; color: #f8fafc; margin: 0 0 0.5rem; letter-spacing: -0.02em; }
.card { background: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 1.75rem; }
.card-title { font-size: 1rem; font-weight: 700; color: #f1f5f9; margin: 0 0 1.25rem; }
.card-sub { font-size: 0.8125rem; color: #64748b; margin: -0.75rem 0 1.25rem; }
.card-sub strong { color: #94a3b8; }
.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1rem; }
.alert-error { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
.alert-success { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
.form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; }
.input-wrap { position: relative; }
.input { width: 100%; padding: 0.6875rem 0.875rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 0.9375rem; font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
.input::placeholder { color: #475569; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.input-pr { padding-right: 2.75rem; }
.input-mismatch { border-color: #ef4444 !important; }
.eye-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #475569; padding: 4px; display: flex; align-items: center; transition: color 0.15s; }
.eye-btn:hover { color: #94a3b8; }
.hint-error { font-size: 0.75rem; color: #f87171; margin-top: 0.2rem; }
.btn-primary { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.btn-primary:hover:not(:disabled) { background: #2563eb; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>