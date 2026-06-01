<template>
  <AppLayout>
      <PageHeader>
        <template #title>Gestion des membres</template>
        <template #subtitle>Validez les inscriptions et gérez les accès</template>
        <template #actions>
          <div class="header-stats">
            <div class="stat"><span class="stat-num yellow">{{ pendingUsers.length }}</span><span class="stat-label">En attente</span></div>
            <div class="stat"><span class="stat-num green">{{ activeUsers.length }}</span><span class="stat-label">Actifs</span></div>
            <div class="stat"><span class="stat-num gray">{{ disabledUsers.length }}</span><span class="stat-label">Désactivés</span></div>
          </div>
        </template>
      </PageHeader>

      <div class="page-content">
        <BaseAlert
          v-if="globalMessage"
          :variant="globalSuccess ? 'success' : 'error'"
          :icon="globalSuccess ? CheckCircle2 : XCircle"
          class="ds-page-feedback"
        >
          {{ globalMessage }}
        </BaseAlert>
        <div v-if="loading" class="ds-loading-state">
          <Loader2 class="spin" :size="22" aria-hidden="true" />
          Chargement...
        </div>

        <template v-else>
          <!-- TABS Statut -->
          <div class="tab-bar">
            <button v-for="t in statusTabs" :key="t.key" @click="activeStatus = t.key" :class="['tab', activeStatus === t.key ? 'tab-active' : '']">
              <span class="tab-dot" :class="t.dot"></span>
              {{ t.label }}
              <span class="tab-cnt">{{ t.count }}</span>
            </button>
          </div>

          <!-- FILTRES Rôle + Recherche -->
          <div class="toolbar">
            <div class="role-filters">
              <button @click="roleFilter = ''" :class="['rfb', roleFilter === '' ? 'rfb-active' : '']">Tous</button>
              <button @click="roleFilter = 'testeur'" :class="['rfb', 'btn-with-icon', roleFilter === 'testeur' ? 'rfb-active rfb-test' : '']">
                <FlaskConical :size="14" aria-hidden="true" />
                Testeurs
              </button>
              <button @click="roleFilter = 'developpeur'" :class="['rfb', 'btn-with-icon', roleFilter === 'developpeur' ? 'rfb-active rfb-dev' : '']">
                <Monitor :size="14" aria-hidden="true" />
                Développeurs
              </button>
            </div>
            <div class="ds-search">
              <Search class="ds-search-icon" :size="14" aria-hidden="true" />
              <input v-model="search" placeholder="Rechercher un membre..." class="ds-search-input" />
            </div>
          </div>

          <!-- TABLE UNIQUE -->
          <div v-if="!filteredUsers.length" class="empty">
            <Users class="ei" :size="40" aria-hidden="true" />
            <p class="et">Aucun membre dans cette catégorie.</p>
          </div>
          <div v-else class="card">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Membre</th>
                  <th>Rôle</th>
                  <th>GitHub</th>
                  <th>Statut</th>
                  <th>Inscrit le</th>
                  <th class="tc">Actions</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="u in filteredUsers" :key="u.id">
                  <tr>
                    <td>
                      <div class="uc">
                        <div class="av" :class="u.statut === 'actif' ? 'blue-av' : 'gray-av'">{{ ini(u) }}</div>
                        <div>
                          <p class="un">{{ u.prenom }} {{ u.nom }}</p>
                          <p class="ue">{{ u.email }}</p>
                        </div>
                      </div>
                    </td>
                    <td><span class="rb" :class="rc(u.role)">{{ roleLabel(u.role) }}</span></td>
                    <td><a v-if="u.github_link" :href="u.github_link" target="_blank" class="gh-link">GitHub ↗</a><span v-else class="mu">—</span></td>
                    <td><span class="st-chip" :class="stClass(u.statut)">{{ stLabel(u.statut) }}</span></td>
                    <td class="mu">{{ fmt(u.created_at) }}</td>
                    <td class="tc">
                      <div class="ab">
                        <template v-if="u.statut === 'en_attente'">
                          <button @click="valider(u.id)" :disabled="pid === u.id" class="ba btn-with-icon">
                            {{ pid === u.id ? '...' : '' }}
                            <Check v-if="pid !== u.id" :size="14" aria-hidden="true" />
                            Valider
                          </button>
                          <button @click="rejeter(u.id)" :disabled="pid === u.id" class="br btn-with-icon">
                            {{ pid === u.id ? '...' : '' }}
                            <X v-if="pid !== u.id" :size="14" aria-hidden="true" />
                            Rejeter
                          </button>
                        </template>
                        <button v-else-if="u.statut === 'actif'" @click="cid = u.id" class="bw btn-with-icon">
                          <Ban :size="14" aria-hidden="true" />
                          Désactiver
                        </button>
                        <button v-else-if="u.statut === 'desactive'" @click="reactiver(u.id)" class="bs btn-with-icon">
                          <RotateCcw :size="14" aria-hidden="true" />
                          Réactiver
                        </button>
                        <span v-else class="mu">—</span>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="cid === u.id" class="crow">
                    <td colspan="6">
                      <div class="cbar">
                        <p class="ct ct--warn">
                          <AlertTriangle :size="16" aria-hidden="true" />
                          Désactiver <strong>{{ u.prenom }} {{ u.nom }}</strong> ?
                        </p>
                        <div class="ca">
                          <button @click="cid = null" class="bcc">Annuler</button>
                          <button @click="desactiver(u.id)" class="bcw">Oui, désactiver</button>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
      </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { AlertTriangle, Ban, Check, CheckCircle2, FlaskConical, Loader2, Monitor, RotateCcw, Search, Users, X, XCircle } from 'lucide-vue-next';
import AppLayout from '../../components/layout/AppLayout.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import BaseAlert from '../../components/ui/BaseAlert.vue';

const allUsers = ref([]), loading = ref(false), globalMessage = ref(''), globalSuccess = ref(true), cid = ref(null), pid = ref(null);
const activeStatus = ref('en_attente');
const roleFilter = ref('');
const search = ref('');

const members = computed(() => allUsers.value.filter(u => !['chef_de_projet', 'admin'].includes(u.role)));
const pendingUsers  = computed(() => members.value.filter(u => u.statut === 'en_attente'));
const activeUsers   = computed(() => members.value.filter(u => u.statut === 'actif'));
const disabledUsers = computed(() => members.value.filter(u => u.statut === 'desactive'));

const statusTabs = computed(() => [
  { key: 'en_attente', label: 'En attente', count: pendingUsers.value.length,  dot: 'dot-yellow' },
  { key: 'actif',      label: 'Actifs',     count: activeUsers.value.length,   dot: 'dot-green'  },
  { key: 'desactive',  label: 'Désactivés', count: disabledUsers.value.length, dot: 'dot-gray'   },
  { key: 'rejete',     label: 'Rejetés',    count: members.value.filter(u => u.statut === 'rejete').length, dot: 'dot-red' },
]);

const filteredUsers = computed(() => {
  let list = members.value.filter(u => u.statut === activeStatus.value);
  if (roleFilter.value) list = list.filter(u => u.role === roleFilter.value);
  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    list = list.filter(u => u.nom.toLowerCase().includes(q) || u.prenom.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
  }
  return list;
});

const fetchUsers = async () => { loading.value = true; try { const r = await api.get('/users'); allUsers.value = r.data; } catch { msg('Erreur de chargement.', false); } finally { loading.value = false; } };
onMounted(fetchUsers);

const msg = (m, ok = true) => { globalMessage.value = m; globalSuccess.value = ok; setTimeout(() => globalMessage.value = '', 4000); };
const fmt = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—';
const ini = u => ((u.prenom || '')[0] + (u.nom || '')[0]).toUpperCase();
const rc = r => ({ developpeur: 'dev-rb', testeur: 'test-rb' }[r] || '');
const roleLabel = r => ({ developpeur: 'Développeur', testeur: 'Testeur' }[r] || r);
const stLabel = s => ({ en_attente: 'En attente', actif: 'Actif', desactive: 'Désactivé', rejete: 'Rejeté' }[s] || s);
const stClass = s => ({ en_attente: 'st-wait', actif: 'st-ok', desactive: 'st-off', rejete: 'st-rej' }[s] || '');

const valider  = async (id) => { if (pid.value) return; pid.value = id; try { await api.put(`/users/${id}/validate`, { action: 'accepter' }); msg('Compte validé'); await fetchUsers(); } catch { msg('Erreur.', false); } finally { pid.value = null; } };
const rejeter  = async (id) => { if (pid.value) return; pid.value = id; try { await api.put(`/users/${id}/validate`, { action: 'rejeter'  }); msg('Compte rejeté.');  await fetchUsers(); } catch { msg('Erreur.', false); } finally { pid.value = null; } };
const desactiver = async (id) => { cid.value = null; try { await api.put(`/users/${id}/deactivate`); msg('Compte désactivé.'); await fetchUsers(); } catch (e) { msg(e.response?.data?.message || 'Erreur.', false); } };
const reactiver  = async (id) => { try { await api.put(`/users/${id}/reactivate`); msg('Compte réactivé'); await fetchUsers(); } catch { msg('Erreur.', false); } };
</script>

<style scoped>
.header-stats{display:flex;gap:1.5rem;}
.stat{text-align:center;}
.stat-num{display:block;font-size:1.5rem;font-weight:800;line-height:1;}
.yellow{color:#d97706;}.green{color:#16a34a;}.gray{color:#94a3b8;}
.stat-label{font-size:.75rem;color:#94a3b8;}
.page-content{padding:2rem 2.5rem;display:flex;flex-direction:column;gap:1.5rem;}
.tab-bar{display:flex;gap:.375rem;background:white;border:1px solid #e2e8f0;border-radius:12px;padding:.375rem;width:fit-content;}
.tab{display:flex;align-items:center;gap:.5rem;padding:.5rem 1.125rem;border-radius:8px;border:none;background:none;font-size:.8125rem;font-weight:600;color:#64748b;cursor:pointer;font-family:inherit;transition:all .15s;}
.tab:hover{background:#f8fafc;color:#1e293b;}
.tab-active{background:#1e293b;color:white;}
.tab-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.dot-yellow{background:#f59e0b;}.dot-green{background:#22c55e;}.dot-gray{background:#94a3b8;}.dot-red{background:#ef4444;}
.tab-cnt{font-size:.6875rem;font-weight:700;padding:1px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;}
.tab-active .tab-cnt{background:rgba(255,255,255,.2);color:white;}
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.role-filters{display:flex;gap:.375rem;}
.rfb{padding:.4375rem .875rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.8125rem;font-weight:500;color:#64748b;background:white;cursor:pointer;font-family:inherit;transition:all .15s;}
.rfb:hover{border-color:#cbd5e1;color:#1e293b;}
.rfb-active{background:#1e293b;color:white;border-color:#1e293b;}
.rfb-test.rfb-active{background:#7c3aed;border-color:#7c3aed;}
.rfb-dev.rfb-active{background:#1d4ed8;border-color:#1d4ed8;}
.empty{text-align:center;padding:4rem 2rem;}
.ei{margin-bottom:.75rem;color:#94a3b8;}
.ct--warn{display:flex;align-items:center;gap:.5rem;}
.et{font-size:.9375rem;font-weight:600;color:#64748b;margin:0;}
.card{background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.tbl{width:100%;border-collapse:collapse;font-size:.875rem;}
.tbl thead{background:#f8fafc;}
.tbl th{padding:.75rem 1rem;text-align:left;font-size:.6875rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #e2e8f0;}
.tbl td{padding:.875rem 1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.tbl tbody tr:last-child td{border-bottom:none;}
.tbl tbody tr:hover:not(.crow) td{background:#f8fafc;}
.tc{text-align:center;}
.mu{color:#94a3b8;font-size:.8125rem;}
.uc{display:flex;align-items:center;gap:.625rem;}
.av{width:32px;height:32px;border-radius:8px;font-size:.6875rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-transform:uppercase;}
.blue-av{background:#dbeafe;color:#1d4ed8;}.gray-av{background:#f1f5f9;color:#94a3b8;}
.un{font-size:.875rem;font-weight:600;color:#1e293b;margin:0;}
.ue{font-size:.75rem;color:#94a3b8;margin:0;}
.rb{font-size:.6875rem;font-weight:700;padding:3px 9px;border-radius:20px;}
.dev-rb{background:#dbeafe;color:#1d4ed8;}.test-rb{background:#f3e8ff;color:#7c3aed;}
.gh-link{color:#3b82f6;font-size:.8125rem;text-decoration:none;}
.gh-link:hover{text-decoration:underline;}
.st-chip{font-size:.6875rem;font-weight:700;padding:3px 9px;border-radius:20px;}
.st-wait{background:#fef9c3;color:#92400e;}.st-ok{background:#dcfce7;color:#166534;}.st-off{background:#f1f5f9;color:#64748b;}.st-rej{background:#fee2e2;color:#dc2626;}
.ab{display:flex;gap:.375rem;justify-content:center;}
.ba{padding:5px 12px;background:#22c55e;color:white;border:none;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;}
.ba:hover:not(:disabled){background:#16a34a;}.ba:disabled{opacity:.5;cursor:not-allowed;}
.br{padding:5px 12px;background:#ef4444;color:white;border:none;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;}
.br:hover:not(:disabled){background:#dc2626;}.br:disabled{opacity:.5;cursor:not-allowed;}
.bw{padding:5px 12px;background:#fff7ed;color:#ea580c;border:1px solid #fed7aa;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;}
.bw:hover{background:#ea580c;color:white;}
.bs{padding:5px 12px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;}
.bs:hover{background:#22c55e;color:white;}
.crow td{padding:0;}
.cbar{background:#fff7ed;border-top:1px solid #fed7aa;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;}
.ct{font-size:.8125rem;color:#9a3412;margin:0;}
.ca{display:flex;gap:.5rem;}
.bcc{padding:5px 12px;background:white;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;}
.bcw{padding:5px 12px;background:#ea580c;color:white;border:none;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;}
</style>