<template>
  <AppLayout>
    <div class="page-header">
      <div class="header-left">
        <button type="button" class="back-btn" @click="$router.push({ name: 'UserManagement' })" aria-label="Retour à la gestion des utilisateurs">
          <ArrowLeft :size="18" aria-hidden="true" />
          Retour
        </button>
        <div>
          <h1 class="page-title">
            <UserPen class="page-title-icon" aria-hidden="true" />
            Modifier l'utilisateur
          </h1>
          <p v-if="!loading" class="page-sub">{{ form.prenom }} {{ form.nom }}</p>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div v-if="loading" class="loading-state">
        <Loader2 :size="22" class="spin" aria-hidden="true" />
        Chargement…
      </div>

      <div v-else class="form-card">
        <AlertBanner v-if="errorMsg" variant="error" class="alert alert-err">{{ errorMsg }}</AlertBanner>
        <AlertBanner v-if="successMsg" variant="success" class="alert alert-ok">{{ successMsg }}</AlertBanner>

        <form class="form" @submit.prevent="sauvegarder">
          <BaseInput v-model="form.nom" label="Nom" type="text" required />
          <BaseInput v-model="form.prenom" label="Prénom" type="text" required />
          <BaseInput v-model="form.email" label="Email" type="email" required />
          <div class="field">
            <label class="label">Rôle</label>
            <select v-model="form.role" class="ds-input" required>
              <option value="testeur">Testeur</option>
              <option value="developpeur">Développeur</option>
              <option value="chef_de_projet">Chef de projet</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>

          <div class="actions">
            <BaseButton type="button" variant="secondary" size="sm" @click="$router.push({ name: 'UserManagement' })">
              Annuler
            </BaseButton>
            <BaseButton type="submit" variant="primary" size="sm">Enregistrer</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Loader2, UserPen } from 'lucide-vue-next';
import api from '../../services/api';
import AppLayout from '../../components/layout/AppLayout.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import AlertBanner from '../../components/ui/AlertBanner.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const errorMsg = ref('');
const successMsg = ref('');
const form = ref({ nom: '', prenom: '', email: '', role: '' });

onMounted(async () => {
  try {
    const res = await api.get(`/users/${route.params.id}`);
    const user = res.data;
    form.value = { nom: user.nom, prenom: user.prenom, email: user.email, role: user.role };
  } catch {
    errorMsg.value = 'Erreur lors du chargement.';
  } finally {
    loading.value = false;
  }
});

const sauvegarder = async () => {
  errorMsg.value = '';
  successMsg.value = '';
  try {
    await api.put(`/users/${route.params.id}`, form.value);
    successMsg.value = 'Modifications enregistrées.';
    setTimeout(() => router.push({ name: 'UserManagement' }), 1200);
  } catch (err) {
    const errors = err.response?.data?.errors;
    errorMsg.value = errors
      ? Object.values(errors).flat().join(' | ')
      : err.response?.data?.message || 'Erreur lors de la mise à jour.';
  }
};
</script>

<style scoped>
.page-header {
  padding: 1.5rem 2.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: white;
}
.header-left {
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
}
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  margin-top: 0.25rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #64748b;
  font-size: 0.8125rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.15s;
}
.back-btn:hover {
  color: #1e293b;
  border-color: #cbd5e1;
  background: #f8fafc;
}
.page-title {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.02em;
}
.page-title-icon {
  width: 1.375rem;
  height: 1.375rem;
  color: var(--color-brand, #2563eb);
  flex-shrink: 0;
}
.page-sub {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0;
}
.page-content {
  padding: 2rem 2.5rem;
}
.form-card {
  max-width: 32rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.75rem;
}
.loading-state {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #94a3b8;
  font-size: 0.875rem;
}
.spin {
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.alert {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 1rem;
}
.alert-ok {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
}
.alert-err {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}
.form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #475569;
}
.actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
</style>
