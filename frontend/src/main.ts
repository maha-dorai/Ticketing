import './assets/index.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/authStore'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Restaure la session depuis localStorage au démarrage
const authStore = useAuthStore()
authStore.init()

app.mount('#app')