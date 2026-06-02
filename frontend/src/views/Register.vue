<template>
  <AuthLayout>
    <AuthBrand subtitle="Créer un compte — soumis à validation" />

    <div class="auth-card">
      <h2 class="auth-card__title">Inscription</h2>

      <BaseAlert v-if="successMessage" variant="success" :icon="CheckCircle2" class="auth-alert">
        {{ successMessage }}
      </BaseAlert>

      <form v-else @submit.prevent="registerCandidate" class="auth-form">
        <div class="auth-field">
          <label class="auth-label">Nom</label>
          <input v-model="form.nom" type="text" required class="auth-input" />
        </div>

        <div class="auth-field">
          <label class="auth-label">Prénom</label>
          <input v-model="form.prenom" type="text" required class="auth-input" />
        </div>

        <div class="auth-field">
          <label class="auth-label">Adresse email</label>
          <input v-model="form.email" type="email" required class="auth-input" />
        </div>

        <div class="auth-field">
          <label class="auth-label">Mot de passe</label>
          <div class="auth-input-wrap">
            <input
              v-model="form.mot_de_passe"
              :type="showPw ? 'text' : 'password'"
              required
              placeholder="••••••••"
              class="auth-input auth-input--pw"
            />
            <button type="button" class="auth-eye-btn" @click="showPw = !showPw" tabindex="-1" aria-label="Afficher le mot de passe">
              <Eye v-if="!showPw" :size="18" aria-hidden="true" />
              <EyeOff v-else :size="18" aria-hidden="true" />
            </button>
          </div>
          <p class="auth-hint">Minimum 8 caractères — majuscule, chiffre, symbole requis</p>
        </div>

        <div class="auth-field">
          <label class="auth-label">Rôle</label>
          <select v-model="form.role" required class="auth-input auth-select">
            <option value="developpeur">Développeur</option>
            <option value="testeur">Testeur (QA)</option>
          </select>
        </div>

        <div v-if="form.role === 'developpeur' || form.role === 'testeur'" class="auth-field">
          <label class="auth-label">Lien GitHub / Portfolio</label>
          <input
            v-model="form.github_link"
            type="url"
            required
            placeholder="https://github.com/username"
            class="auth-input"
          />
        </div>

        <div v-if="errorMessage" class="auth-alert auth-alert--error">
          <XCircle :size="16" aria-hidden="true" />
          {{ errorMessage }}
        </div>

        <button type="submit" :disabled="loading" class="auth-btn">
          <span v-if="loading" class="auth-btn__spinner" aria-hidden="true"></span>
          <span v-else>Envoyer ma candidature</span>
        </button>
      </form>

      <div class="auth-card__footer">
        Déjà un compte ?
        <router-link to="/login" class="auth-link">Se connecter</router-link>
      </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useRouter } from 'vue-router';
import { CheckCircle2, Eye, EyeOff, XCircle } from 'lucide-vue-next';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';

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
  margin: 0 0 1.5rem;
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

.auth-hint {
  font-size: 0.75rem;
  color: #475569;
  margin: 0.2rem 0 0;
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
  appearance: none;
}

.auth-input::placeholder {
  color: #475569;
}

.auth-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Password field with eye button */
.auth-input-wrap {
  position: relative;
}

.auth-input--pw {
  padding-right: 2.75rem;
}

.auth-eye-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
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

.auth-eye-btn:hover { color: #94a3b8; }

/* Select */
.auth-select {
  cursor: pointer;
}

/* Alerts */
.auth-alert {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
}

.auth-alert--error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  text-align: center;
  margin-top: 1.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.auth-link {
  color: #3b82f6;
  font-weight: 600;
  text-decoration: none;
  margin-left: 0.25rem;
}

.auth-link:hover { color: #60a5fa; }
</style>