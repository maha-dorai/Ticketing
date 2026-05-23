<template>
  <div class="page">
    <div class="container">
      <div class="brand"><span class="brand-icon">🔐</span><h1 class="brand-name">Sécurité requise</h1></div>
      <div class="card">
        <div class="warn-box">
          <span class="warn-icon">⚠</span>
          <p>Votre compte a été créé par un administrateur. Vous devez définir votre propre mot de passe avant de continuer.</p>
        </div>

        <div v-if="message" class="alert" :class="ok ? 'alert-ok' : 'alert-err'">{{ ok ? '✓' : '✕' }} {{ message }}</div>

        <form @submit.prevent="submit" class="form">
          <div class="field">
            <label class="label">Mot de passe temporaire (reçu par email)</label>
            <div class="iw"><input v-model="f.ancien" :type="s1?'text':'password'" required placeholder="••••••••" class="input ipr"/><button type="button" class="eye" @click="s1=!s1" tabindex="-1"><Eye :o="s1"/></button></div>
          </div>
          <div class="field">
            <label class="label">Nouveau mot de passe</label>
            <div class="iw"><input v-model="f.nouveau" :type="s2?'text':'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="input ipr"/><button type="button" class="eye" @click="s2=!s2" tabindex="-1"><Eye :o="s2"/></button></div>
            <div class="sbar"><div v-for="i in 4" :key="i" class="seg" :class="sc(i)"></div></div>
          </div>
          <div class="field">
            <label class="label">Confirmer le nouveau mot de passe</label>
            <div class="iw"><input v-model="f.confirm" :type="s3?'text':'password'" required placeholder="••••••••" class="input ipr" :class="{mm:f.confirm&&f.nouveau!==f.confirm}"/><button type="button" class="eye" @click="s3=!s3" tabindex="-1"><Eye :o="s3"/></button></div>
            <p v-if="f.confirm&&f.nouveau!==f.confirm" class="mm-txt">Les mots de passe ne correspondent pas</p>
          </div>
          <button type="submit" :disabled="loading||(f.confirm&&f.nouveau!==f.confirm)" class="btn-primary">
            <svg v-if="loading" class="spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" style="opacity:.75"/></svg>
            <span v-else>Définir mon mot de passe →</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import api from '../services/api';

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

const Eye = defineComponent({ props: ['o'], setup: (p) => () => p.o
  ? h('svg', { xmlns:'http://www.w3.org/2000/svg', width:17, height:17, fill:'none', viewBox:'0 0 24 24', 'stroke-width':'1.8', stroke:'currentColor' }, [
      h('path', { 'stroke-linecap':'round','stroke-linejoin':'round', d:'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z' }),
      h('path', { 'stroke-linecap':'round','stroke-linejoin':'round', d:'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })])
  : h('svg', { xmlns:'http://www.w3.org/2000/svg', width:17, height:17, fill:'none', viewBox:'0 0 24 24', 'stroke-width':'1.8', stroke:'currentColor' }, [
      h('path', { 'stroke-linecap':'round','stroke-linejoin':'round', d:'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88' })])
});

const submit = async () => {
  if (f.value.nouveau !== f.value.confirm) { message.value = 'Les mots de passe ne correspondent pas.'; ok.value = false; return; }
  loading.value = true; message.value = '';
  try {
    await api.put('/users/change-password', { ancien_mot_de_passe: f.value.ancien, nouveau_mot_de_passe: f.value.nouveau });
    authStore.clearForcePasswordChange();
    ok.value = true; message.value = 'Mot de passe défini. Redirection...';
// ✅ Corriger
const role = authStore.currentUser?.role;
setTimeout(() => {
  if (role === 'admin') router.push({ name: 'UserManagement' });
  else router.push({ name: 'AdminDashboard' });
}, 1500);  } catch (e) { ok.value = false; message.value = e.response?.data?.message || 'Erreur.'; }
  finally { loading.value = false; }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.page{min-height:100vh;background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%);display:flex;align-items:center;justify-content:center;padding:2rem 1rem;}
.container{width:100%;max-width:440px;}
.brand{text-align:center;margin-bottom:2rem;}
.brand-icon{font-size:2.5rem;display:block;margin-bottom:.5rem;}
.brand-name{font-size:1.375rem;font-weight:800;color:#f8fafc;margin:0;letter-spacing:-.02em;}
.card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:2rem;box-shadow:0 25px 50px rgba(0,0,0,.4);}
.warn-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:.875rem 1rem;display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.5rem;}
.warn-icon{font-size:1.125rem;flex-shrink:0;margin-top:.125rem;}
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
.btn-primary{padding:.75rem;background:#3b82f6;color:white;border:none;border-radius:8px;font-size:.9375rem;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .2s;margin-top:.25rem;}
.btn-primary:hover:not(:disabled){background:#2563eb;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
</style>