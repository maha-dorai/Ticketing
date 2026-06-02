<template>
  <AuthLayout>
    <AuthBrand subtitle="Réinitialisation du mot de passe" />

    <div class="auth-card">
      <h2 class="auth-card__title">Mot de passe oublié</h2>
      <p class="auth-card__sub">
        Saisissez votre adresse email. Nous vous enverrons un lien pour réinitialiser votre mot de passe.
      </p>

      <div v-if="successMessage" class="auth-alert auth-alert--success">
        <CheckCircle2 :size="16" aria-hidden="true" />
        {{ successMessage }}
      </div>

      <form v-else @submit.prevent="onSubmit" class="auth-form">
        <div class="auth-field">
          <label class="auth-label">Adresse email</label>
          <input
            v-model="email"
            type="email"
            required
            placeholder="vous@exemple.com"
            class="auth-input"
          />
        </div>

        <div v-if="errorMessage" class="auth-alert auth-alert--error">
          <XCircle :size="16" aria-hidden="true" />
          {{ errorMessage }}
        </div>

        <button type="submit" :disabled="loading" class="auth-btn">
          <span v-if="loading" class="auth-btn__spinner" aria-hidden="true"></span>
          <span v-else>Envoyer le lien</span>
        </button>
      </form>

      <div class="auth-card__footer">
        <router-link :to="{ name: 'Login' }" class="auth-back-link">
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
/* Card — dark surface identical to Login.vue */
.auth-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.auth-card__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0 0 0.5rem;
}

.auth-card__sub {
  margin: 0 0 1.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #64748b;
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.auth-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.auth-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 0.02em;
}

/* Input — dark like Login.vue */
.auth-input {
  width: 100%;
  padding: 0.6875rem 0.875rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 0.9375rem;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

.auth-input::placeholder { color: #475569; }

.auth-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Alerts */
.auth-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 1rem;
}

.auth-alert--success {
  background: rgba(34, 197, 94, 0.1);
  color: #86efac;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.auth-alert--error {
  background: rgba(239, 68, 68, 0.1);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Submit button */
.auth-btn {
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

.auth-btn:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.auth-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.auth-btn__spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.65s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Footer */
.auth-card__footer {
  margin-top: 1.5rem;
  text-align: center;
}

.auth-back-link {
  display: inline-flex;
  gap: 0.375rem;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  text-decoration: none;
  transition: color 0.15s;
}

.auth-back-link:hover { color: #94a3b8; }
</style>