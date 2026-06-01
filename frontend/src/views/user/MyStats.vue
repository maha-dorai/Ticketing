<template>
  <AppLayout>
      <div class="page-header">
        <h1 class="page-title">
          <User class="page-title-icon" aria-hidden="true" />
          Mes Statistiques Personnelles
        </h1>
        <p class="page-subtitle">Aperçu de votre activité et de vos performances</p>
      </div>

      <!-- Time Filter -->
      <div class="time-filters">
        <button @click="setPeriod('today')" :class="['time-btn', period === 'today' ? 'active' : '']">Aujourd'hui</button>
        <button @click="setPeriod('week')" :class="['time-btn', period === 'week' ? 'active' : '']">Cette Semaine</button>
        <button @click="setPeriod('month')" :class="['time-btn', period === 'month' ? 'active' : '']">Ce Mois</button>
        <button @click="setPeriod('all')" :class="['time-btn', period === 'all' ? 'active' : '']">Global</button>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Chargement de vos statistiques...</p>
      </div>
      
      <div v-else class="page-content fade-in">

        <!-- KPIs -->
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-title">Total de mes tickets</div>
            <div class="kpi-value">{{ stats.my_kpi?.total || 0 }}</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-title">Tickets Actifs (Ouverts/En cours)</div>
            <div class="kpi-value">{{ activeTicketsCount }}</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-title">Tickets Résolus</div>
            <div class="kpi-value" style="color: #10b981;">{{ resolvedTicketsCount }}</div>
          </div>
        </div>

        <div class="charts-grid">
          <!-- Mon activité (Courbe) -->
          <div class="chart-card full-width">
            <h3>Mon Activité d'Assignation</h3>
            <apexchart type="area" height="350" :options="activityOptions" :series="activitySeries"></apexchart>
          </div>

          <!-- Répartition de ma charge (Donut) -->
          <div class="chart-card">
            <h3>Répartition de ma charge par Projet</h3>
            <apexchart type="donut" height="300" :options="projectOptions" :series="projectSeries"></apexchart>
          </div>

          <!-- Mes tickets par statut (Barres) -->
          <div class="chart-card">
            <h3>Mes tickets par statut</h3>
            <apexchart type="bar" height="300" :options="statusOptions" :series="statusSeries"></apexchart>
          </div>
        </div>

      </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { User } from 'lucide-vue-next';
import api from '../../services/api';
import AppLayout from '../../components/layout/AppLayout.vue';

const loading = ref(true);
const stats = ref({});
const period = ref('month');

const setPeriod = (p) => {
  period.value = p;
  fetchStats();
};

const fetchStats = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/stats/me?period=${period.value}`);
    stats.value = res.data;
  } catch (e) {
    console.error("Erreur chargement stats", e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchStats();
});

// === ANIMATION & COLORS ===
const defaultAnim = { enabled: true, easing: 'easeinout', speed: 800 };
const colors = {
  blue: '#3b82f6', green: '#10b981', red: '#ef4444', amber: '#f59e0b', slate: '#64748b', purple: '#8b5cf6'
};

// === COMPUTED KPI ===
const activeTicketsCount = computed(() => {
  if (!stats.value.my_kpi?.by_status) return 0;
  return stats.value.my_kpi.by_status
    .filter(i => ['OUVERT', 'EN_COURS', 'A_TESTER', 'RECLAMATION'].includes(i.etat))
    .reduce((sum, item) => sum + item.count, 0);
});

const resolvedTicketsCount = computed(() => {
  if (!stats.value.my_kpi?.by_status) return 0;
  return stats.value.my_kpi.by_status
    .filter(i => ['VALIDE', 'RESOLU', 'FERME'].includes(i.etat))
    .reduce((sum, item) => sum + item.count, 0);
});

// === COMPUTED CHARTS ===

// Mon activité (Courbe Area)
const activitySeries = computed(() => [
  { name: 'Nouveaux tickets assignés/créés', data: stats.value.my_activity?.map(i => i.count) || [] }
]);
const activityOptions = computed(() => {
  const categories = stats.value.my_activity?.map(i => i.date) || [];
  return {
    chart: { type: 'area', animations: defaultAnim, toolbar: { show: false } },
    colors: [colors.blue],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' },
    xaxis: { categories, type: 'datetime' },
  };
});

// Répartition par Projet (Donut)
const projectSeries = computed(() => stats.value.my_tickets_by_project?.map(i => i.count) || []);
const projectOptions = computed(() => ({
  chart: { animations: defaultAnim },
  labels: stats.value.my_tickets_by_project?.map(i => i.project.nom) || [],
  plotOptions: { pie: { donut: { size: '65%' } } }
}));

// Mes tickets par statut (Barres)
const statusSeries = computed(() => [{
  name: 'Tickets',
  data: stats.value.my_kpi?.by_status?.map(i => i.count) || []
}]);
const statusOptions = computed(() => ({
  chart: { type: 'bar', animations: defaultAnim, toolbar: { show: false } },
  xaxis: { categories: stats.value.my_kpi?.by_status?.map(i => i.etat) || [] },
  plotOptions: { bar: { borderRadius: 4, distributed: true } },
  legend: { show: false }
}));

</script>

<style scoped>
.page-header{padding:2rem 2.5rem 1rem;background:white;border-bottom:1px solid #e2e8f0;}
.page-title{display:flex;align-items:center;gap:.625rem;font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;}
.page-title-icon{width:1.375rem;height:1.375rem;color:var(--color-brand);flex-shrink:0;}
.page-subtitle{font-size:.875rem;color:#64748b;margin-top:4px;}

.time-filters {
  padding: 1rem 2.5rem;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  gap: 0.5rem;
}
.time-btn {
  padding: 0.5rem 1rem;
  border-radius: 99px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.time-btn:hover { background: #e2e8f0; }
.time-btn.active { background: #0f172a; color: white; border-color: #0f172a; }

.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;}

.loading-state { padding: 4rem; text-align: center; color: #64748b; font-weight: 600; }
.spinner {
  width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top-color: #3b82f6;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }

.fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* KPIs */
.kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.kpi-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.kpi-title { font-size: 0.875rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.kpi-value { font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-top: 0.5rem; }

/* Charts */
.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.chart-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.chart-card.full-width { grid-column: span 2; }
.chart-card h3 { font-size: 1rem; font-weight: 800; color: #1e293b; margin-top: 0; margin-bottom: 1.5rem; }
</style>
