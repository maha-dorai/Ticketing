// ============================================================
// main.ts — Le point d'entrée de l'application Vue
// C'est le premier fichier exécuté au démarrage
// ============================================================

// Importe le fichier CSS global qui charge Tailwind CSS
import './assets/index.css'

// createApp : la fonction principale de Vue 3 pour créer l'application
import { createApp } from 'vue'

// createPinia : crée le gestionnaire d'état global (stockage des données partagées entre les composants)
import { createPinia } from 'pinia'

// Importe toute la configuration des routes (pages) définie dans router/index.ts
import router from './router'

// Importe le composant racine App.vue — c'est la coquille qui contient toute l'application
import App from './App.vue'

// Crée l'application Vue en partant du composant App.vue
const app = createApp(App)

// Installe Pinia sur l'application — maintenant tous les composants peuvent utiliser les stores
app.use(createPinia())

// Installe Vue Router sur l'application — maintenant la navigation entre pages fonctionne
app.use(router)

// Monte l'application sur l'élément HTML ayant l'id "app" dans le fichier index.html
// C'est à ce moment que Vue prend le contrôle du navigateur et affiche l'interface
app.mount('#app')
