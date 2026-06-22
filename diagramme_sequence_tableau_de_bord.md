# 6.4.2 Diagramme de séquence « Consultation du tableau de bord » (Modèle MVC - Optimisé Draw.io)

Ce code Mermaid est optimisé pour être copié et collé directement dans **Draw.io** (via le menu `Insérer -> Avancé -> Mermaid`).

## Code Mermaid à copier

```mermaid
sequenceDiagram
    autonumber
    actor Utilisateur as "Utilisateur"
    participant Vue as "Vue (Interface Frontend)"
    participant Ctrl as "Contrôleur (StatsController)"
    participant Model as "Modèle (Calculs & Données)"

    %% 1. Navigation
    Utilisateur->>Vue: Clique sur "Tableau de bord"
    Note over Vue: Identifie le rôle de l'utilisateur connecté

    %% 2. Branchement selon le rôle (Appels API)
    alt Rôle = Administrateur
        Vue->>Ctrl: Appel route : GET /api/stats/admin
        Ctrl->>Model: Demande les indicateurs globaux
        Model-->>Ctrl: Retourne les chiffres globaux consolidés
        Ctrl-->>Vue: Envoie les données administratives (JSON)
        Note over Vue: Rendu des graphiques d'administration

    else Rôle = Chef de Projet (CP)
        Vue->>Ctrl: Appel route : GET /api/stats/manager/{projectId}
        Ctrl->>Model: Demande l'avancement et la charge du projet
        Model-->>Ctrl: Retourne les chiffres du projet consolidés
        Ctrl-->>Vue: Envoie les données du projet (JSON)
        Note over Vue: Rendu des graphiques projet

    else Rôle = Développeur ou Testeur
        Vue->>Ctrl: Appel route : GET /api/stats/me
        Ctrl->>Model: Demande les indicateurs personnels
        Model-->>Ctrl: Retourne les statistiques personnelles consolidées
        Ctrl-->>Vue: Envoie les données de l'utilisateur (JSON)
        Note over Vue: Rendu des graphiques personnels

    end

    %% 3. Restitution finale
    Vue-->>Utilisateur: Affiche le tableau de bord personnalisé
```

---

## Les Étapes Expliquées (MVC)

### 1. La Vue (Interface Frontend)
* L'**Utilisateur** clique sur le bouton "Tableau de bord".
* La **Vue** identifie son rôle et déclenche l'appel vers la route correspondante sur le contrôleur :
  * `GET /api/stats/admin` pour l'Administrateur.
  * `GET /api/stats/manager/{projectId}` pour le Chef de Projet.
  * `GET /api/stats/me` pour le Développeur ou le Testeur.

### 2. Le Contrôleur (`StatsController`)
* Le **Contrôleur** reçoit la requête de la Vue et orchestre la collecte des statistiques en sollicitant le **Modèle**.

### 3. Le Modèle
* Le **Modèle** se charge de traiter, de compter et de consolider les données nécessaires (ex. : taux de résolution des tickets, volume de tickets par priorité ou activité par développeur).
* Une fois les chiffres calculés, le **Modèle** renvoie les résultats au Contrôleur.

### 4. Rendu de la Vue
* Le **Contrôleur** retourne les statistiques formatées à la **Vue**.
* La **Vue** reçoit ces données et dessine dynamiquement les graphiques (diagrammes circulaires, courbes, barres d'avancement) pour les présenter à l'**Utilisateur**.
