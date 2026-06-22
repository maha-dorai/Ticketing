import axios from 'axios'

/**
 * [Sprint 1] Configuration centrale d'Axios.
 * Ce fichier est le "pont" entre le Frontend (Vue.js) et le Backend (Laravel).
 * C'est ce qui rend notre application une SPA (Single Page Application) "découplée".
 */
const api = axios.create({
  // L'URL de base pointe vers notre API Laravel (ex: http://localhost:8000/api)
  baseURL: import.meta.env.VITE_API_URL as string,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json', // Force Laravel à toujours répondre en JSON, même lors d'erreurs
  }
})

/**
 * [Sprint 1] Intercepteur de requêtes (L'équivalent d'un Middleware côté Frontend).
 * Avant CHAQUE requête envoyée au serveur, ce bloc de code s'exécute.
 * Il récupère le token JWT stocké dans le localStorage et l'injecte dans les headers.
 * C'est ce mécanisme qui remplace les "Sessions classiques" PHP.
 */
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    // Si l'utilisateur est connecté, on ajoute le badge "Authorization: Bearer <token>"
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api