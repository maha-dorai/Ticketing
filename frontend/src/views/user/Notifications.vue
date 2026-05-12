<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Header & Navigation -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900">Notifications</h1>
          <p class="text-gray-500 text-sm mt-0.5">Gardez un œil sur l'activité de vos tickets</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="$router.push({ name: 'Projects' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            📂 Projets
          </button>
          <button @click="$router.push({ name: 'Tickets' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            🎟️ Tickets
          </button>
          <button @click="$router.push({ name: 'Notifications' })" class="px-4 py-2 text-sm text-blue-700 bg-blue-100 rounded font-semibold ring-2 ring-blue-500">
            🔔 Notifications
          </button>
          <button @click="$router.push({ name: 'Profile' })" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 font-semibold transition">
            👤 Mon compte
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end">
        <button v-if="unreadCount > 0" @click="markAllAsRead" :disabled="marking" class="px-4 py-2 text-sm text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg font-bold transition disabled:opacity-50">
          ✓ Marquer tout comme lu
        </button>
      </div>

      <!-- Chargement -->
      <div v-if="loading" class="text-center text-gray-400 py-12 text-sm">Chargement...</div>

      <!-- Aucune notif -->
      <div v-else-if="notifications.length === 0" class="text-center py-16 bg-white rounded-xl shadow border border-gray-100 text-gray-400">
        <div class="text-4xl mb-3">📭</div>
        <p class="font-medium text-gray-500">Vous n'avez aucune notification.</p>
      </div>

      <!-- Liste des notifs -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden divide-y">
        <div v-for="notif in notifications" :key="notif.id" 
             @click="goToTicket(notif)"
             class="p-4 hover:bg-gray-50 cursor-pointer transition flex items-start gap-4"
             :class="notif.lu ? 'opacity-60' : 'bg-blue-50/30'">
          
          <div class="text-xl pt-1">
            <span v-if="!notif.lu">🔵</span>
            <span v-else>⚪</span>
          </div>
          
          <div class="flex-1">
            <p class="text-sm font-semibold text-gray-800" :class="!notif.lu ? 'text-gray-900' : 'text-gray-600'">
              {{ notif.message }}
            </p>
            <p class="text-xs text-gray-400 mt-1">{{ formatTime(notif.created_at) }}</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const router = useRouter();
const notifications = ref([]);
const loading = ref(true);
const marking = ref(false);

const unreadCount = computed(() => notifications.value.filter(n => !n.lu).length);

const fetchNotifications = async () => {
  try {
    const res = await api.get('/notifications');
    notifications.value = res.data;
  } catch (e) {
    console.error("Erreur notifications", e);
  } finally {
    loading.value = false;
  }
};

const markAllAsRead = async () => {
  const unreadIds = notifications.value.filter(n => !n.lu).map(n => n.id);
  if (!unreadIds.length) return;
  
  marking.value = true;
  try {
    await api.put('/notifications/read', { notification_ids: unreadIds });
    notifications.value.forEach(n => n.lu = true);
  } catch (e) {
    console.error(e);
  } finally {
    marking.value = false;
  }
};

const goToTicket = async (notif) => {
  // Mark as read locally and in API if not read
  if (!notif.lu) {
    try {
      await api.put('/notifications/read', { notification_ids: [notif.id] });
      notif.lu = true;
    } catch(e) {}
  }
  
  if (notif.ticket_id) {
    router.push({ name: 'TicketDetails', params: { id: notif.ticket_id } });
  }
};

onMounted(() => {
  fetchNotifications();
});

const formatTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }) : '';
</script>
