<template>
  <div class="layout">
    <AppSidebar />
    <main class="main">
      <div class="page-header">
        <h1 class="page-title">📊 Tableau de Bord</h1>
        <p class="page-subtitle">Aperçu global des performances de l'équipe</p>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500">Chargement des données...</div>
      
      <div v-else class="page-content">
        <!-- Global Filters -->
        <div class="filters-card">
          <div class="filter-pills">
            <span class="filter-label">Filtrer par Rôle :</span>
            <button @click="toggleRole('developpeur')" :class="['pill', filterRole.includes('developpeur') ? 'pill-active' : '']">👨‍💻 Développeurs</button>
            <button @click="toggleRole('testeur')" :class="['pill', filterRole.includes('testeur') ? 'pill-active' : '']">🕵️ Testeurs</button>
            <button @click="toggleRole('chef_de_projet')" :class="['pill', filterRole.includes('chef_de_projet') ? 'pill-active' : '']">👑 Chefs de projet</button>
          </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Membre</th>
                <th>Rôle</th>
                <th>Projets</th>
                <th>Tickets Actifs</th>
                <th>Tickets Résolus</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredStats" :key="user.id">
                <td>
                  <div class="user-cell">
                    <div class="avatar">{{ ini(user) }}</div>
                    <div>
                      <div class="u-name">{{ user.prenom }} {{ user.nom }}</div>
                      <div class="u-email">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td><span class="role-badge" :class="'role-' + user.role">{{ user.role }}</span></td>
                <td><span class="num-badge">{{ user.projects_count }}</span></td>
                <td>
                  <span class="charge-badge charge-normal">
                    {{ user.active_tickets_count }}
                  </span>
                </td>
                <td><span class="num-badge success">{{ user.closed_tickets_count }}</span></td>
                <td>
                  <button @click="selectedUser = user" class="btn-details">Détails</button>
                </td>
              </tr>
              <tr v-if="filteredStats.length === 0">
                <td colspan="6" class="text-center py-4 text-gray-500">Aucun membre trouvé.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detail Modal -->
      <div v-if="selectedUser" class="modal-overlay" @click.self="selectedUser = null">
        <div class="modal">
          <div class="modal-header">
            <h3>Détails : {{ selectedUser.prenom }} {{ selectedUser.nom }}</h3>
            <button @click="selectedUser = null" class="close-btn">&times;</button>
          </div>
          <div class="modal-body">
            <div class="stat-grid">
              <div class="stat-box">
                <div class="stat-val">{{ selectedUser.projects_count }}</div>
                <div class="stat-lbl">Projets</div>
              </div>
              <div class="stat-box">
                <div class="stat-val">{{ selectedUser.active_tickets_count }}</div>
                <div class="stat-lbl">Tickets Actifs</div>
              </div>
              <div class="stat-box">
                <div class="stat-val">{{ selectedUser.closed_tickets_count }}</div>
                <div class="stat-lbl">Tickets Résolus</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import api from '../../services/api';
import AppSidebar from '../../components/AppSidebar.vue';

const router = useRouter();
const authStore = useAuthStore();
const stats = ref([]);
const loading = ref(true);
const filterRole = ref([]);
const selectedUser = ref(null);

const fetchStats = async () => {
  try {
    const res = await api.get('/dashboard/stats');
    stats.value = res.data;
  } catch (e) {
    if (e.response?.status === 403) {
      router.push({ name: 'Projects' });
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchStats();
});



const toggleRole = (role) => {
  if (filterRole.value.includes(role)) {
    filterRole.value = filterRole.value.filter(r => r !== role);
  } else {
    filterRole.value.push(role);
  }
};

const filteredStats = computed(() => {
  if (filterRole.value.length === 0) return stats.value;
  return stats.value.filter(u => filterRole.value.includes(u.role));
});

const ini = u => (u.prenom?.[0] || '') + (u.nom?.[0] || '');
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;box-sizing:border-box;}

.layout{display:flex;min-height:100vh;background:#f8fafc;}
.main{flex:1;overflow-y:auto;display:flex;flex-direction:column;}

.page-header{padding:2rem 2.5rem;background:white;border-bottom:1px solid #e2e8f0;}
.page-title{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;}
.page-subtitle{font-size:.875rem;color:#64748b;margin-top:4px;}

.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;}

.filters-card{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.5rem;display:flex;align-items:center;}
.filter-pills{display:flex;align-items:center;gap:.75rem;}
.filter-label{font-size:.75rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;}
.pill{padding:.4rem 1rem;border-radius:99px;border:1px solid #e2e8f0;background:white;font-size:.8125rem;font-weight:700;color:#64748b;cursor:pointer;transition:all .2s;}
.pill:hover{border-color:#cbd5e1;background:#f8fafc;}
.pill-active{background:#3b82f6;color:white;border-color:#3b82f6;}

.table-container{background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.data-table{width:100%;border-collapse:collapse;text-align:left;}
.data-table th{padding:1rem 1.5rem;font-size:.75rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #e2e8f0;background:#f8fafc;}
.data-table td{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:#f8fafc;}

.user-cell{display:flex;align-items:center;gap:.75rem;}
.avatar{width:36px;height:36px;border-radius:10px;background:#e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;}
.u-name{font-size:.875rem;font-weight:700;color:#1e293b;}
.u-email{font-size:.75rem;color:#64748b;}

.role-badge{font-size:.6875rem;font-weight:700;padding:3px 10px;border-radius:99px;text-transform:uppercase;}
.role-testeur{background:#dcfce7;color:#16a34a;}
.role-developpeur{background:#dbeafe;color:#1d4ed8;}
.role-admin,.role-chef_de_projet{background:#f3e8ff;color:#7c3aed;}

.num-badge{background:#f1f5f9;color:#475569;padding:3px 10px;border-radius:8px;font-size:.75rem;font-weight:800;}
.num-badge.success{background:#dcfce7;color:#16a34a;}

.charge-badge{font-size:.75rem;font-weight:800;padding:3px 10px;border-radius:8px;}
.charge-normal{background:#f1f5f9;color:#475569;}
.charge-high{background:#fee2e2;color:#dc2626;}

.btn-details{padding:.4rem .75rem;font-size:.75rem;font-weight:700;color:#3b82f6;background:#eff6ff;border:none;border-radius:6px;cursor:pointer;transition:background .2s;}
.btn-details:hover{background:#dbeafe;}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;}
.modal{background:white;border-radius:16px;width:100%;max-width:500px;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,.15);}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;background:#f8fafc;}
.modal-header h3{margin:0;font-size:1.125rem;font-weight:800;color:#0f172a;}
.close-btn{background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;line-height:1;}
.close-btn:hover{color:#475569;}
.modal-body{padding:1.5rem;}
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;}
.stat-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.5rem;text-align:center;}
.stat-val{font-size:2rem;font-weight:800;color:#2563eb;}
.stat-lbl{font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-top:.5rem;}
.mt-4{margin-top:1rem;}
.text-sm{font-size:0.875rem;}
.text-gray-600{color:#4b5563;}
.text-center{text-align:center;}
.py-4{padding-top:1rem;padding-bottom:1rem;}
</style>