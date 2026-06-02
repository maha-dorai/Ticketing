<template>
  <AppLayout>
    <div class="ds-app__content">
      <div class="ds-page ds-page--narrow">
        <header class="ds-page-header">
          <div>
            <h1 class="ds-page-header__title">Profile</h1>
            <p class="ds-page-header__subtitle">Manage your account settings and security preferences.</p>
          </div>
          <BaseButton type="button" variant="secondary" @click="$router.push({ name: 'MyStats' })">
            <TrendingUp :size="16" aria-hidden="true" />
            Mes statistiques
          </BaseButton>
        </header>

        <div class="ds-profile-stack">
          <section class="ds-card">
            <div class="ds-card__header">
              <div>
                <h2 class="ds-card__title">Personal information</h2>
                <p class="ds-card__subtitle">Update the profile details attached to your workspace account.</p>
              </div>
              <BaseButton v-if="!editingInfo && !isManagerRole" type="button" variant="ghost" size="sm" @click="startEdit">
                <Pencil :size="16" aria-hidden="true" />
                Edit
              </BaseButton>
            </div>

            <form @submit.prevent="saveInfo">
              <div class="ds-card__body">
                <BaseAlert v-if="infoMsg" :variant="infoOk ? 'success' : 'error'" :icon="infoOk ? CheckCircle2 : XCircle" class="ds-profile-feedback">
                  {{ infoMsg }}
                </BaseAlert>

                <div class="ds-form">
                  <template v-if="editingInfo">
                    <BaseInput v-model="infoForm.prenom" label="Prénom" required placeholder="Prénom" />
                    <BaseInput v-model="infoForm.nom" label="Nom" required placeholder="Nom" />
                    <BaseInput
                      v-if="isDeveloper"
                      v-model="infoForm.github_link"
                      label="GitHub"
                      type="url"
                      placeholder="https://github.com/votre-profil"
                    />
                  </template>

                  <template v-else>
                    <BaseInput :model-value="user?.prenom || ''" label="Prénom" disabled />
                    <BaseInput :model-value="user?.nom || ''" label="Nom" disabled />
                    <BaseInput :model-value="roleLabel" label="Rôle" disabled />
                    <BaseInput
                      v-if="isDeveloper"
                      :model-value="user?.github_link || 'Non renseigné'"
                      label="GitHub"
                      type="url"
                      disabled
                    />
                  </template>
                </div>
              </div>

              <div v-if="editingInfo" class="ds-card__footer">
                <BaseButton type="button" variant="ghost" @click="cancelEditInfo">Cancel</BaseButton>
                <BaseButton type="submit" variant="primary" :loading="infoL">Save changes</BaseButton>
              </div>
            </form>
          </section>

          <section v-if="!isManagerRole" class="ds-card">
            <div class="ds-card__header">
              <div>
                <h2 class="ds-card__title">Email address</h2>
                <p class="ds-card__subtitle">Change the email address used to sign in to your account.</p>
              </div>
            </div>

            <form @submit.prevent="changeEm">
              <div class="ds-card__body">
                <BaseAlert v-if="emMsg" :variant="emOk ? 'success' : 'error'" :icon="emOk ? CheckCircle2 : XCircle" class="ds-profile-feedback">
                  {{ emMsg }}
                </BaseAlert>

                <div class="ds-form">
                  <BaseInput :model-value="user?.email || ''" label="Current email" type="email" disabled />
                  <BaseInput v-model="em.email" label="New email" type="email" required placeholder="nouveau@exemple.com" />
                  <BaseInput
                    v-model="em.mdp"
                    label="Password confirmation"
                    :type="s4 ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                  >
                    <template #suffix>
                      <BaseButton type="button" variant="ghost" size="sm" icon @click="s4 = !s4">
                        <EyeOff v-if="s4" :size="18" aria-hidden="true" />
                        <Eye v-else :size="18" aria-hidden="true" />
                      </BaseButton>
                    </template>
                  </BaseInput>
                </div>
              </div>

              <div class="ds-card__footer">
                <BaseButton type="submit" variant="primary" :loading="emL">Update email</BaseButton>
              </div>
            </form>
          </section>

          <section v-if="!isManagerRole" class="ds-card">
            <div class="ds-card__header">
              <div>
                <h2 class="ds-card__title">Password</h2>
                <p class="ds-card__subtitle">Use a strong password to keep your account secure.</p>
              </div>
            </div>

            <form @submit.prevent="changePw">
              <div class="ds-card__body">
                <BaseAlert v-if="pwMsg" :variant="pwOk ? 'success' : 'error'" :icon="pwOk ? CheckCircle2 : XCircle" class="ds-profile-feedback">
                  {{ pwMsg }}
                </BaseAlert>

                <div class="ds-form">
                  <BaseInput
                    v-model="pw.ancien"
                    label="Current password"
                    :type="s1 ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                  >
                    <template #suffix>
                      <BaseButton type="button" variant="ghost" size="sm" icon @click="s1 = !s1">
                        <EyeOff v-if="s1" :size="18" aria-hidden="true" />
                        <Eye v-else :size="18" aria-hidden="true" />
                      </BaseButton>
                    </template>
                  </BaseInput>

                  <BaseInput
                    v-model="pw.nouveau"
                    label="New password"
                    :type="s2 ? 'text' : 'password'"
                    required
                    placeholder="Min 8 car., majuscule, chiffre, symbole"
                  >
                    <template #suffix>
                      <BaseButton type="button" variant="ghost" size="sm" icon @click="s2 = !s2">
                        <EyeOff v-if="s2" :size="18" aria-hidden="true" />
                        <Eye v-else :size="18" aria-hidden="true" />
                      </BaseButton>
                    </template>
                  </BaseInput>

                  <BaseInput
                    v-model="pw.confirm"
                    label="Confirm new password"
                    :type="s3 ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                    :error="pw.confirm && pw.nouveau !== pw.confirm ? 'Les mots de passe ne correspondent pas' : ''"
                  >
                    <template #suffix>
                      <BaseButton type="button" variant="ghost" size="sm" icon @click="s3 = !s3">
                        <EyeOff v-if="s3" :size="18" aria-hidden="true" />
                        <Eye v-else :size="18" aria-hidden="true" />
                      </BaseButton>
                    </template>
                  </BaseInput>
                </div>
              </div>

              <div class="ds-card__footer">
                <BaseButton type="submit" variant="primary" :loading="pwL" :disabled="pwL || (pw.confirm && pw.nouveau !== pw.confirm)">
                  Update password
                </BaseButton>
              </div>
            </form>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import {
  CheckCircle2,
  Eye,
  EyeOff,
  Pencil,
  TrendingUp,
  XCircle,
} from "lucide-vue-next";
import AppLayout from '../../components/layout/AppLayout.vue';
import BaseAlert from '../../components/ui/BaseAlert.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';

const authStore = useAuthStore();
const user = computed(() => authStore.currentUser);
const roleLabel = computed(() => ({ admin: 'Administrateur', chef_de_projet: 'Chef de Projet', developpeur: 'Développeur', testeur: 'Testeur' }[user.value?.role] || ''));

// Admin et chef_de_projet : profil en lecture seule (pas de modification)
const isManagerRole = computed(() => ['admin', 'chef_de_projet'].includes(user.value?.role));
// Lien GitHub : affiché uniquement pour les développeurs
const isDeveloper = computed(() => user.value?.role === 'developpeur');

// ── Section info personnelle ──
const editingInfo = ref(false);
const infoMsg = ref(''), infoOk = ref(true), infoL = ref(false);
const infoForm = ref({ prenom: '', nom: '', github_link: '' });

const cancelEditInfo = () => { editingInfo.value = false; infoMsg.value = ''; };
const startEdit = () => {
  infoForm.value = { prenom: user.value?.prenom || '', nom: user.value?.nom || '', github_link: user.value?.github_link || '' };
  editingInfo.value = true;
};

const saveInfo = async () => {
  infoL.value = true; infoMsg.value = '';
  try {
    await api.put('/users/profile', infoForm.value);
    if (authStore.currentUser) {
      authStore.currentUser.prenom = infoForm.value.prenom;
      authStore.currentUser.nom = infoForm.value.nom;
      authStore.currentUser.github_link = infoForm.value.github_link;
      localStorage.setItem('user', JSON.stringify(authStore.currentUser));
    }
    infoOk.value = true; infoMsg.value = 'Informations mises à jour';
    editingInfo.value = false;
  } catch (e) { infoOk.value = false; infoMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { infoL.value = false; }
};

// ── Mot de passe ──
const pw = ref({ ancien: '', nouveau: '', confirm: '' }), pwMsg = ref(''), pwOk = ref(true), pwL = ref(false);
const s1 = ref(false), s2 = ref(false), s3 = ref(false), s4 = ref(false);

const changePw = async () => {
  if (pw.value.nouveau !== pw.value.confirm) { pwMsg.value = 'Les mots de passe ne correspondent pas.'; pwOk.value = false; return; }
  pwL.value = true; pwMsg.value = '';
  try { await api.put('/users/change-password', { ancien_mot_de_passe: pw.value.ancien, nouveau_mot_de_passe: pw.value.nouveau }); authStore.clearForcePasswordChange(); pwOk.value = true; pwMsg.value = 'Mot de passe modifié'; pw.value = { ancien: '', nouveau: '', confirm: '' }; }
  catch (e) { pwOk.value = false; pwMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { pwL.value = false; }
};

// ── Email ──
const em = ref({ email: '', mdp: '' }), emMsg = ref(''), emOk = ref(true), emL = ref(false);
const changeEm = async () => {
  emL.value = true; emMsg.value = '';
  try { await api.put('/users/change-email', { new_email: em.value.email, mot_de_passe: em.value.mdp }); if (authStore.currentUser) { authStore.currentUser.email = em.value.email; localStorage.setItem('user', JSON.stringify(authStore.currentUser)); } emOk.value = true; emMsg.value = 'Email modifié'; em.value = { email: '', mdp: '' }; }
  catch (e) { emOk.value = false; emMsg.value = e.response?.data?.message || 'Erreur.'; }
  finally { emL.value = false; }
};
</script>

<style scoped>
.ds-profile-stack {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.ds-profile-feedback {
  margin-bottom: var(--space-5);
}
</style>
