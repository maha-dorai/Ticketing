<template>
  <AuthLayout wide>
      <AuthBrand title="Sécurité requise" />
      <div class="card">
        <div class="warn-box">
          <AlertTriangle class="warn-icon" aria-hidden="true" />
          <p>Votre compte a été créé par un administrateur. Vous devez définir votre propre mot de passe avant de continuer.</p>
        </div>

        <AlertBanner v-if="message" :variant="ok ? 'success' : 'error'" class="alert" :class="ok ? 'alert-ok' : 'alert-err'">{{ message }}</AlertBanner>

        <form @submit.prevent="submit" class="form">
          <div class="field">
            <label class="label">Mot de passe temporaire (reçu par email)</label>
            <div class="iw"><input v-model="f.ancien" :type="s1?'text':'password'" required placeholder="••••••••" class="input ipr"/><button type="button" class="eye" @click="s1=!s1" tabindex="-1" aria-label="Afficher le mot de passe"><Eye v-if="s1" :size="17" aria-hidden="true" /><EyeOff v-else :size="17" aria-hidden="true" /></button></div>
          </div>
          <div class="field">
            <label class="label">Nouveau mot de passe</label>
            <div class="iw"><input v-model="f.nouveau" :type="s2?'text':'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="input ipr"/><button type="button" class="eye" @click="s2=!s2" tabindex="-1" aria-label="Afficher le mot de passe"><Eye v-if="s2" :size="17" aria-hidden="true" /><EyeOff v-else :size="17" aria-hidden="true" /></button></div>
            <div class="sbar"><div v-for="i in 4" :key="i" class="seg" :class="sc(i)"></div></div>
          </div>
          <div class="field">
            <label class="label">Confirmer le nouveau mot de passe</label>
            <div class="iw"><input v-model="f.confirm" :type="s3?'text':'password'" required placeholder="••••••••" class="input ipr" :class="{mm:f.confirm&&f.nouveau!==f.confirm}"/><button type="button" class="eye" @click="s3=!s3" tabindex="-1" aria-label="Afficher le mot de passe"><Eye v-if="s3" :size="17" aria-hidden="true" /><EyeOff v-else :size="17" aria-hidden="true" /></button></div>
            <p v-if="f.confirm&&f.nouveau!==f.confirm" class="mm-txt">Les mots de passe ne correspondent pas</p>
          </div>
          <BaseButton type="submit" :disabled="loading||(f.confirm&&f.nouveau!==f.confirm)" variant="primary" size="sm" :loading="loading">
            <span>Définir mon mot de passe →</span>
          </BaseButton>
        </form>
      </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import { AlertTriangle, Eye, EyeOff, Loader2 } from 'lucide-vue-next';
import api from '../services/api';
import AuthLayout from '../components/layout/AuthLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import AuthBrand from '../components/auth/AuthBrand.vue';
import AlertBanner from '../components/ui/AlertBanner.vue';

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
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:2rem;box-shadow:0 25px 50px rgba(0,0,0,.4);}
.warn-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:.875rem 1rem;display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.5rem;}
.warn-icon{width:1.125rem;height:1.125rem;flex-shrink:0;margin-top:.125rem;color:#fbbf24;}
.warn-box p{font-size:.8125rem;color:#fcd34d;margin:0;line-height:1.6;}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:1.25rem;}
.alert-ok{background:rgba(34,197,94,.1);color:#86efac;border:1px solid rgba(34,197,94,.2);}
.alert-err{background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2);}
.form{display:flex;flex-direction:column;gap:1.1rem;}
.field{display:flex;flex-direction:column;gap:.35rem;}
.label{font-size:.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;}
.iw{position:relative;}
.input{width:100%;padding:.625rem .875rem;background:#0f172a;border:1px solid #334155;border-radius:8px;color:#f1f5f9;font-size:.9375rem;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.input::placeholder{color:#475569;}
.input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
.ipr{padding-right:2.75rem;}
.mm{border-color:#ef4444!important;}
.mm-txt{font-size:.75rem;color:#f87171;}
.eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#475569;padding:4px;display:flex;align-items:center;transition:color .15s;}
.eye:hover{color:#94a3b8;}
.sbar{display:flex;gap:4px;margin-top:6px;}
.seg{height:3px;flex:1;border-radius:2px;transition:background .3s;}
.seg-e{background:#1e293b;border:1px solid #334155;}
.seg-w{background:#ef4444;}.seg-f{background:#f59e0b;}.seg-g{background:#3b82f6;}.seg-s{background:#22c55e;}
</style>