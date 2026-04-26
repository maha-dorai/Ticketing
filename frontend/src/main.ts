// Importe de force tout le réseau CSS configuré spécifiquement pour Tailwind. C'est l'essence du styling de la plateforme !
import './assets/index.css'

// Importe de l'usine Vue3 le concept même d'Application logicielle (createApp)
import { createApp } from 'vue'
// Importe le centre d'entrepôt Pinia destiné à gérer en mémoire les connexions des employés mock.
import { createPinia } from 'pinia'
// Tire manuellement depuis le dossier Root/Router tous les paramètres complexes configurants nos Url et Gardien (Sécurisation Admin)
import router from './router'
// Incorpore l'architecture physique du Dom Vue central 'App' comme socle initial 
import App from './App.vue'

// La genese : On construit "l'APP" autour de l'instance vide initialisée par 'App.vue' (le chef d'orchestre final Web).
const app = createApp(App)

// Phase d'Armement (Installation Plugins sur Node). On déclare Pinia pour l'utilisation et la disponibilité Universelle
app.use(createPinia())
// Idem mais avec les mécaniques de Web Router !
app.use(router)

// Dernière ligne au démarrage Web (Lancement réel du Programme !) : "Vise ce code sur l'id DIV appelé '#app' de ton fichier global index.html ".
app.mount('#app')
