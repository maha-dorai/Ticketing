<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />

    <div class="ds-card ds-card--auth register-card">
      <h2 class="register-title">Mot de passe oublié</h2>
      <p class="register-sub">
        Saisissez votre adresse email. Nous vous enverrons un lien pour réinitialiser votre mot de passe.
      </p>

      <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="auth-alert">
        {{ successMessage }}
      </BaseAlert>

      <form v-else @submit.prevent="onSubmit" class="ds-form register-form">
        <BaseInput
          v-model="email"
          label="Adresse email"
          type="email"
          auth
          required
          placeholder="vous@exemple.com"
        />

        <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="auth-alert">
          {{ errorMessage }}
        </BaseAlert>

        <BaseButton type="submit" variant="primary" block :loading="loading" class="register-submit">
          Envoyer le lien →
        </BaseButton>
      </form>

      <div class="register-footer">
        <router-link :to="{ name: 'Login' }" class="register-link register-link--back">
          <ArrowLeft :size="14" aria-hidden="true" />
          Retour à la connexion
        </router-link>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import api from '../services/api';
import { ArrowLeft, CheckCircle2, XCircle } from 'lucide-vue-next';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';

const email = ref('');
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const onSubmit = async () => {
  loading.value = true;
  successMessage.value = '';
  errorMessage.value = '';
  try {
    await api.post('/auth/forgot-password', { email: email.value });
    successMessage.value = 'Lien envoyé ! Vérifiez votre boîte mail.';
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Une erreur est survenue.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.register-card {
  border-radius: 16px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.register-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.5rem;
}

.register-sub {
  margin: 0 0 1.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #64748b;
}

.register-form {
  gap: 1.25rem;
}

.register-form :deep(.ds-field) {
  gap: 0.4rem;
}

.register-form :deep(.ds-field__label) {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 0.02em;
}

.auth-alert {
  margin-bottom: 1.25rem;
  border-radius: 8px;
}

.auth-alert.ds-alert--success {
  color: #86efac;
  background: rgba(34, 197, 94, 0.1);
  border-color: rgba(34, 197, 94, 0.2);
}

.auth-alert.ds-alert--error {
  color: #fca5a5;
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.2);
}

.register-submit {
  margin-top: 0.25rem;
}

.register-footer {
  margin-top: 1.5rem;
  text-align: center;
}

.register-link {
  display: inline-flex;
  gap: 0.375rem;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 600;
  color: #3b82f6;
  text-decoration: none;
}

.register-link:hover {
  color: #60a5fa;
}

.register-link--back {
  color: #64748b;
  font-weight: 500;
}

.register-link--back:hover {
  color: #94a3b8;
}
</style>