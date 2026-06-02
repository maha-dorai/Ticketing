<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />
    <div class="ds-card ds-card--auth auth-card">
      <h2 class="auth-title">Mot de passe oublié ?</h2>
      <p class="auth-sub">Saisissez votre email et nous vous enverrons un lien de réinitialisation.</p>

      <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="auth-alert">{{ successMessage }}</BaseAlert>
      <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="auth-alert">{{ errorMessage }}</BaseAlert>

      <form @submit.prevent="onSubmit" class="ds-form auth-form">
        <BaseInput v-model="email" label="Adresse email" type="email" required placeholder="vous@exemple.com" auth />
        <BaseButton type="submit" variant="primary" block :loading="loading" :disabled="!!successMessage">
          <CheckCircle2 v-if="successMessage" :size="18" aria-hidden="true" />
          <Send v-else :size="18" aria-hidden="true" />
          {{ successMessage ? 'Lien envoyé' : 'Envoyer le lien' }}
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

<script setup>
import { ref } from 'vue';
import api from '../services/api';
import { ArrowLeft, CheckCircle2, Send, XCircle } from "lucide-vue-next";
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';
const email = ref(''); const loading = ref(false); const successMessage = ref(''); const errorMessage = ref('');
const onSubmit = async () => {
  loading.value = true; successMessage.value = ''; errorMessage.value = '';
  try { await api.post('/auth/forgot-password', { email: email.value }); successMessage.value = 'Lien envoyé ! Vérifiez votre boîte mail.'; }
  catch (err) { errorMessage.value = err.response?.data?.message || 'Une erreur est survenue.'; }
  finally { loading.value = false; }
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
</style>
