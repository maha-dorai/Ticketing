<template>
  <!-- [Sprint 1] Page de Connexion : Layout principal -->
  <AuthLayout>
      <AuthBrand subtitle="Plateforme de gestion de tickets" />
      <div class="card">
        <h2 class="card-title">Connexion</h2>

        <!-- Alertes pour les règles métier (Erreurs de mot de passe, Comptes en attente...) -->
        <AlertBanner v-if="errorMessage" variant="error" class="alert alert-error">{{ errorMessage }}</AlertBanner>
        <AlertBanner v-if="pendingMessage" variant="pending" class="alert alert-warn">{{ pendingMessage }}</AlertBanner>
        
        <form @submit.prevent="onSubmit" class="form">
          <!-- Champ Email -->
          <div class="field">
            <label class="label">Adresse email</label>
            <input v-model.trim="email" type="email"  class="input" />
          </div>

          <!-- Champ Mot de Passe -->
          <div class="field">
            <div class="label-row">
              <label class="label">Mot de passe</label>
              <router-link to="/forgot-password" class="forgot-link">Mot de passe oublié ?</router-link>
            </div>
            <div class="input-wrap">
              <!-- Bouton dynamique pour afficher/masquer le mot de passe -->
              <input v-model="password" :type="show ? 'text' : 'password'" required placeholder="••••••••" class="input input-pr" />
              <button type="button" class="eye-btn" @click="show = !show" tabindex="-1">
                <svg v-if="show" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary">
            <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="op25"/>
              <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="op75"/>
            </svg>
            <span v-else>Se connecter →</span>
          </button>
        </form>

        <div class="card-footer">
          Pas encore de compte ?
          <router-link to="/register" class="link">S'inscrire</router-link>
        </div>
      </div>
  </AuthLayout>
</template>

<script setup>
/**
 * [Sprint 1] Logique du composant de Connexion
 */
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore(); // Appel du Pinia Store

// Variables réactives (Mise à jour de l'UI instantanée)
const email = ref(''); const password = ref(''); const show = ref(false);
const loading = ref(false); const errorMessage = ref(''); const pendingMessage = ref('');

/**
 * Appelée quand l'utilisateur clique sur "Se connecter"
 */
const onSubmit = async () => {
  errorMessage.value = ''; pendingMessage.value = ''; loading.value = true;
  
  try {
    // Appel du service de connexion dans le Store
    const status = await authStore.login(email.value, password.value);
    
    if (status === 'success') {
      // Routage conditionnel selon le rôle de l'utilisateur
      const role = authStore.currentUser?.role;
      if (role === 'admin') router.push({ name: 'Dashboard' }); // Les admins vont sur les stats par défaut
      else if (role === 'chef_de_projet') router.push({ name: 'Dashboard' });
      else router.push({ name: 'Projects' }); // Les devs/testeurs vont sur la liste de leurs projets

    } else if (status === 'en_attente') pendingMessage.value = 'Votre compte est en attente de validation par un administrateur.';
    else if (status === 'rejete') errorMessage.value = 'Votre compte a été rejeté. Contactez l\'administrateur.';
    else if (status === 'desactive') errorMessage.value = 'Votre compte a été désactivé.';
    else errorMessage.value = 'Email ou mot de passe incorrect.';
  } finally { 
    loading.value = false; // Arrête l'animation de chargement
  }
};
</script>

<style scoped>
/* Styles esthétiques (Omis dans les commentaires pédagogiques car standards CSS) */
.card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.4); }
.card-title { font-size: 1.25rem; font-weight: 700; color: #f1f5f9; margin: 0 0 1.5rem; }
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
.btn-primary { width: 100%; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 0.9375rem; font-weight: 600; font-family: inherit; cursor: pointer; transition: background 0.2s, transform 0.1s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.25rem; }
.btn-primary:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.card-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #64748b; }
.link { color: #3b82f6; font-weight: 600; text-decoration: none; margin-left: 0.25rem; }
.link:hover { color: #60a5fa; }
.spinner { animation: spin 0.8s linear infinite; }
.op25 { opacity: 0.25; } .op75 { opacity: 0.75; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>