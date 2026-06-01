<template>
  <AppLayout>
      <PageHeader stacked>
        <template #title>
          <BarChart3 aria-hidden="true" />
          Tableau de Bord Analytique
        </template>
        <template #subtitle>
          <template v-if="isAdmin">Vue globale de la plateforme (Administrateur)</template>
          <template v-else>Vue opérationnelle des projets (Chef de Projet)</template>
        </template>
      </PageHeader>

      <!-- Time Filter & Project Selector -->
      <div class="time-filters">
        <div class="time-buttons">
          <button @click="setPeriod('today')" :class="['time-btn', period === 'today' ? 'active' : '']">Aujourd'hui</button>
          <button @click="setPeriod('week')" :class="['time-btn', period === 'week' ? 'active' : '']">Cette Semaine</button>
          <button @click="setPeriod('month')" :class="['time-btn', period === 'month' ? 'active' : '']">Ce Mois</button>
          <button @click="setPeriod('all')" :class="['time-btn', period === 'all' ? 'active' : '']">Global</button>
        </div>

        <div v-if="!isAdmin" class="project-selector">
          <select v-model="selectedProject" @change="fetchStats" class="select-input">
            <option value="">Tous mes projets</option>
            <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.nom }}</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Génération des statistiques en temps réel...</p>
      </div>
      
      <div v-else class="page-content fade-in">

        <!-- ADMIN VIEW -->
        <div v-if="isAdmin">
          <!-- KPIs -->
          <div class="kpi-grid">
            <div class="kpi-card">
              <div class="kpi-title">Total Projets</div>
              <div class="kpi-value">{{ stats.total_projects || 0 }}</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-title">Total Tickets</div>
              <div class="kpi-value">{{ totalAdminTickets }}</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-title">Chefs de projet actifs</div>
              <div class="kpi-value">{{ stats.kpi_chefs?.active }} / {{ stats.kpi_chefs?.total }}</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-title">Taux de résolution</div>
              <div class="kpi-value">{{ adminResolutionRate }}%</div>
            </div>
          </div>

          <div class="charts-grid">
            <!-- Tickets par statut (Donut) -->
            <div class="chart-card">
              <h3>Tickets par Statut</h3>
              <apexchart type="donut" height="300" :options="adminStatusOptions" :series="adminStatusSeries"></apexchart>
            </div>
            <!-- Membres par rôle (Camembert) -->
            <div class="chart-card">
              <h3>Membres par Rôle</h3>
              <apexchart type="pie" height="300" :options="adminRoleOptions" :series="adminRoleSeries"></apexchart>
            </div>
          </div>

          <!-- Activité globale des Projets (Courbe) -->
          <div class="chart-card full-width">
            <h3>Évolution de la plateforme (Projets Créés vs Archivés)</h3>
            <apexchart type="area" height="300" :options="adminProjectActivityOptions" :series="adminProjectActivitySeries"></apexchart>
          </div>

          <!-- Taux d'avancement par projet (Barres) -->
          <div class="chart-card full-width">
            <h3>Taux d'Avancement des Projets (%)</h3>
            <apexchart type="bar" height="300" :options="adminProjectAdvancementOptions" :series="adminProjectAdvancementSeries"></apexchart>
          </div>

          <!-- Activité globale des Tickets (Courbe) -->
          <div class="chart-card full-width">
            <h3>Activité Globale des Tickets (Créés vs Résolus)</h3>
            <apexchart type="area" height="300" :options="adminActivityOptions" :series="adminActivitySeries"></apexchart>
          </div>

          <!-- Tickets par projet (Barres) -->
          <div class="chart-card full-width">
            <h3>Tickets par Projet</h3>
            <apexchart type="bar" height="350" :options="adminProjectOptions" :series="adminProjectSeries"></apexchart>
          </div>
        </div>

        <!-- MANAGER VIEW -->
        <div v-else>
          <!-- KPIs -->
          <div class="kpi-grid">
            <div class="kpi-card">
              <div class="kpi-title">Tickets du Projet</div>
              <div class="kpi-value">{{ stats.avancement?.total || 0 }}</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-title">Délai Moyen Résolution</div>
              <div class="kpi-value">{{ stats.avg_resolution_hours || 0 }} h</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-title">Avancement</div>
              <div class="kpi-value">{{ managerResolutionRate }}%</div>
            </div>
          </div>

          <div class="charts-grid">
            <!-- Tickets par priorité (Barres) -->
            <div class="chart-card">
              <h3>Tickets par Priorité</h3>
              <apexchart type="bar" height="300" :options="managerPriorityOptions" :series="managerPrioritySeries"></apexchart>
            </div>
            <!-- Tickets par type (Camembert) -->
            <div class="chart-card">
              <h3>Catégorisation IA</h3>
              <apexchart type="pie" height="300" :options="managerTypeOptions" :series="managerTypeSeries"></apexchart>
            </div>
          </div>

          <!-- Évolution tickets (Courbe) -->
          <div class="chart-card full-width">
            <h3>Évolution des Tickets</h3>
            <apexchart type="area" height="350" :options="managerActivityOptions" :series="managerActivitySeries"></apexchart>
          </div>

          <div class="charts-grid">
            <!-- Charge par membre (Barres horiz) -->
            <div class="chart-card">
              <h3>Charge par Développeur</h3>
              <apexchart type="bar" height="350" :options="managerChargeOptions" :series="managerChargeSeries"></apexchart>
            </div>
            <!-- Heatmap d'activité -->
            <div class="chart-card">
              <h3>Heatmap d'Activité (Création)</h3>
              <apexchart type="heatmap" height="350" :options="managerHeatmapOptions" :series="managerHeatmapSeries"></apexchart>
            </div>
          </div>
        </div>

      </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { BarChart3 } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';

const authStore = useAuthStore();
const isAdmin = computed(() => authStore.currentUser?.role === 'admin');
const loading = ref(true);
const stats = ref({});
const period = ref('month');
const projects = ref([]);
const selectedProject = ref('');

const setPeriod = (p) => {
  period.value = p;
  fetchStats();
};

const fetchStats = async () => {
  loading.value = true;
  try {
    let endpoint = isAdmin.value ? '/stats/admin' : '/stats/manager';
    if (!isAdmin.value && selectedProject.value) {
      endpoint = `/stats/manager/${selectedProject.value}`;
    }
    const res = await api.get(`${endpoint}?period=${period.value}`);
    stats.value = res.data;
  } catch (e) {
    console.error("Erreur chargement stats", e);
  } finally {
    loading.value = false;
  }
};

const fetchProjects = async () => {
  try {
    const res = await api.get('/projects');
    projects.value = res.data.data || res.data; // Adapte selon le format de l'API
  } catch(e) {
    console.error("Erreur chargement projets", e);
  }
}

onMounted(() => {
  if (!isAdmin.value) fetchProjects();
  fetchStats();
});

// === ANIMATION & COLORS ===
const defaultAnim = { enabled: true, easing: 'easeinout', speed: 800 };
const colors = {
  blue: '#3b82f6', green: '#10b981', red: '#ef4444', amber: '#f59e0b', slate: '#64748b', purple: '#8b5cf6'
};

// ==============================================
// 1. ADMIN COMPUTED CHARTS
// ==============================================
const totalAdminTickets = computed(() => {
  if (!stats.value.tickets_by_status) return 0;
  return stats.value.tickets_by_status.reduce((sum, item) => sum + item.count, 0);
});
const adminResolutionRate = computed(() => {
  if (totalAdminTickets.value === 0) return 0;
  const resolved = stats.value.tickets_by_status.filter(i => ['VALIDE','RESOLU','FERME'].includes(i.etat))
                                                 .reduce((sum, item) => sum + item.count, 0);
  return Math.round((resolved / totalAdminTickets.value) * 100);
});

// Admin Status Donut
const adminStatusSeries = computed(() => stats.value.tickets_by_status?.map(i => i.count) || []);
const adminStatusOptions = computed(() => ({
  chart: { animations: defaultAnim },
  labels: stats.value.tickets_by_status?.map(i => i.etat) || [],
  colors: [colors.amber, colors.blue, colors.slate, colors.red, colors.green],
  plotOptions: { pie: { donut: { size: '65%' } } }
}));

// Admin Role Pie
const adminRoleSeries = computed(() => stats.value.members_by_role?.map(i => i.count) || []);
const adminRoleOptions = computed(() => ({
  chart: { animations: defaultAnim },
  labels: stats.value.members_by_role?.map(i => i.role) || [],
  colors: [colors.blue, colors.green]
}));

// Admin Project Activity Area
const adminProjectActivitySeries = computed(() => [
  { name: 'Projets Créés', data: stats.value.project_activity_created?.map(i => i.count) || [] },
  { name: 'Projets Archivés', data: stats.value.project_activity_archived?.map(i => i.count) || [] }
]);
const adminProjectActivityOptions = computed(() => {
  const categories = stats.value.project_activity_created?.map(i => i.date) || [];
  return {
    chart: { type: 'area', animations: defaultAnim, toolbar: { show: false } },
    colors: [colors.amber, colors.slate],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' },
    xaxis: { categories, type: 'datetime' },
  };
});

// Admin Project Advancement Bar
const adminProjectAdvancementSeries = computed(() => [{
  name: 'Avancement (%)',
  data: stats.value.projects_advancement?.map(i => i.rate) || []
}]);
const adminProjectAdvancementOptions = computed(() => {
  const categories = stats.value.projects_advancement?.map(i => i.nom) || [];
  return {
    chart: { type: 'bar', animations: defaultAnim, toolbar: { show: false } },
    colors: [colors.blue],
    xaxis: { categories },
    yaxis: { min: 0, max: 100 },
    plotOptions: { bar: { borderRadius: 4, dataLabels: { position: 'top' } } },
    dataLabels: {
      enabled: true,
      formatter: function (val) { return val + "%"; },
      offsetY: -20,
      style: { fontSize: '12px', colors: ["#304758"] }
    }
  };
});

// Admin Activity Area
const adminActivitySeries = computed(() => [
  { name: 'Créés', data: stats.value.activity_created?.map(i => i.count) || [] },
  { name: 'Résolus', data: stats.value.activity_resolved?.map(i => i.count) || [] }
]);
const adminActivityOptions = computed(() => {
  const categories = stats.value.activity_created?.map(i => i.date) || [];
  return {
    chart: { type: 'area', animations: defaultAnim, toolbar: { show: false } },
    colors: [colors.red, colors.green],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' },
    xaxis: { categories, type: 'datetime' },
  };
});

// Admin Projects Bar
const adminProjectSeries = computed(() => {
  if (!stats.value.tickets_by_project) return [];
  const projects = [...new Set(stats.value.tickets_by_project.map(i => i.project.nom))];
  const etats = [...new Set(stats.value.tickets_by_project.map(i => i.etat))];
  
  return etats.map(etat => {
    return {
      name: etat,
      data: projects.map(p => {
        const item = stats.value.tickets_by_project.find(x => x.project.nom === p && x.etat === etat);
        return item ? item.count : 0;
      })
    };
  });
});
const adminProjectOptions = computed(() => {
  const categories = [...new Set(stats.value.tickets_by_project?.map(i => i.project.nom) || [])];
  return {
    chart: { type: 'bar', stacked: true, animations: defaultAnim },
    xaxis: { categories },
    colors: [colors.amber, colors.blue, colors.slate, colors.red, colors.green]
  };
});


// ==============================================
// 2. MANAGER COMPUTED CHARTS
// ==============================================
const managerResolutionRate = computed(() => {
  const t = stats.value.avancement?.total || 0;
  if (t === 0) return 0;
  return Math.round((stats.value.avancement.resolus / t) * 100);
});

// Priority Bar
const managerPrioritySeries = computed(() => [{
  name: 'Tickets',
  data: stats.value.tickets_by_priority?.map(i => i.count) || []
}]);
const managerPriorityOptions = computed(() => ({
  chart: { type: 'bar', animations: defaultAnim, toolbar: { show: false } },
  xaxis: { categories: stats.value.tickets_by_priority?.map(i => i.priorite) || [] },
  plotOptions: { bar: { borderRadius: 4, distributed: true } },
  colors: [colors.slate, colors.blue, colors.amber, colors.red],
  legend: { show: false }
}));

// Category Pie
const managerTypeSeries = computed(() => stats.value.tickets_by_category?.map(i => i.count) || []);
const managerTypeOptions = computed(() => ({
  chart: { animations: defaultAnim },
  labels: stats.value.tickets_by_category?.map(i => i.categorie_ia) || [],
}));

// Manager Activity Area
const managerActivitySeries = computed(() => [
  { name: 'Créés', data: stats.value.activity_created?.map(i => i.count) || [] },
  { name: 'Résolus', data: stats.value.activity_resolved?.map(i => i.count) || [] }
]);
const managerActivityOptions = computed(() => {
  const categories = stats.value.activity_created?.map(i => i.date) || [];
  return {
    chart: { type: 'area', animations: defaultAnim, toolbar: { show: false } },
    colors: [colors.purple, colors.green],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth' },
    xaxis: { categories, type: 'datetime' },
  };
});

// Manager Charge (Horizontal Bar)
const managerChargeSeries = computed(() => {
  if (!stats.value.charge_by_member) return [];
  const devs = [...new Set(stats.value.charge_by_member.map(i => `${i.developpeur.prenom} ${i.developpeur.nom}`))];
  const etats = [...new Set(stats.value.charge_by_member.map(i => i.etat))];
  return etats.map(etat => {
    return {
      name: etat,
      data: devs.map(d => {
        const item = stats.value.charge_by_member.find(x => `${x.developpeur.prenom} ${x.developpeur.nom}` === d && x.etat === etat);
        return item ? item.count : 0;
      })
    };
  });
});
const managerChargeOptions = computed(() => {
  const categories = [...new Set(stats.value.charge_by_member?.map(i => `${i.developpeur.prenom} ${i.developpeur.nom}`) || [])];
  return {
    chart: { type: 'bar', stacked: true, animations: defaultAnim },
    plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
    xaxis: { categories },
    colors: [colors.amber, colors.blue, colors.green]
  };
});

// Heatmap
const managerHeatmapSeries = computed(() => {
  if (!stats.value.heatmap) return [];
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return days.map(day => {
    return {
      name: day.substring(0,3),
      data: Array.from({length: 24}, (_, i) => {
        const item = stats.value.heatmap.find(x => x.day === day && x.hour === i);
        return { x: i.toString(), y: item ? item.count : 0 };
      })
    };
  }).reverse();
});
const managerHeatmapOptions = computed(() => ({
  chart: { type: 'heatmap', animations: defaultAnim, toolbar: { show: false } },
  dataLabels: { enabled: false },
  colors: [colors.blue],
  title: { text: '' }
}));

</script>

<style scoped>
.time-filters {
  padding: 1rem 2.5rem;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.time-buttons {
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

.select-input {
  padding: 0.5rem 1rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  color: #1e293b;
  font-size: 0.875rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}

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
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; }
.kpi-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.kpi-title { font-size: 0.875rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.kpi-value { font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-top: 0.5rem; }

/* Charts */
.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.chart-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.chart-card.full-width { grid-column: span 2; }
.chart-card h3 { font-size: 1rem; font-weight: 800; color: #1e293b; margin-top: 0; margin-bottom: 1.5rem; }
</style>