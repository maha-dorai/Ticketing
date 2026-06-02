<template>
  <AuthLayout>
    <AuthBrand subtitle="Créer un compte — soumis à validation" />
    <div class="ds-card ds-card--auth register-card">
      <h2 class="register-title">Inscription</h2>

      <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="auth-alert">{{ successMessage }}</BaseAlert>

      <form v-else @submit.prevent="registerCandidate" class="ds-form register-form">
        <BaseInput v-model="form.nom" label="Nom" type="text" auth />

        <BaseInput v-model="form.prenom" label="Prénom" type="text" auth />

        <BaseInput v-model="form.email" label="Adresse email" type="email" auth />

        <BaseInput
          v-model="form.mot_de_passe"
          label="Mot de passe"
          :type="showPw ? 'text' : 'password'"
          auth
          required
          placeholder="••••••••"
          hint="Minimum 8 caractères — majuscule, chiffre, symbole requis"
        >
          <template #suffix>
            <button type="button" class="eye-btn" @click="showPw = !showPw" tabindex="-1" aria-label="Afficher le mot de passe">
              <Eye v-if="!showPw" :size="18" aria-hidden="true" />
              <EyeOff v-else :size="18" aria-hidden="true" />
            </button>
          </template>
        </BaseInput>

        <div class="ds-field">
          <label class="ds-field__label">Rôle</label>
          <select v-model="form.role" class="ds-input ds-select ds-input--auth">
            <option value="developpeur">Développeur</option>
            <option value="testeur">Testeur (QA)</option>
          </select>
        </div>

        <BaseInput
          v-if="form.role === 'developpeur' || form.role === 'testeur'"
          v-model="form.github_link"
          label="Lien GitHub / Portfolio"
          type="url"
          auth
          placeholder="https://github.com/username"
        />

        <BaseAlert v-if="errorMessage" variant="error" :icon="XCircle" class="auth-alert">{{ errorMessage }}</BaseAlert>

        <BaseButton type="submit" variant="primary" block :loading="loading" class="register-submit">
          Envoyer ma candidature →
        </BaseButton>
      </form>

      <div class="register-footer">
        Déjà un compte ?
        <router-link to="/login" class="register-link">Se connecter</router-link>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useRouter } from 'vue-router';
import { CheckCircle2, Eye, EyeOff, XCircle } from "lucide-vue-next";
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';

const authStore = useAuthStore();
const router = useRouter();

const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);
const showPw = ref(false);

const form = ref({ nom: '', prenom: '', email: '', mot_de_passe: '', role: 'developpeur', github_link: '' });

const registerCandidate = async () => {
  errorMessage.value = ''; loading.value = true;
  try {
    await authStore.register({
      nom: form.value.nom, prenom: form.value.prenom, email: form.value.email,
      mot_de_passe: form.value.mot_de_passe, role: form.value.role,
      github_link: (form.value.role === 'developpeur' || form.value.role === 'testeur') ? form.value.github_link : null,
    });
    successMessage.value = 'Compte créé ! Votre demande est en attente de validation.';
    setTimeout(() => router.push({ name: 'Login' }), 2500);
  } catch (err) {
    const errors = err.response?.data?.errors;
    errorMessage.value = errors ? String(Object.values(errors).flat()[0]) : err.response?.data?.message || 'Une erreur est survenue.';
  } finally { loading.value = false; }
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
  margin: 0 0 1.5rem;
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

.register-form :deep(.ds-field__hint) {
  color: #475569;
  margin: 0.2rem 0 0;
}

.eye-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #475569;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  transition: color 0.15s;
}

.eye-btn:hover { color: #94a3b8; }

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
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.register-link {
  color: #3b82f6;
  font-weight: 600;
  text-decoration: none;
  margin-left: 0.25rem;
}

.register-link:hover {
  color: #60a5fa;
}
</style>
