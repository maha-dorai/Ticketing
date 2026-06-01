<template>
  <AuthLayout>
      <AuthBrand subtitle="Plateforme de gestion de tickets" />
      <div class="card">
        <h2 class="card-title">Connexion</h2>
        <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="ds-page-feedback">{{ errorMessage }}</BaseAlert>
        <BaseAlert v-if="pendingMessage" variant="warning" :icon="Clock" class="ds-page-feedback">{{ pendingMessage }}</BaseAlert>
        <form @submit.prevent="onSubmit" class="form">
          <div class="field">
            <label class="label">Adresse email</label>
            <BaseInput v-model="email" type="email" auth />
          </div>
          <div class="field">
            <div class="label-row">
              <label class="label">Mot de passe</label>
              <router-link to="/forgot-password" class="forgot-link">Mot de passe oublié ?</router-link>
            </div>
            <BaseInput v-model="password" :type="show ? 'text' : 'password'" auth required placeholder="••••••••">
              <template #suffix>
                <button type="button" class="eye-btn" @click="show = !show" tabindex="-1" aria-label="Afficher le mot de passe">
                  <Eye v-if="!show" :size="18" aria-hidden="true" />
                  <EyeOff v-else :size="18" aria-hidden="true" />
                </button>
              </template>
            </BaseInput>
          </div>
          <BaseButton type="submit" variant="primary" block :disabled="loading" class="submit-btn">
            <Loader2 v-if="loading" :size="18" class="spinner" aria-hidden="true" />
            <span v-else>Se connecter →</span>
          </BaseButton>
        </form>
        <div class="card-footer">
          Pas encore de compte ?
          <router-link to="/register" class="link">S'inscrire</router-link>
        </div>
      </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Clock, Eye, EyeOff, Loader2, XCircle } from 'lucide-vue-next';
import { useAuthStore } from '../stores/authStore';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const show = ref(false);
const loading = ref(false);
const errorMessage = ref('');
const pendingMessage = ref('');

const onSubmit = async () => {
  errorMessage.value = '';
  pendingMessage.value = '';
  loading.value = true;
  try {
    const status = await authStore.login(email.value, password.value);
    if (status === 'success') {
      const role = authStore.currentUser?.role;
      if (role === 'admin') router.push({ name: 'Dashboard' });
      else if (role === 'chef_de_projet') router.push({ name: 'Dashboard' });
      else router.push({ name: 'Projects' });
    } else if (status === 'en_attente') pendingMessage.value = 'Votre compte est en attente de validation par un administrateur.';
    else if (status === 'rejete') errorMessage.value = 'Votre compte a été rejeté. Contactez l\'administrateur.';
    else if (status === 'desactive') errorMessage.value = 'Votre compte a été désactivé.';
    else errorMessage.value = 'Email ou mot de passe incorrect.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
.card-title { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin: 0 0 1.5rem; }
.form { display: flex; flex-direction: column; gap: 1.25rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.field :deep(.ds-input) { width: 100%; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }
.label-row { display: flex; justify-content: space-between; align-items: center; }
.eye-btn { background: none; border: none; cursor: pointer; color: #475569; padding: 4px; border-radius: 4px; display: flex; align-items: center; transition: color 0.15s; }
.eye-btn:hover { color: #94a3b8; }
.forgot-link { font-size: 0.8125rem; color: #3b82f6; text-decoration: none; font-weight: 500; }
.forgot-link:hover { color: #60a5fa; }
.submit-btn { margin-top: 0.25rem; }
.card-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #64748b; }
.link { color: #3b82f6; font-weight: 600; text-decoration: none; margin-left: 0.25rem; }
.link:hover { color: #60a5fa; }
.spinner { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
