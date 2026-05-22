<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <div class="page-header">
        <div class="header-left">
          <h1 class="page-title">📈 Mes Statistiques</h1>
          <p class="page-subtitle">Aperçu de votre activité sur la plateforme</p>
        </div>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500">Chargement de vos statistiques...</div>
      
      <div v-else-if="stats" class="page-content">
        <!-- Overview Grid -->
        <div class="stat-grid">
          <div class="stat-card projects">
            <div class="stat-icon">📁</div>
            <div class="stat-info">
              <div class="stat-val">{{ stats.projects_count }}</div>
              <div class="stat-lbl">Projets Actifs</div>
            </div>
          </div>
          
          <div class="stat-card active-tickets">
            <div class="stat-icon">{{ isTesteur ? '🎫' : '👨‍💻' }}</div>
            <div class="stat-info">
              <div class="stat-val">{{ stats.active_tickets_count }}</div>
              <div class="stat-lbl">{{ isTesteur ? 'Tickets Signalés (Ouverts)' : 'Tickets en charge' }}</div>
            </div>
          </div>
          
          <div class="stat-card resolved-tickets">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
              <div class="stat-val">{{ stats.closed_tickets_count }}</div>
              <div class="stat-lbl">{{ isTesteur ? 'Tickets Résolus' : 'Tickets Clôturés' }}</div>
            </div>
          </div>
        </div>

        <div class="info-banner">
          <div class="banner-icon">💡</div>
          <div class="banner-text">
            Ces statistiques sont mises à jour en temps réel. Des graphiques avancés seront disponibles lors d'une prochaine mise à jour.
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const authStore = useAuthStore();
const stats = ref(null);
const loading = ref(true);

const isTesteur = computed(() => authStore.currentUser?.role === 'testeur');

const fetchStats = async () => {
  try {
    const res = await api.get('/user/stats');
    stats.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchStats();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}

.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;display:flex;flex-direction:column;}

.page-header{padding:2rem 2.5rem;background:white;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;}
.page-subtitle{font-size:.875rem;color:#64748b;margin-top:4px;}

.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:2rem;}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;}
.stat-card{background:white;border:1px solid #e2e8f0;border-radius:16px;padding:1.5rem;display:flex;align-items:center;gap:1.25rem;transition:transform .2s,box-shadow .2s;}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(0,0,0,.05);}

.stat-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;}
.projects .stat-icon{background:#eff6ff;color:#3b82f6;}
.active-tickets .stat-icon{background:#fef2f2;color:#ef4444;}
.resolved-tickets .stat-icon{background:#ecfdf5;color:#10b981;}

.stat-info{display:flex;flex-direction:column;gap:4px;}
.stat-val{font-size:2rem;font-weight:800;color:#0f172a;line-height:1;}
.stat-lbl{font-size:.8125rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;}

.info-banner{background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:1.25rem 1.5rem;display:flex;align-items:flex-start;gap:1rem;}
.banner-icon{font-size:1.25rem;}
.banner-text{font-size:.875rem;color:#1e3a8a;line-height:1.5;}
</style>
