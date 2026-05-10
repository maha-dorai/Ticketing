<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="brand">
        <div class="brand-icon">🔐</div>
        <h1 class="brand-name">Changement requis</h1>
        <p class="brand-sub">Vous devez définir un nouveau mot de passe avant de continuer</p>
      </div>
      <div class="card">
        <div class="info-box">
          <p>Votre compte a été créé par un administrateur. Pour des raisons de sécurité, vous devez changer votre mot de passe temporaire maintenant.</p>
        </div>

        <div v-if="message" class="alert" :class="success ? 'alert-success' : 'alert-error'">
          {{ success ? '✓' : '✕' }} {{ message }}
        </div>

        <form @submit.prevent="handleSubmit" class="form">
          <div class="field">
            <label class="label">Mot de passe temporaire (actuel)</label>
            <div class="input-wrap">
              <input v-model="form.ancien" :type="show1 ? 'text' : 'password'" required placeholder="Mot de passe reçu par email" class="input input-pr" />
              <button type="button" class="eye-btn" @click="show1 = !show1" tabindex="-1"><EyeSvg :open="show1" /></button>
            </div>
          </div>

          <div class="field">
            <label class="label">Nouveau mot de passe</label>
            <div class="input-wrap">
              <input v-model="form.nouveau" :type="show2 ? 'text' : 'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="input input-pr" />
              <button type="button" class="eye-btn" @click="show2 = !show2" tabindex="-1"><EyeSvg :open="show2" /></button>
            </div>
            <div class="strength-bar">
              <div v-for="i in 4" :key="i" class="seg" :class="strengthClass(i)"></div>
            </div>
          </div>

          <div class="field">
            <label class="label">Confirmer le nouveau mot de passe</label>
            <div class="input-wrap">
              <input v-model="form.confirm" :type="show3 ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" :class="{ mismatch: form.confirm && form.nouveau !== form.confirm }" />
              <button type="button" class="eye-btn" @click="show3 = !show3" tabindex="-1"><EyeSvg :open="show3" /></button>
            </div>
            <p v-if="form.confirm && form.nouveau !== form.confirm" class="hint-error">Les mots de passe ne correspondent pas</p>
          </div>

          <button type="submit" :disabled="loading || (form.confirm && form.nouveau !== form.confirm)" class="btn-primary">
            <svg v-if="loading" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/>
              <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/>
            </svg>
            <span v-else>Définir mon mot de passe →</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import api from '../services/api';

const router = useRouter();
const authStore = useAuthStore();
const form = ref({ ancien: '', nouveau: '', confirm: '' });
const message = ref(''); const success = ref(false); const loading = ref(false);
const show1 = ref(false); const show2 = ref(false); const show3 = ref(false);

const strength = computed(() => {
  const p = form.value.nouveau; let s = 0;
  if (p.length >= 8) s++; if (/[A-Z]/.test(p)) s++; if (/[0-9]/.test(p)) s++; if (/[\W_]/.test(p)) s++;
  return s;
});
const strengthClass = (i) => {
  if (strength.value < i) return 'seg-empty';
  return ['', 'seg-weak', 'seg-fair', 'seg-good', 'seg-strong'][strength.value] || 'seg-strong';
};

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

const handleSubmit = async () => {
  if (form.value.nouveau !== form.value.confirm) { message.value = 'Les mots de passe ne correspondent pas.'; success.value = false; return; }
  loading.value = true; message.value = '';
  try {
    await api.put('/users/change-password', { ancien_mot_de_passe: form.value.ancien, nouveau_mot_de_passe: form.value.nouveau });
    authStore.clearForcePasswordChange();
    success.value = true; message.value = 'Mot de passe défini. Redirection...';
    setTimeout(() => router.push({ name: 'UserManagement' }), 1500);
  } catch (err) { success.value = false; message.value = err.response?.data?.message || 'Erreur.'; }
  finally { loading.value = false; }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
* { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
.auth-page { min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
.auth-container { width: 100%; max-width: 440px; }
.brand { text-align: center; margin-bottom: 2rem; }
.brand-icon { font-size: 2.5rem; } .brand-name { font-size: 1.5rem; font-weight: 800; color: #f8fafc; margin: 0.5rem 0 0; } .brand-sub { color: #64748b; font-size: 0.875rem; margin: 0.25rem 0 0; }
.card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
.info-box { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); border-radius: 8px; padding: 0.875rem 1rem; font-size: 0.875rem; color: #fcd34d; margin-bottom: 1.5rem; line-height: 1.5; }
.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1.25rem; }
.alert-error { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
.alert-success { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
.form { display: flex; flex-direction: column; gap: 1.1rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; }
.input-wrap { position: relative; }
.input { width: 100%; padding: 0.6875rem 0.875rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 0.9375rem; font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
.input::placeholder { color: #475569; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.input-pr { padding-right: 2.75rem; }
.mismatch { border-color: #ef4444 !important; }
.eye-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #475569; padding: 4px; display: flex; align-items: center; transition: color 0.15s; }
.eye-btn:hover { color: #94a3b8; }
.strength-bar { display: flex; gap: 4px; margin-top: 6px; }
.seg { height: 3px; flex: 1; border-radius: 2px; transition: background 0.3s; }
.seg-empty { background: #1e293b; border: 1px solid #334155; }
.seg-weak { background: #ef4444; } .seg-fair { background: #f59e0b; } .seg-good { background: #3b82f6; } .seg-strong { background: #22c55e; }
.hint-error { font-size: 0.75rem; color: #f87171; }
.btn-primary { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.5rem; }
.btn-primary:hover:not(:disabled) { background: #2563eb; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>