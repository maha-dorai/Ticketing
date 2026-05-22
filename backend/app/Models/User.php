<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'role',                   // 'admin' | 'chef_de_projet' | 'testeur' | 'developpeur'
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

    // Vérifie si l'utilisateur a des droits admin (chef_de_projet ou admin)
    public function isAdmin(): bool
    {
        return in_array($this->role, ['chef_de_projet', 'admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
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

    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'testeur_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'developpeur_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}