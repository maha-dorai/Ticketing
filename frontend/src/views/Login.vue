<template>
  <AuthLayout>
      <AuthBrand subtitle="Plateforme de gestion de tickets" />
      <div class="ds-card ds-card--auth">
        <h2 class="ds-card__title">Connexion</h2>
        <AlertBanner v-if="errorMessage" variant="error" class="alert alert-error">{{ errorMessage }}</AlertBanner>
        <AlertBanner v-if="pendingMessage" variant="pending" class="alert alert-warn">{{ pendingMessage }}</AlertBanner>
        <form @submit.prevent="onSubmit" class="form">
          <BaseInput v-model="email" type="email" label="Adresse email" />
          <div class="field">
            <div class="label-row">
              <label class="label">Mot de passe</label>
              <router-link to="/forgot-password" class="forgot-link">Mot de passe oublié ?</router-link>
            </div>
            <div class="input-wrap">
              <input v-model="password" :type="show ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" />
              <button type="button" class="eye-btn" @click="show = !show" tabindex="-1" aria-label="Afficher le mot de passe">
                <Eye v-if="show" :size="18" :stroke-width="1.8" aria-hidden="true" />
                <EyeOff v-else :size="18" :stroke-width="1.8" aria-hidden="true" />
              </button>
            </div>
          </div>
          <BaseButton type="submit" :disabled="loading" variant="primary" size="sm" :loading="loading">
            <span>Se connecter →</span>
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
import { useAuthStore } from '../stores/authStore';
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next';
import AuthLayout from '../components/layout/AuthLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();
const email = ref(''); const password = ref(''); const show = ref(false);
const loading = ref(false); const errorMessage = ref(''); const pendingMessage = ref('');

const onSubmit = async () => {
  errorMessage.value = ''; pendingMessage.value = ''; loading.value = true;
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
  } finally { loading.value = false; }
};
</script>

<style scoped>
.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; margin-bottom: 1.25rem; }
.alert-error { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
.alert-warn { background: rgba(245,158,11,0.1); color: #fcd34d; border: 1px solid rgba(245,158,11,0.2); }
.form { display: flex; flex-direction: column; gap: 1.25rem; }
.field { display: flex; flex-direction: column; gap: 0.4rem; }
.label { font-size: 0.8125rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }
.label-row { display: flex; justify-content: space-between; align-items: center; }
.input-wrap { position: relative; }
.input { width: 100%; padding: 0.6875rem 0.875rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f1f5f9; font-size: 0.9375rem; font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s; outline: none; }
.input::placeholder { color: #475569; }
.input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.input-pr { padding-right: 2.75rem; }
.eye-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #475569; padding: 4px; border-radius: 4px; display: flex; align-items: center; transition: color 0.15s; }
.eye-btn:hover { color: #94a3b8; }
.forgot-link { font-size: 0.8125rem; color: #3b82f6; text-decoration: none; font-weight: 500; }
.forgot-link:hover { color: #60a5fa; }
.card-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #64748b; }
.link { color: #3b82f6; font-weight: 600; text-decoration: none; margin-left: 0.25rem; }
.link:hover { color: #60a5fa; }
.spinner { animation: spin 0.8s linear infinite; }
.op25 { opacity: 0.25; } .op75 { opacity: 0.75; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>