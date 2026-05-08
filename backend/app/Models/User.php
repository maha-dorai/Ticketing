<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'role',                   // 'testeur' | 'developpeur' | 'admin' | 'super_admin'
        'statut',                 // 'en_attente' | 'actif' | 'rejete' | 'desactive'
        'github_link',
        'force_password_change',  // true = obligé de changer le mdp à la prochaine connexion
        'reset_token',
        'reset_token_expires',
    ];

    protected $hidden = ['mot_de_passe', 'reset_token'];

    protected $casts = [
        'force_password_change' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role'                  => $this->role,
            'email'                 => $this->email,
            'force_password_change' => (bool) $this->force_password_change,
        ];
    }

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    // Vérifie si l'utilisateur a des droits admin (admin ou super_admin)
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // Relation Many-to-Many avec les Projets
    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    // Projets créés par cet utilisateur
    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }
}