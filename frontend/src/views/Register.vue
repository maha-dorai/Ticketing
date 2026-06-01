<template>
  <AuthLayout wide>
      <AuthBrand subtitle="Créer un compte — soumis à validation" />
      <div class="card">
        <h2 class="card-title">Inscription</h2>

        <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="ds-page-feedback">{{ successMessage }}</BaseAlert>

        <form v-else @submit.prevent="registerCandidate" class="form">
          <div class="row2">
            <div class="field">
              <label class="label">Nom</label>
              <BaseInput v-model="form.nom" type="text" auth />
            </div>
            <div class="field">
              <label class="label">Prénom</label>
              <BaseInput v-model="form.prenom" type="text" auth />
            </div>
          </div>

          <div class="field">
            <label class="label">Adresse email</label>
            <BaseInput v-model="form.email" type="email" auth />
          </div>

          <div class="field">
            <label class="label">Mot de passe</label>
            <BaseInput v-model="form.mot_de_passe" :type="showPw ? 'text' : 'password'" auth required placeholder="••••••••">
              <template #suffix>
                <button type="button" class="eye-btn" @click="showPw = !showPw" tabindex="-1" aria-label="Afficher le mot de passe">
                  <Eye v-if="!showPw" :size="18" aria-hidden="true" />
                  <EyeOff v-else :size="18" aria-hidden="true" />
                </button>
              </template>
            </BaseInput>
            <!-- Indicateur de force -->
            <div class="strength-bar">
              <div v-for="i in 4" :key="i" class="strength-segment" :class="strengthClass(i)"></div>
            </div>
            <p class="hint">Minimum 8 caractères — majuscule, chiffre, symbole requis</p>
          </div>

          <div class="field">
            <label class="label">Rôle</label>
            <select v-model="form.role" class="ds-input ds-select ds-input--auth">
              <option value="developpeur">Développeur</option>
              <option value="testeur">Testeur (QA)</option>
            </select>
          </div>

          <div v-if="form.role === 'developpeur' || form.role === 'testeur'" class="github-box">
            <label class="label" style="color:#93c5fd">Lien GitHub / Portfolio</label>
            <BaseInput v-model="form.github_link" type="url" auth placeholder="https://github.com/username" class="github-input" />
          </div>

          <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="ds-page-feedback">{{ errorMessage }}</BaseAlert>

          <BaseButton type="submit" variant="primary" block :disabled="loading" class="submit-btn">
            <Loader2 v-if="loading" :size="18" class="spinner" aria-hidden="true" />
            <span v-else>Envoyer ma candidature →</span>
          </BaseButton>
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
import { CheckCircle2, Eye, EyeOff, Loader2, XCircle } from 'lucide-vue-next';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';

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
.form { display: flex; flex-direction: column; gap: 1.1rem; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.field :deep(.ds-input) { width: 100%; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }
.eye-btn { background: none; border: none; cursor: pointer; color: #475569; padding: 4px; display: flex; align-items: center; transition: color 0.15s; }
.eye-btn:hover { color: #94a3b8; }
.github-input { margin-top: 0.4rem; }
.strength-bar { display: flex; gap: 4px; margin-top: 6px; }
.strength-segment { height: 3px; flex: 1; border-radius: 2px; transition: background 0.3s; }
.seg-empty { background: #1e293b; border: 1px solid #334155; }
.seg-weak { background: #ef4444; }
.seg-fair { background: #f59e0b; }
.seg-good { background: #3b82f6; }
.seg-strong { background: #22c55e; }
.hint { font-size: 0.75rem; color: #475569; margin-top: 0.2rem; }
.field .ds-select { width: 100%; }
.github-box { background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2); border-radius: 8px; padding: 1rem; }
.submit-btn { margin-top: 0.25rem; }
.card-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #64748b; }
.link { color: #3b82f6; font-weight: 600; text-decoration: none; margin-left: 0.25rem; }
.link:hover { color: #60a5fa; }
.spinner { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>