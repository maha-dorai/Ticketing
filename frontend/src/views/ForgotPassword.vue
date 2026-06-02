<template>
  <AuthLayout>
      <AuthBrand subtitle="Réinitialisation du mot de passe" />
      <div class="ds-card ds-card--auth">
        <h2 class="ds-card__title">Mot de passe oublié ?</h2>
        <p class="ds-card__subtitle">Saisissez votre email et nous vous enverrons un lien de réinitialisation.</p>

        <AlertBanner v-if="successMessage" variant="success" class="alert alert-success">{{ successMessage }}</AlertBanner>
        <AlertBanner v-if="errorMessage" variant="error" class="alert alert-error">{{ errorMessage }}</AlertBanner>

        <form @submit.prevent="onSubmit" class="form">
          <BaseInput v-model="email" type="email" label="Adresse email" required placeholder="vous@exemple.com" />
          <BaseButton type="submit" :disabled="loading || !!successMessage" variant="primary" size="sm" :loading="loading">
            <span>{{ successMessage ? 'Lien envoyé' : 'Envoyer le lien →' }}</span>
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
import { Loader2 } from 'lucide-vue-next';
import api from '../services/api';
import AuthLayout from '../components/layout/AuthLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';
const email = ref(''); const loading = ref(false); const successMessage = ref(''); const errorMessage = ref('');
const onSubmit = async () => {
  loading.value = true; successMessage.value = ''; errorMessage.value = '';
  try { await api.post('/auth/forgot-password', { email: email.value }); successMessage.value = 'Lien envoyé ! Vérifiez votre boîte mail.'; }
  catch (err) { errorMessage.value = err.response?.data?.message || 'Une erreur est survenue.'; }
  finally { loading.value = false; }
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