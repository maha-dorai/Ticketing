<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />
    <div class="ds-card ds-card--auth auth-card">
      <h2 class="auth-title">Nouveau mot de passe</h2>
      <p class="auth-sub">Saisissez et confirmez votre nouveau mot de passe.</p>

      <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="auth-alert">{{ successMessage }}</BaseAlert>
      <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="auth-alert">{{ errorMessage }}</BaseAlert>

      <form @submit.prevent="onSubmit" class="ds-form auth-form">
        <BaseInput
          v-model="mot_de_passe"
          label="Nouveau mot de passe"
          :type="showPassword ? 'text' : 'password'"
          required
          placeholder="Min 8 car., majuscule, chiffre, symbole"
          auth
        >
          <template #suffix>
            <BaseButton type="button" variant="ghost" size="sm" icon class="eye-btn" @click="showPassword = !showPassword">
              <EyeOff v-if="showPassword" :size="18" aria-hidden="true" />
              <Eye v-else :size="18" aria-hidden="true" />
            </BaseButton>
          </template>
        </BaseInput>

        <BaseInput
          v-model="confirmation"
          label="Confirmer le mot de passe"
          :type="showConfirmation ? 'text' : 'password'"
          required
          placeholder="••••••••"
          auth
        >
          <template #suffix>
            <BaseButton type="button" variant="ghost" size="sm" icon class="eye-btn" @click="showConfirmation = !showConfirmation">
              <EyeOff v-if="showConfirmation" :size="18" aria-hidden="true" />
              <Eye v-else :size="18" aria-hidden="true" />
            </BaseButton>
          </template>
        </BaseInput>

        <BaseButton type="submit" variant="primary" block :loading="loading">
          <KeyRound :size="18" aria-hidden="true" />
          Réinitialiser
        </BaseButton>
      </form>

      <div class="auth-footer">
        <BaseButton tag="router-link" :to="{ name: 'Login' }" variant="ghost" size="sm">
          <ArrowLeft :size="16" aria-hidden="true" />
          Retour à la connexion
        </BaseButton>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { ArrowLeft, CheckCircle2, Eye, EyeOff, KeyRound, XCircle } from "lucide-vue-next";
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
const showPassword = ref(false);
const showConfirmation = ref(false);

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
.auth-card { border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
.auth-title { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin: 0 0 0.5rem; }
.auth-sub { font-size: 0.875rem; color: #64748b; margin: 0 0 1.5rem; line-height: 1.5; }
.auth-form { gap: 1.25rem; }
.auth-form :deep(.ds-field) { gap: 0.4rem; }
.auth-form :deep(.ds-field__label) { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }
.auth-alert { border-radius: 8px; margin-bottom: 1.25rem; }
.auth-alert.ds-alert--error { background: rgba(239,68,68,0.1); color: #fca5a5; border-color: rgba(239,68,68,0.2); }
.auth-alert.ds-alert--success { background: rgba(34,197,94,0.1); color: #86efac; border-color: rgba(34,197,94,0.2); }
.auth-footer { text-align: center; margin-top: 1.5rem; }
.eye-btn { color: #475569; }
.eye-btn:hover { color: #94a3b8; }
</style>
