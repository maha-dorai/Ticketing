<template>
  <AuthLayout>
      <AuthBrand subtitle="Réinitialisation du mot de passe" />
      <div class="card">
        <h2 class="card-title">Mot de passe oublié ?</h2>
        <p class="card-sub">Saisissez votre email et nous vous enverrons un lien de réinitialisation.</p>

        <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="ds-page-feedback">{{ successMessage }}</BaseAlert>
        <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="ds-page-feedback">{{ errorMessage }}</BaseAlert>

        <form @submit.prevent="onSubmit" class="form">
          <div class="field">
            <label class="label">Adresse email</label>
            <BaseInput v-model="email" type="email" auth required placeholder="vous@exemple.com" />
          </div>
          <BaseButton type="submit" variant="primary" block :disabled="loading || !!successMessage">
            <Loader2 v-if="loading" :size="18" class="spin" aria-hidden="true" />
            <span v-else>{{ successMessage ? 'Lien envoyé' : 'Envoyer le lien →' }}</span>
          </BaseButton>
        </form>

        <div class="card-footer">
          <router-link to="/login" class="back-link">← Retour à la connexion</router-link>
        </div>
      </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { CheckCircle2, Loader2, XCircle } from 'lucide-vue-next';
import api from '../services/api';
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
