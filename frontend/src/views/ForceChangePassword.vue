<template>
  <AuthLayout>
    <AuthBrand subtitle="Plateforme de gestion de tickets" />

    <div class="card">
      <div class="card-icon">
        <ShieldCheck :size="22" aria-hidden="true" />
      </div>
      <h2 class="card-title">Définir votre mot de passe</h2>
      <p class="card-desc">
        Votre compte a été créé par un administrateur.<br />
        Choisissez un mot de passe personnel avant de continuer.
      </p>

      <AlertBanner v-if="message" class="alert" :class="ok ? 'alert-success' : 'alert-error'">
        {{ message }}
      </AlertBanner>

      <form @submit.prevent="submit" class="form">

        <!-- Mot de passe temporaire -->
        <div class="field">
          <label class="label">Mot de passe temporaire</label>
          <div class="input-wrap">
            <input v-model="f.ancien" :type="s1 ? 'text' : 'password'" required
              placeholder="Reçu par email" class="input input-pr" />
            <button type="button" class="eye-btn" @click="s1 = !s1" tabindex="-1">
              <EyeOff v-if="s1" :size="17" />
              <Eye v-else :size="17" />
            </button>
          </div>
        </div>

        <!-- Nouveau mot de passe -->
        <div class="field">
          <label class="label">Nouveau mot de passe</label>
          <div class="input-wrap">
            <input v-model="f.nouveau" :type="s2 ? 'text' : 'password'" required
              placeholder="Min 8 car., MAJ, chiffre, symbole" class="input input-pr" />
            <button type="button" class="eye-btn" @click="s2 = !s2" tabindex="-1">
              <EyeOff v-if="s2" :size="17" />
              <Eye v-else :size="17" />
            </button>
          </div>
          <!-- Barre de force -->
          <div class="sbar">
            <div v-for="i in 4" :key="i" class="seg" :class="sc(i)" />
          </div>
          <span class="sbar-label" :class="strLabel.cls">{{ strLabel.text }}</span>
        </div>

        <!-- Confirmer -->
        <div class="field">
          <label class="label">Confirmer le mot de passe</label>
          <div class="input-wrap">
            <input v-model="f.confirm" :type="s3 ? 'text' : 'password'" required
              placeholder="••••••••" class="input input-pr"
              :class="{ 'input-error': f.confirm && f.nouveau !== f.confirm }" />
            <button type="button" class="eye-btn" @click="s3 = !s3" tabindex="-1">
              <EyeOff v-if="s3" :size="17" />
              <Eye v-else :size="17" />
            </button>
          </div>
          <span v-if="f.confirm && f.nouveau !== f.confirm" class="field-error">
            Les mots de passe ne correspondent pas
          </span>
        </div>

        <button type="submit" class="btn-primary" :disabled="loading || !!(f.confirm && f.nouveau !== f.confirm)">
          <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" width="18" height="18">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="op25"/>
            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="op75"/>
          </svg>
          <span v-else>Enregistrer mon mot de passe →</span>
        </button>

      </form>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import api from '../services/api';
import { Eye, EyeOff, ShieldCheck } from 'lucide-vue-next';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';

const router   = useRouter();
const authStore = useAuthStore();

const f = ref({ ancien: '', nouveau: '', confirm: '' });
const message = ref('');
const ok      = ref(true);
const loading = ref(false);
const s1 = ref(false);
const s2 = ref(false);
const s3 = ref(false);

const str = computed(() => {
  const p = f.value.nouveau;
  let s = 0;
  if (p.length >= 8)    s++;
  if (/[A-Z]/.test(p))  s++;
  if (/[0-9]/.test(p))  s++;
  if (/[\W_]/.test(p))  s++;
  return s;
});

const sc = (i) => {
  if (str.value < i) return 'seg-e';
  return ['', 'seg-w', 'seg-f', 'seg-g', 'seg-s'][str.value] || 'seg-s';
};

const strLabel = computed(() => {
  const map = [
    { text: '',           cls: '' },
    { text: 'Faible',     cls: 'sl-w' },
    { text: 'Moyen',      cls: 'sl-f' },
    { text: 'Fort',       cls: 'sl-g' },
    { text: 'Très fort',  cls: 'sl-s' },
  ];
  return map[str.value] || map[0];
});

const submit = async () => {
  if (f.value.nouveau !== f.value.confirm) {
    message.value = 'Les mots de passe ne correspondent pas.';
    ok.value = false;
    return;
  }
  loading.value = true;
  message.value = '';
  try {
    await api.put('/users/change-password', {
      ancien_mot_de_passe: f.value.ancien,
      nouveau_mot_de_passe: f.value.nouveau,
    });
    authStore.clearForcePasswordChange();
    ok.value = true;
    message.value = 'Mot de passe défini avec succès. Redirection…';
    const role = authStore.currentUser?.role;
    setTimeout(() => {
      router.push({ name: ['admin', 'chef_de_projet'].includes(role) ? 'Dashboard' : 'Projects' });
    }, 1500);
  } catch (e) {
    ok.value = false;
    message.value = e.response?.data?.message || 'Erreur. Vérifiez votre mot de passe temporaire.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 25px 50px rgba(0,0,0,0.4);
}

.card-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: rgba(59,130,246,0.12);
  border: 1px solid rgba(59,130,246,0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #60a5fa;
  margin-bottom: 1rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.4rem;
}

.card-desc {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0 0 1.5rem;
  line-height: 1.6;
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
}
.alert-success { background: rgba(34,197,94,0.1);  color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
.alert-error   { background: rgba(239,68,68,0.1);  color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }

.form  { display: flex; flex-direction: column; gap: 1.25rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }

.input-wrap { position: relative; }
.input {
  width: 100%;
  padding: 0.6875rem 0.875rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 0.9375rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}
.input::placeholder { color: #475569; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.input-pr   { padding-right: 2.75rem; }
.input-error { border-color: #ef4444 !important; }

.eye-btn {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; color: #475569;
  padding: 4px; border-radius: 4px; display: flex; align-items: center;
  transition: color 0.15s;
}
.eye-btn:hover { color: #94a3b8; }

.field-error { font-size: 0.75rem; color: #f87171; }

/* Barre de force */
.sbar { display: flex; gap: 4px; margin-top: 6px; }
.seg  { height: 3px; flex: 1; border-radius: 2px; transition: background 0.3s; }
.seg-e { background: #1e293b; border: 1px solid #334155; }
.seg-w { background: #ef4444; }
.seg-f { background: #f59e0b; }
.seg-g { background: #3b82f6; }
.seg-s { background: #22c55e; }
.sbar-label { font-size: 0.72rem; font-weight: 600; }
.sl-w { color: #ef4444; }
.sl-f { color: #f59e0b; }
.sl-g { color: #3b82f6; }
.sl-s { color: #22c55e; }

.btn-primary {
  width: 100%;
  padding: 0.75rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}
.btn-primary:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner { animation: spin 0.8s linear infinite; }
.op25 { opacity: 0.25; }
.op75 { opacity: 0.75; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>