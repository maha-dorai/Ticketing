<?php
// ============================================================
// app/Models/User.php — Le modèle Utilisateur
// Représente la table "users" dans la base de données
// Chaque instance de User = une ligne dans la table
// ============================================================

namespace App\Models;

// Authenticatable : la classe de base Laravel pour les utilisateurs (gère l'auth)
use Illuminate\Foundation\Auth\User as Authenticatable;

// JWTSubject : interface obligatoire pour que tymon/jwt-auth puisse générer des tokens JWT pour cet utilisateur
use Tymon\JWTAuth\Contracts\JWTSubject;

// La classe User implémente JWTSubject pour permettre la génération de tokens JWT
class User extends Authenticatable implements JWTSubject
{
    // Nom exact de la table en base de données
    // (Laravel utiliserait "users" par défaut mais on le précise explicitement)
    protected $table = 'users';

    // Liste des colonnes que l'on peut remplir via User::create() ou $user->update()
    // Protection contre les attaques "mass assignment" : seuls ces champs sont modifiables
    protected $fillable = [
        'nom',                  // nom de famille
        'prenom',               // prénom
        'email',                // adresse email unique
        'mot_de_passe',         // mot de passe hashé (bcrypt)
        'role',                 // 'testeur', 'developpeur' ou 'admin'
        'statut',               // 'en_attente', 'actif' ou 'rejete'
        'github_link',          // lien GitHub (obligatoire pour les développeurs)
        'reset_token',          // token temporaire pour réinitialiser le mot de passe
        'reset_token_expires',  // date d'expiration du token de réinitialisation
    ];

    // Colonnes cachées : jamais incluses dans les réponses JSON retournées au frontend
    // Le mot de passe et le token de reset ne doivent JAMAIS être exposés via l'API
    protected $hidden = ['mot_de_passe', 'reset_token'];

    // ─────────────────────────────────────────────
    // MÉTHODES REQUISES PAR JWTSubject
    // Ces deux méthodes sont obligatoires pour que jwt-auth fonctionne
    // ─────────────────────────────────────────────

    // Retourne l'identifiant unique de l'utilisateur utilisé pour créer le JWT
    // getKey() retourne la valeur de la clé primaire (id)
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Retourne des informations supplémentaires à intégrer dans le token JWT
    // Ces données seront encodées dans le token et accessibles sans appeler la DB
    public function getJWTCustomClaims()
    {
        return [
            'role'  => $this->role,   // le rôle est encodé dans le JWT pour des vérifications rapides
            'email' => $this->email,  // l'email est aussi inclus dans le token
        ];
    }

    // ─────────────────────────────────────────────
    // SURCHARGE DU MOT DE PASSE
    // ─────────────────────────────────────────────

    // Par défaut Laravel cherche la colonne "password" pour l'authentification
    // Notre table utilise "mot_de_passe" — on surcharge cette méthode pour pointer vers la bonne colonne
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    // Relation Many-to-Many avec les Projets
    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}