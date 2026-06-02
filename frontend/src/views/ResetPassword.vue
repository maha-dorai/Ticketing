<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />
    <div class="ds-card ds-card--auth">
      <h2 class="ds-card__title">Nouveau mot de passe</h2>
      <p class="ds-card__subtitle">Saisissez et confirmez votre nouveau mot de passe.</p>

      <AlertBanner v-if="successMessage" variant="success" class="alert alert-success">{{ successMessage }}</AlertBanner>
      <AlertBanner v-if="errorMessage" variant="error" class="alert alert-error">{{ errorMessage }}</AlertBanner>

      <form @submit.prevent="onSubmit" class="form">
        <BaseInput
          v-model="mot_de_passe"
          type="password"
          label="Nouveau mot de passe"
          required
          placeholder="Min 8 car., majuscule, chiffre, symbole"
        />
        <BaseInput v-model="confirmation" type="password" label="Confirmer le mot de passe" required />
        <BaseButton type="submit" :disabled="loading" variant="primary" size="sm" :loading="loading">
          <span>Réinitialiser</span>
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
import { Loader2 } from 'lucide-vue-next';
import api from '../services/api';
import AuthLayout from '../components/layout/AuthLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';
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
.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1.25rem; }
.alert-error { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
.alert-success { background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
.form { display: flex; flex-direction: column; gap: 1.25rem; }
.card-footer { text-align: center; margin-top: 1.5rem; }
.back-link { font-size: 0.875rem; color: #64748b; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #94a3b8; }
.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
