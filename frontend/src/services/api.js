// ============================================================
// api.js — Le pont de communication entre Vue et Laravel
// Toutes les requêtes HTTP passent par ce fichier
// ============================================================

// On importe axios : la librairie qui permet d'envoyer des requêtes HTTP (GET, POST, PUT, DELETE)
import axios from 'axios'

// On crée une instance axios personnalisée avec une configuration de base
const api = axios.create({
  // L'adresse de base du backend — définie dans le fichier .env du frontend (VITE_API_URL=http://localhost:8000/api)
  baseURL: import.meta.env.VITE_API_URL,

  // Ces headers disent au serveur qu'on envoie et qu'on attend du JSON
  headers: {
    'Content-Type': 'application/json', // on envoie du JSON
    'Accept': 'application/json',        // on veut recevoir du JSON
  }
})

// Intercepteur de requête : s'exécute AUTOMATIQUEMENT avant chaque appel API
// Son rôle : ajouter le token JWT dans le header si l'utilisateur est connecté
api.interceptors.request.use(config => {

  // On récupère le token JWT sauvegardé dans le localStorage du navigateur
  const token = localStorage.getItem('token')

  // Si un token existe (= l'utilisateur est connecté), on l'ajoute dans le header Authorization
  if (token) {
    // Le format "Bearer" est le standard pour envoyer un JWT dans HTTP
    config.headers.Authorization = `Bearer ${token}`
  }

  // On retourne la config modifiée — la requête part ensuite vers le serveur
  return config
})

// On exporte l'instance pour pouvoir l'utiliser dans tous les autres fichiers
export default api
