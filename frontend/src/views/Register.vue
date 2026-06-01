<template>
  <AuthLayout wide>
      <AuthBrand subtitle="Créer un compte — soumis à validation" />
      <div class="card">
        <h2 class="card-title">Inscription</h2>

        <AlertBanner v-if="successMessage" variant="success" class="alert alert-success">{{ successMessage }}</AlertBanner>

        <form v-else @submit.prevent="registerCandidate" class="form">
          <div class="row2">
            <div class="field">
              <label class="label">Nom</label>
              <input v-model="form.nom" type="text" class="input" />
            </div>
            <div class="field">
              <label class="label">Prénom</label>
              <input v-model="form.prenom" type="text" class="input" />
            </div>
          </div>

          <div class="field">
            <label class="label">Adresse email</label>
            <input v-model="form.email" type="email" class="input" />
          </div>

          <div class="field">
            <label class="label">Mot de passe</label>
            <div class="input-wrap">
              <input v-model="form.mot_de_passe" :type="showPw ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" />
              <button type="button" class="eye-btn" @click="showPw = !showPw" tabindex="-1">
                <svg v-if="showPw" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                </svg>
              </button>
            </div>
            <!-- Indicateur de force -->
            <div class="strength-bar">
              <div v-for="i in 4" :key="i" class="strength-segment" :class="strengthClass(i)"></div>
            </div>
            <p class="hint">Minimum 8 caractères — majuscule, chiffre, symbole requis</p>
          </div>

          <div class="field">
            <label class="label">Rôle</label>
            <select v-model="form.role" class="select">
              <option value="developpeur">Développeur</option>
              <option value="testeur">Testeur (QA)</option>
            </select>
          </div>

          <div v-if="form.role === 'developpeur' || form.role === 'testeur'" class="github-box">
            <label class="label" style="color:#93c5fd">Lien GitHub / Portfolio</label>
            <input v-model="form.github_link" type="url" placeholder="https://github.com/username" class="input" style="margin-top:0.4rem" />
          </div>

          <AlertBanner v-if="errorMessage" variant="error" class="alert alert-error">{{ errorMessage }}</AlertBanner>

          <button type="submit" :disabled="loading" class="btn-primary">
            <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:0.25"/>
              <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:0.75"/>
            </svg>
            <span v-else>Envoyer ma candidature →</span>
          </button>
        </form>

        <div class="card-footer">
          Déjà un compte ?
          <router-link to="/login" class="link">Se connecter</router-link>
        </div>
      </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useRouter } from 'vue-router';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';

const authStore = useAuthStore();
const router = useRouter();

const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);
const showPw = ref(false);

const form = ref({ nom: '', prenom: '', email: '', mot_de_passe: '', role: 'developpeur', github_link: '' });

// Password strength
const strength = computed(() => {
  const p = form.value.mot_de_passe;
  let s = 0;
  if (p.length >= 8) s++;
  if (/[A-Z]/.test(p)) s++;
  if (/[0-9]/.test(p)) s++;
  if (/[\W_]/.test(p)) s++;
  return s;
});

const strengthClass = (i) => {
  if (strength.value < i) return 'seg-empty';
  if (strength.value <= 1) return 'seg-weak';
  if (strength.value <= 2) return 'seg-fair';
  if (strength.value <= 3) return 'seg-good';
  return 'seg-strong';
};

const registerCandidate = async () => {
  errorMessage.value = ''; loading.value = true;
  try {
    await authStore.register({
      nom: form.value.nom, prenom: form.value.prenom, email: form.value.email,
      mot_de_passe: form.value.mot_de_passe, role: form.value.role,
      github_link: (form.value.role === 'developpeur' || form.value.role === 'testeur') ? form.value.github_link : null,
    });
    successMessage.value = 'Compte créé ! Votre demande est en attente de validation.';
    setTimeout(() => router.push({ name: 'Login' }), 2500);
  } catch (err) {
    const errors = err.response?.data?.errors;
    errorMessage.value = errors ? String(Object.values(errors).flat()[0]) : err.response?.data?.message || 'Une erreur est survenue.';
  } finally { loading.value = false; }
};
</script>

<style scoped>
.card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
.card-title { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin: 0 0 1.5rem; }
.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1rem; }
.alert-error { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
.alert-success { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
.form { display: flex; flex-direction: column; gap: 1.1rem; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }
.input-wrap { position: relative; }
.input { width: 100%; padding: 0.6875rem 0.875rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 0.9375rem; font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
.input::placeholder { color: #475569; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.input-pr { padding-right: 2.75rem; }
.eye-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #475569; padding: 4px; display: flex; align-items: center; transition: color 0.15s; }
.eye-btn:hover { color: #94a3b8; }
.strength-bar { display: flex; gap: 4px; margin-top: 6px; }
.strength-segment { height: 3px; flex: 1; border-radius: 2px; transition: background 0.3s; }
.seg-empty { background: #1e293b; border: 1px solid #334155; }
.seg-weak { background: #ef4444; }
.seg-fair { background: #f59e0b; }
.seg-good { background: #3b82f6; }
.seg-strong { background: #22c55e; }
.hint { font-size: 0.75rem; color: #475569; margin-top: 0.2rem; }
.select { width: 100%; padding: 0.6875rem 0.875rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 0.9375rem; font-family: inherit; outline: none; }
.select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.github-box { background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2); border-radius: 8px; padding: 1rem; }
.btn-primary { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s, transform 0.1s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.25rem; }
.btn-primary:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.card-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #64748b; }
.link { color: #3b82f6; font-weight: 600; text-decoration: none; margin-left: 0.25rem; }
.link:hover { color: #60a5fa; }
.spinner { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>