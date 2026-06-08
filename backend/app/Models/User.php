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
        'force_password_change',
        'reset_token',
        'reset_token_expires',
    ];

    protected $hidden = ['mot_de_passe', 'reset_token'];

    protected $casts = [
        'force_password_change' => 'boolean',
    ];

    // ─── JWT ──────────────────────────────────────────────────────────────────

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

    // ─── RELATIONS ────────────────────────────────────────────────────────────

    public function membre()
    {
        return $this->hasOne(Membre::class);
    }

    public function chefDeProjet()
    {
        return $this->hasOne(ChefDeProjet::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'created_by');
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

    // ─── ACCESSORS ────────────────────────────────────────────────────────────

    public function getRoleAttribute(): ?string
    {
        // Check admin (via chefDeProjet -> admin)
        if ($this->chefDeProjet && $this->chefDeProjet->admin) {
            return 'admin';
        }
        // Check chef_de_projet
        if ($this->chefDeProjet) {
            return 'chef_de_projet';
        }
        // Check membre
        if ($this->membre) {
            return $this->membre->role; // 'testeur' ou 'developpeur'
        }
        return null;
    }

    public function getStatutAttribute(): ?string
    {
        if ($this->membre) {
            return $this->membre->statut;
        }
        // admin et chef_de_projet sont toujours actifs
        if ($this->chefDeProjet) {
            return 'actif';
        }
        return null;
    }

    public function getGithubLinkAttribute(): ?string
    {
        return $this->membre?->github_link;
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    public function isManager(): bool
    {
        return in_array($this->role, ['chef_de_projet', 'admin']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}