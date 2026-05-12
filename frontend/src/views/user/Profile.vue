<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <div class="page-header">
        <div class="header-user">
          <div class="big-av">{{ initials }}</div>
          <div>
            <h1 class="page-title">{{ user?.prenom }} {{ user?.nom }}</h1>
            <div class="user-meta">
              <span class="role-chip">{{ roleLabel }}</span>
              <span class="sep">·</span>
              <span class="email-text">{{ user?.email }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="page-content">

        <!-- MOT DE PASSE -->
        <div class="card">
          <h2 class="card-title">🔒 Changer le mot de passe</h2>
          <div v-if="pwMsg" class="alert" :class="pwOk ? 'alert-ok' : 'alert-err'">{{ pwOk ? '✓' : '✕' }} {{ pwMsg }}</div>
          <form @submit.prevent="changePw" class="form">
            <div class="field">
              <label class="label">Mot de passe actuel</label>
              <div class="iw">
                <input v-model="pw.ancien" :type="s1?'text':'password'" required placeholder="••••••••" class="input ipr" />
                <button type="button" class="eye" @click="s1=!s1" tabindex="-1"><Eye :o="s1"/></button>
              </div>
            </div>
            <div class="field">
              <label class="label">Nouveau mot de passe</label>
              <div class="iw">
                <input v-model="pw.nouveau" :type="s2?'text':'password'" required placeholder="Min 8 car., MAJ, chiffre, symbole" class="input ipr" />
                <button type="button" class="eye" @click="s2=!s2" tabindex="-1"><Eye :o="s2"/></button>
              </div>
              <div class="sbar"><div v-for="i in 4" :key="i" class="seg" :class="sc(i)"></div></div>
            </div>
            <div class="field">
              <label class="label">Confirmer le nouveau mot de passe</label>
              <div class="iw">
                <input v-model="pw.confirm" :type="s3?'text':'password'" required placeholder="••••••••" class="input ipr" :class="{mismatch:pw.confirm&&pw.nouveau!==pw.confirm}"/>
                <button type="button" class="eye" @click="s3=!s3" tabindex="-1"><Eye :o="s3"/></button>
              </div>
              <p v-if="pw.confirm&&pw.nouveau!==pw.confirm" class="mismatch-text">Les mots de passe ne correspondent pas</p>
            </div>
            <button type="submit" :disabled="pwL||(pw.confirm&&pw.nouveau!==pw.confirm)" class="btn-primary">
              <Spin v-if="pwL"/><span v-else>Mettre à jour le mot de passe</span>
            </button>
          </form>
        </div>

        <!-- EMAIL -->
        <div class="card">
          <h2 class="card-title">✉ Changer l'adresse email</h2>
          <div v-if="emMsg" class="alert" :class="emOk ? 'alert-ok' : 'alert-err'">{{ emOk ? '✓' : '✕' }} {{ emMsg }}</div>
          <form @submit.prevent="changeEm" class="form">
            <div class="field">
              <label class="label">Nouvel email</label>
              <input v-model="em.email" type="email" required placeholder="nouveau@exemple.com" class="input"/>
            </div>
            <div class="field">
              <label class="label">Mot de passe actuel (confirmation)</label>
              <div class="iw">
                <input v-model="em.mdp" :type="s4?'text':'password'" required placeholder="••••••••" class="input ipr"/>
                <button type="button" class="eye" @click="s4=!s4" tabindex="-1"><Eye :o="s4"/></button>
              </div>
            </div>
            <button type="submit" :disabled="emL" class="btn-primary">
              <Spin v-if="emL"/><span v-else>Mettre à jour l'email</span>
            </button>
          </form>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, defineComponent, h } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const authStore = useAuthStore();
const user = computed(() => authStore.currentUser);
const initials = computed(() => ((user.value?.prenom||'')[0]+(user.value?.nom||'')[0]).toUpperCase());
const roleLabel = computed(() => ({super_admin:'Super Admin',admin:'Administrateur',developpeur:'Développeur',testeur:'Testeur'}[user.value?.role]||''));

const Eye = defineComponent({ props:['o'], setup:(p)=>()=>(p.o
  ?h('svg',{xmlns:'http://www.w3.org/2000/svg',width:17,height:17,fill:'none',viewBox:'0 0 24 24','stroke-width':'1.8',stroke:'currentColor'},[h('path',{'stroke-linecap':'round','stroke-linejoin':'round',d:'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'}),h('path',{'stroke-linecap':'round','stroke-linejoin':'round',d:'M15 12a3 3 0 11-6 0 3 3 0 016 0z'})])
  :h('svg',{xmlns:'http://www.w3.org/2000/svg',width:17,height:17,fill:'none',viewBox:'0 0 24 24','stroke-width':'1.8',stroke:'currentColor'},[h('path',{'stroke-linecap':'round','stroke-linejoin':'round',d:'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88'})]) 
) });

const Spin = defineComponent({setup:()=>()=>h('svg',{class:'spin',xmlns:'http://www.w3.org/2000/svg',fill:'none',viewBox:'0 0 24 24',width:16,height:16},[h('circle',{cx:12,cy:12,r:10,stroke:'currentColor','stroke-width':4,style:'opacity:.25'}),h('path',{fill:'currentColor',d:'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z',style:'opacity:.75'})])});

const pw=ref({ancien:'',nouveau:'',confirm:''}),pwMsg=ref(''),pwOk=ref(true),pwL=ref(false);
const s1=ref(false),s2=ref(false),s3=ref(false),s4=ref(false);
const str=computed(()=>{const p=pw.value.nouveau;let s=0;if(p.length>=8)s++;if(/[A-Z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[\W_]/.test(p))s++;return s;});
const sc=(i)=>{if(str.value<i)return'seg-e';return['','seg-w','seg-f','seg-g','seg-s'][str.value]||'seg-s';};

const changePw=async()=>{
  if(pw.value.nouveau!==pw.value.confirm){pwMsg.value='Les mots de passe ne correspondent pas.';pwOk.value=false;return;}
  pwL.value=true;pwMsg.value='';
  try{await api.put('/users/change-password',{ancien_mot_de_passe:pw.value.ancien,nouveau_mot_de_passe:pw.value.nouveau});authStore.clearForcePasswordChange();pwOk.value=true;pwMsg.value='Mot de passe modifié ✓';pw.value={ancien:'',nouveau:'',confirm:''};}
  catch(e){pwOk.value=false;pwMsg.value=e.response?.data?.message||'Erreur.';}
  finally{pwL.value=false;}
};

const em=ref({email:'',mdp:''}),emMsg=ref(''),emOk=ref(true),emL=ref(false);
const changeEm=async()=>{
  emL.value=true;emMsg.value='';
  try{await api.put('/users/change-email',{new_email:em.value.email,mot_de_passe:em.value.mdp});if(authStore.currentUser){authStore.currentUser.email=em.value.email;localStorage.setItem('user',JSON.stringify(authStore.currentUser));}emOk.value=true;emMsg.value='Email modifié ✓';em.value={email:'',mdp:''};}
  catch(e){emOk.value=false;emMsg.value=e.response?.data?.message||'Erreur.';}
  finally{emL.value=false;}
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}
.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;}
.page-header{padding:2rem 2.5rem;border-bottom:1px solid #e2e8f0;background:white;}
.header-user{display:flex;align-items:center;gap:1.25rem;}
.big-av{width:56px;height:56px;border-radius:14px;background:#dbeafe;color:#1d4ed8;font-size:1.25rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-transform:uppercase;}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;letter-spacing:-.02em;}
.user-meta{display:flex;align-items:center;gap:.5rem;margin-top:.25rem;}
.role-chip{font-size:.6875rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:3px 9px;border-radius:20px;}
.sep{color:#cbd5e1;}
.email-text{font-size:.875rem;color:#64748b;}
.page-content{max-width:560px;padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;}
.card{background:white;border:1px solid #e2e8f0;border-radius:14px;padding:1.75rem;}
.card-title{font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 1.25rem;}
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:1rem;}
.alert-ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.alert-err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.form{display:flex;flex-direction:column;gap:1rem;}
.field{display:flex;flex-direction:column;gap:.35rem;}
.label{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;}
.iw{position:relative;}
.input{width:100%;padding:.625rem .875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;font-size:.9rem;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.input::placeholder{color:#cbd5e1;}
.input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);background:white;}
.ipr{padding-right:2.5rem;}
.mismatch{border-color:#ef4444!important;}
.mismatch-text{font-size:.75rem;color:#dc2626;}
.eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;align-items:center;transition:color .15s;}
.eye:hover{color:#475569;}
.sbar{display:flex;gap:4px;margin-top:6px;}
.seg{height:3px;flex:1;border-radius:2px;transition:background .3s;}
.seg-e{background:#f1f5f9;border:1px solid #e2e8f0;}
.seg-w{background:#ef4444;}.seg-f{background:#f59e0b;}.seg-g{background:#3b82f6;}.seg-s{background:#22c55e;}
.btn-primary{padding:.75rem;background:#1e293b;color:white;border:none;border-radius:8px;font-size:.875rem;font-weight:700;font-family:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:background .15s;}
.btn-primary:hover:not(:disabled){background:#0f172a;}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.spin{animation:spin .8s linear infinite;}@keyframes spin{to{transform:rotate(360deg);}}
</style>