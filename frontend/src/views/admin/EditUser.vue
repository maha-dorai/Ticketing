<template>
  <AppLayout>
    <PageHeader back-inline>
      <template #back>
        <button type="button" class="back-btn" @click="$router.push({ name: 'UserManagement' })">
          <ArrowLeft :size="18" aria-hidden="true" />
          Retour
        </button>
      </template>
      <template #title>
        <UserPen aria-hidden="true" />
        Modifier l'utilisateur
      </template>
      <template v-if="!loading" #subtitle>{{ form.prenom }} {{ form.nom }}</template>
    </PageHeader>

    <div class="page-content">
      <div v-if="loading" class="loading-state">
        <Loader2 :size="22" class="spin" aria-hidden="true" />
        Chargement…
      </div>

      <div v-else class="form-card">
        <BaseAlert v-if="errorMsg" variant="error" :icon="XCircle" class="ds-page-feedback">{{ errorMsg }}</BaseAlert>
        <BaseAlert v-if="successMsg" variant="success" :icon="CheckCircle2" class="ds-page-feedback">{{ successMsg }}</BaseAlert>

        <form class="form" @submit.prevent="sauvegarder">
          <div class="field">
            <label class="label">Nom</label>
            <BaseInput v-model="form.nom" type="text" required />
          </div>
          <div class="field">
            <label class="label">Prénom</label>
            <BaseInput v-model="form.prenom" type="text" required />
          </div>
          <div class="field">
            <label class="label">Email</label>
            <BaseInput v-model="form.email" type="email" required />
          </div>
          <div class="field">
            <label class="label">Rôle</label>
            <select v-model="form.role" class="ds-input ds-select" required>
              <option value="testeur">Testeur</option>
              <option value="developpeur">Développeur</option>
              <option value="chef_de_projet">Chef de projet</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>

          <div class="actions">
            <BaseButton type="button" variant="secondary" @click="$router.push({ name: 'UserManagement' })">
              Annuler
            </BaseButton>
            <BaseButton type="submit" variant="primary">Enregistrer</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, CheckCircle2, Loader2, UserPen, XCircle } from 'lucide-vue-next';
import api from '../../services/api';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import BaseAlert from '../../components/ui/BaseAlert.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';

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
.field .ds-input,
.field .ds-select {
  width: 100%;
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
.actions .ds-btn {
  flex: 1;
}
</style>
