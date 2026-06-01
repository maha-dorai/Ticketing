<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />
    <div class="card">
      <h2 class="card-title">Nouveau mot de passe</h2>
      <p class="card-sub">Saisissez et confirmez votre nouveau mot de passe.</p>

      <AlertBanner v-if="successMessage" variant="success" class="alert alert-success">{{ successMessage }}</AlertBanner>
      <AlertBanner v-if="errorMessage" variant="error" class="alert alert-error">{{ errorMessage }}</AlertBanner>

      <form @submit.prevent="onSubmit" class="form">
        <div class="field">
          <label class="label">Nouveau mot de passe</label>
          <input
            v-model="mot_de_passe"
            type="password"
            required
            class="input"
            placeholder="Min 8 car., majuscule, chiffre, symbole"
          />
        </div>
        <div class="field">
          <label class="label">Confirmer le mot de passe</label>
          <input v-model="confirmation" type="password" required class="input" />
        </div>
        <button type="submit" :disabled="loading" class="btn-primary">
          <Loader2 v-if="loading" :size="18" class="spin" aria-hidden="true" />
          <span v-else>Réinitialiser</span>
        </button>
      </form>

      <div class="card-footer">
        <router-link to="/login" class="back-link">← Retour à la connexion</router-link>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Loader2 } from 'lucide-vue-next';
import api from '../services/api';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';

const route = useRoute();
const router = useRouter();

const mot_de_passe = ref('');
const confirmation = ref('');
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const onSubmit = async () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (mot_de_passe.value !== confirmation.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas.';
    return;
  }

  loading.value = true;
  try {
    const token = route.params.token as string;
    await api.post(`/auth/reset-password/${token}`, {
      mot_de_passe: mot_de_passe.value,
    });
    successMessage.value = 'Mot de passe mis à jour. Redirection…';
    setTimeout(() => router.push({ name: 'Login' }), 2000);
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string } } };
    errorMessage.value = e.response?.data?.message || 'Lien invalide ou expiré.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
.card-title { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin: 0 0 0.5rem; }
.card-sub { font-size: 0.875rem; color: #64748b; margin: 0 0 1.5rem; line-height: 1.5; }
.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1.25rem; }
.alert-error { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
.alert-success { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
.form { display: flex; flex-direction: column; gap: 1.25rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; }
.input { width: 100%; padding: 0.6875rem 0.875rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 0.9375rem; font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
.input::placeholder { color: #475569; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.btn-primary { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.btn-primary:hover:not(:disabled) { background: #2563eb; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.card-footer { text-align: center; margin-top: 1.5rem; }
.back-link { font-size: 0.875rem; color: #64748b; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #94a3b8; }
.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
