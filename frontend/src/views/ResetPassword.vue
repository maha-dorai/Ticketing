<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />
    <div class="card">
      <h2 class="card-title">Nouveau mot de passe</h2>
      <p class="card-sub">Saisissez et confirmez votre nouveau mot de passe.</p>

      <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="ds-page-feedback">{{ successMessage }}</BaseAlert>
      <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="ds-page-feedback">{{ errorMessage }}</BaseAlert>

      <form @submit.prevent="onSubmit" class="form">
        <div class="field">
          <label class="label">Nouveau mot de passe</label>
          <BaseInput
            v-model="mot_de_passe"
            type="password"
            auth
            required
            placeholder="Min 8 car., majuscule, chiffre, symbole"
          />
        </div>
        <div class="field">
          <label class="label">Confirmer le mot de passe</label>
          <BaseInput v-model="confirmation" type="password" auth required />
        </div>
        <BaseButton type="submit" variant="primary" block :disabled="loading">
          <Loader2 v-if="loading" :size="18" class="spin" aria-hidden="true" />
          <span v-else>Réinitialiser</span>
        </BaseButton>
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
import { CheckCircle2, Loader2, XCircle } from 'lucide-vue-next';
import api from '../services/api';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';

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
.form { display: flex; flex-direction: column; gap: 1.25rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.field :deep(.ds-input) { width: 100%; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; }
.card-footer { text-align: center; margin-top: 1.5rem; }
.back-link { font-size: 0.875rem; color: #64748b; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #94a3b8; }
.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
