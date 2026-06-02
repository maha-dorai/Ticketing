<template>
  <AuthLayout wide>
      <AuthBrand title="Sécurité requise" />
      <div class="ds-card ds-card--auth auth-card">
        <div class="warn-box">
          <AlertTriangle class="warn-icon" aria-hidden="true" />
          <p>Votre compte a été créé par un administrateur. Vous devez définir votre propre mot de passe avant de continuer.</p>
        </div>

        <AlertBanner v-if="message" :variant="ok ? 'success' : 'error'" class="auth-alert" :class="ok ? 'auth-alert-success' : 'auth-alert-error'">{{ message }}</AlertBanner>

        <form @submit.prevent="submit" class="ds-form auth-form">
          <BaseInput v-model="f.ancien" :type="s1 ? 'text' : 'password'" label="Mot de passe temporaire (reçu par email)" required placeholder="••••••••" auth>
            <template #suffix>
              <button type="button" class="eye" @click="s1 = !s1" tabindex="-1" aria-label="Afficher le mot de passe temporaire">
                <EyeOff v-if="s1" :size="17" aria-hidden="true" />
                <Eye v-else :size="17" aria-hidden="true" />
              </button>
            </template>
          </BaseInput>

          <div>
            <BaseInput v-model="f.nouveau" :type="s2 ? 'text' : 'password'" label="Nouveau mot de passe" required placeholder="Min 8 car., MAJ, chiffre, symbole" auth>
              <template #suffix>
                <button type="button" class="eye" @click="s2 = !s2" tabindex="-1" aria-label="Afficher le nouveau mot de passe">
                  <EyeOff v-if="s2" :size="17" aria-hidden="true" />
                  <Eye v-else :size="17" aria-hidden="true" />
                </button>
              </template>
            </BaseInput>
            <div class="sbar"><div v-for="i in 4" :key="i" class="seg" :class="sc(i)"></div></div>
          </div>

          <BaseInput
            v-model="f.confirm"
            :type="s3 ? 'text' : 'password'"
            label="Confirmer le nouveau mot de passe"
            required
            placeholder="••••••••"
            auth
            :error="f.confirm && f.nouveau !== f.confirm ? 'Les mots de passe ne correspondent pas' : ''"
          >
            <template #suffix>
              <button type="button" class="eye" @click="s3 = !s3" tabindex="-1" aria-label="Afficher la confirmation">
                <EyeOff v-if="s3" :size="17" aria-hidden="true" />
                <Eye v-else :size="17" aria-hidden="true" />
              </button>
            </template>
          </BaseInput>

          <BaseButton type="submit" variant="primary" block :loading="loading" :disabled="!!(f.confirm && f.nouveau !== f.confirm)">
            Définir mon mot de passe →
          </BaseButton>
        </form>
      </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import api from '../services/api';
import { AlertTriangle, Eye, EyeOff } from "lucide-vue-next";
import AuthLayout from '../components/layout/AuthLayout.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseInput from '../components/ui/BaseInput.vue';

const router = useRouter();
const authStore = useAuthStore();
const f = ref({ ancien: '', nouveau: '', confirm: '' });
const message = ref(''); const ok = ref(true); const loading = ref(false);
const s1 = ref(false); const s2 = ref(false); const s3 = ref(false);

const str = computed(() => {
  const p = f.value.nouveau; let s = 0;
  if (p.length >= 8) s++; if (/[A-Z]/.test(p)) s++; if (/[0-9]/.test(p)) s++; if (/[\W_]/.test(p)) s++;
  return s;
});
const sc = (i) => { if (str.value < i) return 'seg-e'; return ['','seg-w','seg-f','seg-g','seg-s'][str.value] || 'seg-s'; };

const submit = async () => {
  if (f.value.nouveau !== f.value.confirm) { message.value = 'Les mots de passe ne correspondent pas.'; ok.value = false; return; }
  loading.value = true; message.value = '';
  try {
    await api.put('/users/change-password', { ancien_mot_de_passe: f.value.ancien, nouveau_mot_de_passe: f.value.nouveau });
    authStore.clearForcePasswordChange();
    ok.value = true; message.value = 'Mot de passe défini. Redirection...';
    
    const role = authStore.currentUser?.role;
    setTimeout(() => {
      if (['admin', 'chef_de_projet'].includes(role)) {
        router.push({ name: 'Dashboard' });
      } else {
        router.push({ name: 'Projects' });
      }
    }, 1500);
  } catch (e) { 
    ok.value = false; 
    message.value = e.response?.data?.message || 'Erreur.'; 
  } finally { 
    loading.value = false; 
  }
};
</script>

<style scoped>
.auth-card{border-radius:16px;box-shadow:0 25px 50px rgba(0,0,0,.4);}
.warn-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:.875rem 1rem;display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.5rem;}
.warn-icon{width:1.125rem;height:1.125rem;flex-shrink:0;margin-top:.125rem;color:#fbbf24;}
.warn-box p{font-size:.8125rem;color:#fcd34d;margin:0;line-height:1.6;}
.auth-form{gap:1.1rem;}
.auth-form :deep(.ds-field){gap:.35rem;}
.auth-form :deep(.ds-field__label){font-size:.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;}
.auth-form :deep(.ds-field__error){color:#f87171;}
.auth-alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:1.25rem;}
.auth-alert-success{background:rgba(34,197,94,.1);color:#86efac;border:1px solid rgba(34,197,94,.2);}
.auth-alert-error{background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2);}
.eye{background:none;border:none;cursor:pointer;color:#475569;padding:4px;display:flex;align-items:center;transition:color .15s;}
.eye:hover{color:#94a3b8;}
.sbar{display:flex;gap:4px;margin-top:6px;}
.seg{height:3px;flex:1;border-radius:2px;transition:background .3s;}
.seg-e{background:#1e293b;border:1px solid #334155;}
.seg-w{background:#ef4444;}.seg-f{background:#f59e0b;}.seg-g{background:#3b82f6;}.seg-s{background:#22c55e;}
</style>
